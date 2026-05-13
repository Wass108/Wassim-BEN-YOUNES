<?php
require_once '../db/db.php';
require_once 'gemini_helper.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin/login.php');
    exit;
}

/**
 * Calcule la vitesse de vente moyenne (unités/jour) sur X jours
 */
function calculerVitesseVente($pdo, $produit_id, $jours = 60) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(dc.quantite), 0) as total
        FROM details_commande dc
        JOIN commandes cmd ON dc.commande_id = cmd.id
        WHERE dc.produit_id = ?
          AND cmd.statut != 'annulee'
          AND cmd.date_commande >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$produit_id, $jours]);
    $total = $stmt->fetchColumn();
    return $total / $jours; // vitesse = unités/jour
}

// Analyse de tous les produits
$stmt = $pdo->query("
    SELECT p.id, p.nom, p.stock, p.reference_produit, c.nom as categorie
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE p.disponibilite = TRUE
");
$produits = $stmt->fetchAll();

$predictions = [];
foreach ($produits as $p) {
    $vitesse = calculerVitesseVente($pdo, $p['id'], STOCK_HISTORY_DAYS);
    $jours_restants = $vitesse > 0 ? round($p['stock'] / $vitesse) : 999;
    
    $niveau = 'ok';
    if ($p['stock'] == 0) $niveau = 'rupture';
    elseif ($jours_restants <= STOCK_CRITICAL_DAYS) $niveau = 'critique';
    elseif ($jours_restants <= STOCK_ALERT_DAYS) $niveau = 'alerte';
    
    $predictions[] = array_merge($p, [
        'vitesse' => round($vitesse, 2),
        'jours_restants' => $jours_restants,
        'niveau' => $niveau,
        'reappro_suggere' => $vitesse > 0 ? ceil($vitesse * 30) : 0
    ]);
}

// Trier : ruptures/critiques en premier
usort($predictions, function($a, $b) {
    $order = ['rupture' => 0, 'critique' => 1, 'alerte' => 2, 'ok' => 3];
    return $order[$a['niveau']] <=> $order[$b['niveau']];
});

$conseils_ia = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conseils'])) {
    $critiques = array_filter($predictions, fn($p) => in_array($p['niveau'], ['rupture', 'critique', 'alerte']));
    
    $prompt = "Tu es un expert en gestion de stock pour une boutique d'éclairage.

PRODUITS À RISQUE DE RUPTURE :
" . json_encode(array_slice($critiques, 0, 20), JSON_UNESCAPED_UNICODE) . "

Champs : nom, stock (actuel), vitesse (ventes/jour), jours_restants (avant rupture), niveau, reappro_suggere.

Fournis des conseils stratégiques en JSON :
{
  \"resume\": \"Résumé de la situation stock en 2-3 phrases\",
  \"actions_urgentes\": [
    {\"produit\": \"nom\", \"action\": \"...\", \"quantite_conseillee\": 50}
  ],
  \"recommandations_generales\": [\"conseil 1\", \"conseil 2\", \"conseil 3\"],
  \"alerte_prioritaire\": \"Message d'alerte principal\"
}

Réponds UNIQUEMENT en JSON valide.";

    $result = callGemini($prompt, 0.5);
    if ($result['success']) {
        $conseils_ia = extractJsonFromGemini($result['text']);
        if (!$conseils_ia) $error = "Erreur parsing IA";
    } else {
        $error = $result['error'];
    }
}

// Compteurs
$nb_rupture = count(array_filter($predictions, fn($p) => $p['niveau'] === 'rupture'));
$nb_critique = count(array_filter($predictions, fn($p) => $p['niveau'] === 'critique'));
$nb_alerte = count(array_filter($predictions, fn($p) => $p['niveau'] === 'alerte'));
$nb_ok = count(array_filter($predictions, fn($p) => $p['niveau'] === 'ok'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prédiction Stock IA | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-warehouse text-orange-600 mr-2"></i>
                Prédiction des ruptures de stock
            </h1>
            <a href="../admin/dashboard.php" class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- KPIs -->
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-red-500 text-white rounded-2xl p-6 shadow-lg">
                <i class="fas fa-times-circle text-3xl opacity-80 mb-2"></i>
                <p class="text-sm opacity-90">Rupture</p>
                <h3 class="text-3xl font-bold"><?= $nb_rupture ?></h3>
            </div>
            <div class="bg-orange-500 text-white rounded-2xl p-6 shadow-lg">
                <i class="fas fa-exclamation-triangle text-3xl opacity-80 mb-2"></i>
                <p class="text-sm opacity-90">Critique (&lt; <?= STOCK_CRITICAL_DAYS ?>j)</p>
                <h3 class="text-3xl font-bold"><?= $nb_critique ?></h3>
            </div>
            <div class="bg-yellow-500 text-white rounded-2xl p-6 shadow-lg">
                <i class="fas fa-exclamation-circle text-3xl opacity-80 mb-2"></i>
                <p class="text-sm opacity-90">Alerte (&lt; <?= STOCK_ALERT_DAYS ?>j)</p>
                <h3 class="text-3xl font-bold"><?= $nb_alerte ?></h3>
            </div>
            <div class="bg-green-500 text-white rounded-2xl p-6 shadow-lg">
                <i class="fas fa-check-circle text-3xl opacity-80 mb-2"></i>
                <p class="text-sm opacity-90">OK</p>
                <h3 class="text-3xl font-bold"><?= $nb_ok ?></h3>
            </div>
        </div>

        <!-- Bouton IA -->
        <form method="POST" class="mb-6">
            <button type="submit" name="conseils"
                    class="px-8 py-4 bg-gradient-to-r from-orange-600 to-red-600 text-white rounded-xl font-bold shadow-lg">
                <i class="fas fa-brain mr-2"></i>
                Obtenir conseils IA
            </button>
        </form>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded mb-6">
                <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($conseils_ia): ?>
            <div class="bg-gradient-to-r from-red-600 to-orange-600 rounded-2xl p-6 text-white shadow-lg mb-6">
                <h3 class="text-xl font-bold mb-2"><i class="fas fa-bell mr-2"></i>Alerte prioritaire</h3>
                <p class="opacity-95"><?= htmlspecialchars($conseils_ia['alerte_prioritaire'] ?? '') ?></p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-3"><i class="fas fa-chart-line text-orange-600 mr-2"></i>Résumé situation</h3>
                <p class="text-gray-700"><?= htmlspecialchars($conseils_ia['resume'] ?? '') ?></p>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4"><i class="fas fa-bolt text-red-600 mr-2"></i>Actions urgentes</h3>
                <?php foreach (($conseils_ia['actions_urgentes'] ?? []) as $a): ?>
                    <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded mb-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold"><?= htmlspecialchars($a['produit']) ?></h4>
                                <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($a['action']) ?></p>
                            </div>
                            <span class="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                +<?= (int)($a['quantite_conseillee'] ?? 0) ?> unités
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-3"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Recommandations générales</h3>
                <ul class="space-y-2">
                    <?php foreach (($conseils_ia['recommandations_generales'] ?? []) as $r): ?>
                        <li class="flex"><i class="fas fa-arrow-right text-blue-500 mr-2 mt-1"></i><span><?= htmlspecialchars($r) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Tableau des prédictions -->
        <div class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-xl font-bold mb-4"><i class="fas fa-list mr-2"></i>Prédictions détaillées</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 text-left">Produit</th>
                            <th class="p-3 text-left">Catégorie</th>
                            <th class="p-3 text-center">Stock</th>
                            <th class="p-3 text-center">Ventes/jour</th>
                            <th class="p-3 text-center">Rupture dans</th>
                            <th class="p-3 text-center">Réappro suggéré</th>
                            <th class="p-3 text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($predictions as $p): 
                            $colors = [
                                'rupture' => ['bg-red-100', 'text-red-800', 'Rupture'],
                                'critique' => ['bg-orange-100', 'text-orange-800', 'Critique'],
                                'alerte' => ['bg-yellow-100', 'text-yellow-800', 'Alerte'],
                                'ok' => ['bg-green-100', 'text-green-800', 'OK']
                            ];
                            $c = $colors[$p['niveau']];
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-semibold"><?= htmlspecialchars($p['nom']) ?></td>
                                <td class="p-3 text-gray-600"><?= htmlspecialchars($p['categorie'] ?? '-') ?></td>
                                <td class="p-3 text-center font-bold"><?= $p['stock'] ?></td>
                                <td class="p-3 text-center"><?= $p['vitesse'] ?></td>
                                <td class="p-3 text-center">
                                    <?= $p['jours_restants'] < 999 ? $p['jours_restants'] . ' j' : '∞' ?>
                                </td>
                                <td class="p-3 text-center">
                                    <?php if ($p['reappro_suggere'] > 0): ?>
                                        <span class="text-blue-600 font-semibold">+<?= $p['reappro_suggere'] ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $c[0] ?> <?= $c[1] ?>">
                                        <?= $c[2] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
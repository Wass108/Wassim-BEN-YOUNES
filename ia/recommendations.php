<?php
require_once '../db/db.php';
require_once 'gemini_helper.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin/login.php');
    exit;
}

$recommendations = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    // Récupérer les produits les plus vendus
    $stmt = $pdo->query("
        SELECT p.id, p.nom, p.prix, p.stock, p.marque, c.nom as categorie,
               COALESCE(SUM(dc.quantite), 0) as total_vendus
        FROM produits p
        LEFT JOIN categories c ON p.categorie_id = c.id
        LEFT JOIN details_commande dc ON p.id = dc.produit_id
        LEFT JOIN commandes cmd ON dc.commande_id = cmd.id AND cmd.statut != 'annulee'
        GROUP BY p.id
        ORDER BY total_vendus DESC
        LIMIT 20
    ");
    $produits = $stmt->fetchAll();

    // Récupérer les produits peu vendus (à mettre en avant)
    $stmt = $pdo->query("
        SELECT p.id, p.nom, p.prix, p.stock, c.nom as categorie,
               COALESCE(SUM(dc.quantite), 0) as total_vendus
        FROM produits p
        LEFT JOIN categories c ON p.categorie_id = c.id
        LEFT JOIN details_commande dc ON p.id = dc.produit_id
        WHERE p.stock > 0
        GROUP BY p.id
        ORDER BY total_vendus ASC
        LIMIT 10
    ");
    $peu_vendus = $stmt->fetchAll();

    $prompt = "Tu es un expert en marketing pour une boutique d'éclairage tunisienne (SOUILEM LIGHTING).

PRODUITS LES PLUS VENDUS :
" . json_encode($produits, JSON_UNESCAPED_UNICODE) . "

PRODUITS PEU VENDUS (en stock) :
" . json_encode($peu_vendus, JSON_UNESCAPED_UNICODE) . "

Analyse ces données et recommande :
1. Les " . RECO_MAX_PRODUCTS . " produits à METTRE EN AVANT (bestsellers à promouvoir)
2. Les produits à METTRE EN PROMOTION (peu vendus, à booster)
3. Des suggestions de CROSS-SELLING (produits complémentaires)

Réponds UNIQUEMENT en JSON valide avec cette structure exacte :
{
  \"mise_en_avant\": [
    {\"id\": 1, \"nom\": \"...\", \"raison\": \"...\"}
  ],
  \"a_promouvoir\": [
    {\"id\": 2, \"nom\": \"...\", \"pourcentage_promo\": 15, \"raison\": \"...\"}
  ],
  \"cross_selling\": [
    {\"produit_principal\": \"...\", \"a_suggerer\": [\"...\", \"...\"], \"raison\": \"...\"}
  ],
  \"strategie_globale\": \"Résumé en 2-3 phrases de la stratégie recommandée\"
}";

    $result = callGemini($prompt, 0.6);
    
    if ($result['success']) {
        $recommendations = extractJsonFromGemini($result['text']);
        if (!$recommendations) {
            $error = "Erreur de parsing de la réponse IA";
        }
    } else {
        $error = "Erreur API : " . ($result['error'] ?? 'inconnue');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recommandations IA | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-robot text-purple-600 mr-2"></i>
                Recommandations IA
            </h1>
            <a href="../admin/dashboard.php" class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-8 max-w-6xl">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white mb-8 shadow-lg">
            <h2 class="text-3xl font-bold mb-2">
                <i class="fas fa-magic mr-2"></i>Recommandations Intelligentes
            </h2>
            <p class="opacity-90">Analyse par IA de votre catalogue pour optimiser vos ventes</p>
        </div>

        <form method="POST" class="mb-8">
            <button type="submit" name="generate" 
                    class="px-8 py-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transition">
                <i class="fas fa-brain mr-2"></i>
                Générer les recommandations IA
            </button>
        </form>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded mb-6">
                <p class="text-red-700"><i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($recommendations): ?>
            <!-- Stratégie globale -->
            <div class="bg-white rounded-2xl shadow p-6 mb-6 border-l-4 border-purple-500">
                <h3 class="text-lg font-bold mb-3"><i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Stratégie globale</h3>
                <p class="text-gray-700"><?= htmlspecialchars($recommendations['strategie_globale'] ?? '') ?></p>
            </div>

            <!-- Mise en avant -->
            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4 text-green-700">
                    <i class="fas fa-star mr-2"></i>Produits à mettre en avant
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach (($recommendations['mise_en_avant'] ?? []) as $p): ?>
                        <div class="border border-green-200 bg-green-50 rounded-xl p-4">
                            <h4 class="font-bold text-gray-900"><?= htmlspecialchars($p['nom']) ?></h4>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($p['raison']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- À promouvoir -->
            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4 text-orange-700">
                    <i class="fas fa-percent mr-2"></i>Produits à mettre en promotion
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <?php foreach (($recommendations['a_promouvoir'] ?? []) as $p): ?>
                        <div class="border border-orange-200 bg-orange-50 rounded-xl p-4">
                            <div class="flex justify-between items-start">
                                <h4 class="font-bold text-gray-900"><?= htmlspecialchars($p['nom']) ?></h4>
                                <span class="bg-orange-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                    -<?= (int)($p['pourcentage_promo'] ?? 0) ?>%
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($p['raison']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cross-selling -->
            <div class="bg-white rounded-2xl shadow p-6">
                <h3 class="text-xl font-bold mb-4 text-blue-700">
                    <i class="fas fa-link mr-2"></i>Suggestions de ventes croisées
                </h3>
                <?php foreach (($recommendations['cross_selling'] ?? []) as $cs): ?>
                    <div class="border border-blue-200 bg-blue-50 rounded-xl p-4 mb-3">
                        <p class="font-bold text-gray-900">
                            <i class="fas fa-box mr-1 text-blue-600"></i>
                            <?= htmlspecialchars($cs['produit_principal']) ?>
                        </p>
                        <p class="text-sm text-gray-700 mt-1">
                            <strong>À suggérer :</strong> <?= htmlspecialchars(implode(', ', $cs['a_suggerer'] ?? [])) ?>
                        </p>
                        <p class="text-xs text-gray-600 mt-2 italic"><?= htmlspecialchars($cs['raison']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
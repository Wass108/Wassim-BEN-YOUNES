<?php
require_once '../db/db.php';
require_once 'gemini_helper.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../admin/login.php');
    exit;
}

// Données de base (toujours affichées)
$ca_total = $pdo->query("SELECT SUM(montant_total) FROM commandes WHERE statut != 'annulee'")->fetchColumn() ?: 0;
$nb_commandes = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut != 'annulee'")->fetchColumn();
$panier_moyen = $nb_commandes > 0 ? $ca_total / $nb_commandes : 0;

// Ventes par mois (6 derniers mois)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(date_commande, '%Y-%m') as mois,
           COUNT(*) as nb_commandes,
           SUM(montant_total) as ca
    FROM commandes
    WHERE statut != 'annulee'
      AND date_commande >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
");
$ventes_mois = $stmt->fetchAll();

// Top catégories
$stmt = $pdo->query("
    SELECT c.nom as categorie,
           SUM(dc.quantite) as total_vendus,
           SUM(dc.quantite * dc.prix_unitaire) as ca_categorie
    FROM details_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN categories c ON p.categorie_id = c.id
    JOIN commandes cmd ON dc.commande_id = cmd.id
    WHERE cmd.statut != 'annulee'
    GROUP BY c.id
    ORDER BY ca_categorie DESC
    LIMIT 10
");
$top_categories = $stmt->fetchAll();

// Top 10 produits
$stmt = $pdo->query("
    SELECT p.nom, SUM(dc.quantite) as qte_vendue, SUM(dc.quantite * dc.prix_unitaire) as ca
    FROM details_commande dc
    JOIN produits p ON dc.produit_id = p.id
    JOIN commandes cmd ON dc.commande_id = cmd.id
    WHERE cmd.statut != 'annulee'
    GROUP BY p.id
    ORDER BY ca DESC
    LIMIT 10
");
$top_produits = $stmt->fetchAll();

$analyse_ia = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['analyser'])) {
    $prompt = "Tu es un analyste business expert en e-commerce d'éclairage (SOUILEM LIGHTING, Tunisie).

DONNÉES DE VENTES :
- Chiffre d'affaires total : " . number_format($ca_total, 2) . " TND
- Nombre de commandes : $nb_commandes
- Panier moyen : " . number_format($panier_moyen, 2) . " TND

VENTES PAR MOIS (6 derniers mois) :
" . json_encode($ventes_mois, JSON_UNESCAPED_UNICODE) . "

TOP CATÉGORIES :
" . json_encode($top_categories, JSON_UNESCAPED_UNICODE) . "

TOP 10 PRODUITS :
" . json_encode($top_produits, JSON_UNESCAPED_UNICODE) . "

Analyse ces données et fournis une analyse complète en JSON avec cette structure :
{
  \"tendance_generale\": \"Analyse en 2-3 phrases de la tendance\",
  \"points_forts\": [\"point 1\", \"point 2\", \"point 3\"],
  \"points_faibles\": [\"point 1\", \"point 2\"],
  \"opportunites\": [\"opportunité 1\", \"opportunité 2\", \"opportunité 3\"],
  \"actions_prioritaires\": [
    {\"titre\": \"...\", \"description\": \"...\", \"priorite\": \"haute/moyenne/basse\"}
  ],
  \"prevision_mois_prochain\": \"Prévision chiffrée et justifiée\"
}

Réponds UNIQUEMENT en JSON valide.";

    $result = callGemini($prompt, 0.5);
    if ($result['success']) {
        $analyse_ia = extractJsonFromGemini($result['text']);
        if (!$analyse_ia) $error = "Erreur de parsing";
    } else {
        $error = "Erreur API : " . $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyse des ventes IA | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                Analyse des ventes IA
            </h1>
            <a href="../admin/dashboard.php" class="text-blue-600 hover:underline">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </nav>

    <main class="container mx-auto px-6 py-8 max-w-7xl">
        <!-- KPIs -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-sm opacity-90">Chiffre d'affaires</p>
                <h3 class="text-3xl font-bold mt-2"><?= number_format($ca_total, 0, ',', ' ') ?> TND</h3>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-sm opacity-90">Commandes</p>
                <h3 class="text-3xl font-bold mt-2"><?= $nb_commandes ?></h3>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-sm opacity-90">Panier moyen</p>
                <h3 class="text-3xl font-bold mt-2"><?= number_format($panier_moyen, 2) ?> TND</h3>
            </div>
        </div>

        <!-- Graphique -->
        <div class="bg-white rounded-2xl shadow p-6 mb-8">
            <h3 class="text-xl font-bold mb-4">Évolution du chiffre d'affaires (6 mois)</h3>
            <canvas id="chartVentes" height="80"></canvas>
        </div>

        <!-- Bouton analyse IA -->
        <form method="POST" class="mb-6">
            <button type="submit" name="analyser"
                    class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl">
                <i class="fas fa-brain mr-2"></i>
                Lancer l'analyse IA
            </button>
        </form>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded mb-6">
                <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($analyse_ia): ?>
            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-3"><i class="fas fa-chart-bar text-blue-600 mr-2"></i>Tendance générale</h3>
                <p class="text-gray-700"><?= htmlspecialchars($analyse_ia['tendance_generale'] ?? '') ?></p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-2xl shadow p-6 border-t-4 border-green-500">
                    <h3 class="font-bold text-green-700 mb-3"><i class="fas fa-thumbs-up mr-2"></i>Points forts</h3>
                    <ul class="space-y-2">
                        <?php foreach (($analyse_ia['points_forts'] ?? []) as $pf): ?>
                            <li class="flex"><i class="fas fa-check text-green-500 mr-2 mt-1"></i><span><?= htmlspecialchars($pf) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="bg-white rounded-2xl shadow p-6 border-t-4 border-red-500">
                    <h3 class="font-bold text-red-700 mb-3"><i class="fas fa-thumbs-down mr-2"></i>Points faibles</h3>
                    <ul class="space-y-2">
                        <?php foreach (($analyse_ia['points_faibles'] ?? []) as $pf): ?>
                            <li class="flex"><i class="fas fa-times text-red-500 mr-2 mt-1"></i><span><?= htmlspecialchars($pf) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 mb-6 border-t-4 border-yellow-500">
                <h3 class="font-bold text-yellow-700 mb-3"><i class="fas fa-lightbulb mr-2"></i>Opportunités</h3>
                <ul class="space-y-2">
                    <?php foreach (($analyse_ia['opportunites'] ?? []) as $opp): ?>
                        <li class="flex"><i class="fas fa-star text-yellow-500 mr-2 mt-1"></i><span><?= htmlspecialchars($opp) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="bg-white rounded-2xl shadow p-6 mb-6">
                <h3 class="text-xl font-bold mb-4"><i class="fas fa-tasks text-purple-600 mr-2"></i>Actions prioritaires</h3>
                <?php 
                $priorite_colors = ['haute' => 'red', 'moyenne' => 'yellow', 'basse' => 'green'];
                foreach (($analyse_ia['actions_prioritaires'] ?? []) as $action): 
                    $color = $priorite_colors[strtolower($action['priorite'] ?? '')] ?? 'gray';
                ?>
                    <div class="border-l-4 border-<?= $color ?>-500 bg-<?= $color ?>-50 p-4 rounded mb-3">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold"><?= htmlspecialchars($action['titre']) ?></h4>
                            <span class="bg-<?= $color ?>-500 text-white px-2 py-1 rounded text-xs uppercase"><?= htmlspecialchars($action['priorite']) ?></span>
                        </div>
                        <p class="text-sm text-gray-700 mt-2"><?= htmlspecialchars($action['description']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
                <h3 class="text-xl font-bold mb-2"><i class="fas fa-crystal-ball mr-2"></i>Prévision mois prochain</h3>
                <p class="opacity-95"><?= htmlspecialchars($analyse_ia['prevision_mois_prochain'] ?? '') ?></p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const ctx = document.getElementById('chartVentes').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($ventes_mois, 'mois')) ?>,
                datasets: [{
                    label: 'CA (TND)',
                    data: <?= json_encode(array_column($ventes_mois, 'ca')) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true }
        });
    </script>
</body>
</html>
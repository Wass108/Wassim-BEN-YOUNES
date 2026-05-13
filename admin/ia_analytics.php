<?php
require_once '../db/db.php';
require_once '../ia/stock_prediction.php';

// Vérif admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../user/login.php');
    exit;
}

// ===== ANALYSE DES VENTES =====

// CA total sur 30 jours
$stmt = $pdo->query("
    SELECT COALESCE(SUM(cd.quantite * cd.prix), 0) as ca, COUNT(DISTINCT c.id) as nb_commandes
    FROM commandes c
    JOIN commande_details cd ON c.id = cd.commande_id
    WHERE c.date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stats30 = $stmt->fetch(PDO::FETCH_ASSOC);

// CA par jour (30 derniers jours)
$stmt = $pdo->query("
    SELECT DATE(c.date_commande) as jour, 
           COALESCE(SUM(cd.quantite * cd.prix), 0) as ca
    FROM commandes c
    JOIN commande_details cd ON c.id = cd.commande_id
    WHERE c.date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(c.date_commande)
    ORDER BY jour ASC
");
$ventes_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 5 produits
$stmt = $pdo->query("
    SELECT p.nom, SUM(cd.quantite) as qte, SUM(cd.quantite * cd.prix) as ca
    FROM commande_details cd
    JOIN produits p ON cd.produit_id = p.id
    JOIN commandes c ON cd.commande_id = c.id
    WHERE c.date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id
    ORDER BY qte DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CA par catégorie
$stmt = $pdo->query("
    SELECT cat.nom, SUM(cd.quantite * cd.prix) as ca
    FROM commande_details cd
    JOIN produits p ON cd.produit_id = p.id
    JOIN categories cat ON p.categorie_id = cat.id
    JOIN commandes c ON cd.commande_id = c.id
    WHERE c.date_commande >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY cat.id
    ORDER BY ca DESC
");
$ventes_cat = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== PRÉDICTION STOCK =====
$predictor = new StockPredictor($pdo);
$stock_analysis = $predictor->analyzeAllProducts();
$alerts = array_filter($stock_analysis, fn($p) => in_array($p['alert_level'], ['out', 'critical', 'warning']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics IA - SOUILEM LIGHTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">📊 Analytics & Prédictions IA</h1>
        <a href="dashboard.php" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">← Retour</a>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow border-l-4 border-yellow-500">
            <div class="text-sm text-gray-500">CA 30 jours</div>
            <div class="text-3xl font-bold text-gray-800"><?php echo number_format($stats30['ca'], 2, ',', ' '); ?> DT</div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow border-l-4 border-blue-500">
            <div class="text-sm text-gray-500">Commandes 30j</div>
            <div class="text-3xl font-bold text-gray-800"><?php echo $stats30['nb_commandes']; ?></div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow border-l-4 border-green-500">
            <div class="text-sm text-gray-500">Panier moyen</div>
            <div class="text-3xl font-bold text-gray-800">
                <?php echo $stats30['nb_commandes'] > 0 ? number_format($stats30['ca'] / $stats30['nb_commandes'], 2, ',', ' ') : 0; ?> DT
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow border-l-4 border-red-500">
            <div class="text-sm text-gray-500">⚠️ Alertes stock</div>
            <div class="text-3xl font-bold text-red-600"><?php echo count($alerts); ?></div>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow">
            <h2 class="text-xl font-bold mb-4">📈 Évolution du CA (30 jours)</h2>
            <canvas id="chartVentes"></canvas>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow">
            <h2 class="text-xl font-bold mb-4">🏆 Top 5 Produits</h2>
            <canvas id="chartTop"></canvas>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow lg:col-span-2">
            <h2 class="text-xl font-bold mb-4">🥧 Répartition par catégorie</h2>
            <div style="max-height:350px;"><canvas id="chartCat"></canvas></div>
        </div>
    </div>

    <!-- Prédictions Stock -->
    <div class="bg-white p-6 rounded-2xl shadow mb-8">
        <h2 class="text-2xl font-bold mb-4">🔮 Prédiction des Ruptures de Stock</h2>
        <p class="text-sm text-gray-500 mb-4">Basé sur les ventes des <?php echo STOCK_HISTORY_DAYS; ?> derniers jours.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Statut</th>
                        <th class="p-3 text-left">Produit</th>
                        <th class="p-3 text-left">Catégorie</th>
                        <th class="p-3 text-center">Stock</th>
                        <th class="p-3 text-center">Ventes/jour</th>
                        <th class="p-3 text-center">Jours avant rupture</th>
                        <th class="p-3 text-center">À recommander</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_analysis as $p): 
                        $color = $p['alert_color'];
                        $label = [
                            'out' => '🔴 RUPTURE',
                            'critical' => '🔴 CRITIQUE',
                            'warning' => '🟠 ATTENTION',
                            'ok' => '🟢 OK'
                        ][$p['alert_level']];
                    ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><span class="font-semibold"><?php echo $label; ?></span></td>
                        <td class="p-3 font-medium"><?php echo htmlspecialchars($p['nom']); ?></td>
                        <td class="p-3 text-gray-600"><?php echo htmlspecialchars($p['categorie_nom'] ?? '-'); ?></td>
                        <td class="p-3 text-center font-bold"><?php echo $p['current_stock']; ?></td>
                        <td class="p-3 text-center"><?php echo $p['daily_avg_sales']; ?></td>
                        <td class="p-3 text-center">
                            <?php echo $p['days_until_empty'] >= 9999 ? '∞' : $p['days_until_empty'] . ' j'; ?>
                        </td>
                        <td class="p-3 text-center">
                            <?php if ($p['recommend_restock'] > 0): ?>
                                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded font-bold">
                                    +<?php echo $p['recommend_restock']; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Graphique ventes par jour
new Chart(document.getElementById('chartVentes'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($ventes_jour, 'jour')); ?>,
        datasets: [{
            label: 'CA (DT)',
            data: <?php echo json_encode(array_column($ventes_jour, 'ca')); ?>,
            borderColor: '#D4AF37',
            backgroundColor: 'rgba(212, 175, 55, 0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

// Top produits
new Chart(document.getElementById('chartTop'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($top_products, 'nom')); ?>,
        datasets: [{
            label: 'Quantité vendue',
            data: <?php echo json_encode(array_column($top_products, 'qte')); ?>,
            backgroundColor: ['#D4AF37', '#FFD700', '#FFA500', '#FF8C00', '#B8860B']
        }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } } }
});

// Catégories
new Chart(document.getElementById('chartCat'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_column($ventes_cat, 'nom')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($ventes_cat, 'ca')); ?>,
            backgroundColor: ['#D4AF37', '#1a1a1a', '#FFD700', '#6b7280', '#FFA500']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>
</body>
</html>
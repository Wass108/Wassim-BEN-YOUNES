


<?php
require_once '../db/db.php';

// Sécurité : Restreindre l'accès aux administrateurs
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../user/login.php');
    exit;
}

// Statistiques pour le tableau de bord
 $stmt = $pdo->query("SELECT COUNT(*) FROM produits");
 $total_produits = $stmt->fetchColumn();

 $stmt = $pdo->query("SELECT COUNT(*) FROM users");
 $total_clients = $stmt->fetchColumn();

 $stmt = $pdo->query("SELECT SUM(quantite) FROM panier");
 $total_articles_panier = $stmt->fetchColumn() ?? 0;

// Récupérer les derniers produits ajoutés
 $stmt = $pdo->query("SELECT * FROM produits ORDER BY date_creation DESC LIMIT 5");
 $derniers_produits = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - SOUILEM LIGHTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#1a1a1a', secondary: '#D4AF37' } } }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">

    <!-- Header Admin -->
    <header class="bg-primary text-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="text-2xl">💡</span>
                <span class="font-bold text-xl text-secondary">SOUILEM Admin</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-300 hidden md:block">
                    Connecté en tant que : <strong class="text-white"><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Admin'); ?></strong>
                </span>
                <a href="../home.php" class="text-sm text-gray-300 hover:text-white transition">
                    <i class="fas fa-eye mr-1"></i> Voir le site
                </a>
                <a href="../user/logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg text-sm font-bold transition">
                    <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                </a>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar (Menu latéral) -->
        <nav class="w-64 bg-white h-screen shadow-md pt-6 hidden md:block">
            <ul class="space-y-2 px-4">
                <li>
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-yellow-50 text-secondary font-bold">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="produits.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-box"></i>
                        <span>Gestion Produits</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-users"></i>
                        <span>Clients</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition">
                        <i class="fas fa-truck"></i>
                        <span>Commandes</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Contenu Principal -->
        <main class="flex-1 p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Tableau de bord</h1>

            <!-- Cartes Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 flex items-center justify-between border-l-4 border-secondary">
                    <div>
                        <p class="text-gray-500 text-sm">Produits en ligne</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_produits; ?></p>
                    </div>
                    <div class="text-4xl text-secondary"><i class="fas fa-lightbulb"></i></div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6 flex items-center justify-between border-l-4 border-green-500">
                    <div class="text-4xl text-green-500"><i class="fas fa-users"></i></div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Clients inscrits</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_clients; ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-md p-6 flex items-center justify-between border-l-4 border-blue-500">
                    <div class="text-4xl text-blue-500"><i class="fas fa-shopping-cart"></i></div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Articles dans les paniers</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_articles_panier; ?></p>
                    </div>
                </div>
            </div>

            <!-- Derniers Produits -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">Derniers Produits Ajoutés</h2>
                    <a href="produits.php" class="text-secondary hover:underline text-sm font-bold">Voir tout &rarr;</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Nom</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Référence</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Prix</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Stock</th>
                                <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (count($derniers_produits) > 0): ?>
                                <?php foreach ($derniers_produits as $p): ?>
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo $p['id']; ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($p['nom']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($p['reference']); ?></td>
                                    <td class="px-6 py-4 text-sm font-bold text-secondary"><?php echo number_format($p['prix'], 2); ?> DT</td>
                                    <td class="px-6 py-4 text-sm <?php echo $p['stock'] < 5 ? 'text-red-600 font-bold' : 'text-gray-700'; ?>">
                                        <?php echo $p['stock']; ?>
                                        <?php if($p['stock'] < 5): ?>
                                            <span class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded ml-2">Stock bas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <?php if($p['nouveau']): ?><span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs mr-1">Nouveau</span><?php endif; ?>
                                        <?php if($p['promo']): ?><span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs">Promo</span><?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun produit trouvé.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
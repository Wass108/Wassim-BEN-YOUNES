<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
$total_produits = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes");
$total_commandes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'annulee'");
$chiffre_affaires = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("
    SELECT c.*, u.username, u.email 
    FROM commandes c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.date_commande DESC 
    LIMIT 10
");
$commandes_recentes = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    WHERE p.stock < 5 
    ORDER BY p.stock ASC
");
$produits_stock_bas = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT c.nom, COUNT(p.id) as nb_produits 
    FROM categories c 
    LEFT JOIN produits p ON c.id = p.categorie_id 
    GROUP BY c.id, c.nom
");
$stats_categories = $stmt->fetchAll();

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tableau de bord administrateur - Gestion complète de votre boutique en ligne">
    <meta name="robots" content="noindex, nofollow">
    <title>Tableau de bord Admin | Gestion E-commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#7c3aed',
                        accent: '#06b6d4',
                        dark: '#0f172a',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen font-sans antialiased">
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm backdrop-blur-lg bg-opacity-90">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="dashboard.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-crown text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Administration</h1>
                    </div>
                </a>
                <ul class="hidden lg:flex items-center space-x-1">
                    <li>
                        <a href="../home.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-home"></i>
                            <span>Site</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-primary transition-all duration-200 font-medium">
                            <i class="fas fa-chart-line"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-box"></i>
                            <span>Produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="ia_analytics.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-box"></i>
                            <span>Analytics IA</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-tags"></i>
                            <span>Catégories</span>
                        </a>
                    </li>
                    <li>
                        <a href="stock.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-warehouse"></i>
                            <span>Stock</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="factures.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-file-invoice"></i>
                            <span>Factures</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-users"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>
                </ul>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-3 px-4 py-2 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <a href="../user/logout.php" class="hidden lg:flex items-center space-x-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                    <button id="mobileMenuBtn" class="lg:hidden text-gray-700 focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <nav id="mobileMenu" class="hidden lg:hidden pb-4 border-t border-gray-200 mt-2 pt-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../home.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-home w-5"></i>
                            <span>Site</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 bg-blue-50 text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-chart-line w-5"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-box w-5"></i>
                            <span>Produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-tags w-5"></i>
                            <span>Catégories</span>
                        </a>
                    </li>
                    <li>
                        <a href="stock.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-warehouse w-5"></i>
                            <span>Stock</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart w-5"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="factures.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-file-invoice w-5"></i>
                            <span>Factures</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-users w-5"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>
                    <li class="pt-2 border-t border-gray-200">
                        <div class="flex items-center space-x-3 px-4 py-2 bg-gray-50 rounded-lg mb-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                    </li>
                    <li>
                        <a href="../user/logout.php" class="flex items-center justify-center space-x-2 px-5 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 font-medium shadow-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </nav>
    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }
    </script>
    <main class="container mx-auto px-6 lg:px-8 py-8 max-w-7xl">
        <div class="mb-10 animate-fadeIn">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-4xl font-bold text-gray-900">
                    Tableau de bord
                </h2>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Dernière mise à jour</p>
                    <p class="text-sm font-semibold text-gray-700"><?php echo date('d/m/Y à H:i'); ?></p>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover animate-fadeIn">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">+12%</span>
                </div>
                <div class="mb-3">
                    <p class="text-gray-500 text-sm font-medium mb-1">Utilisateurs</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $total_users; ?></h3>
                </div>
                <a href="users.php" class="text-sm text-blue-600 hover:text-blue-700 font-medium flex items-center group">
                    Gérer les utilisateurs
                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover animate-fadeIn" style="animation-delay: 0.1s;">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-box text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-green-600 bg-green-50 px-3 py-1 rounded-full">Actif</span>
                </div>
                <div class="mb-3">
                    <p class="text-gray-500 text-sm font-medium mb-1">Produits</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $total_produits; ?></h3>
                </div>
                <a href="products.php" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center group">
                    Gérer les produits
                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 card-hover animate-fadeIn" style="animation-delay: 0.2s;">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-shopping-cart text-white text-xl"></i>
                    </div>
                    <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">Total</span>
                </div>
                <div class="mb-3">
                    <p class="text-gray-500 text-sm font-medium mb-1">Commandes</p>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo $total_commandes; ?></h3>
                </div>
                <a href="commands.php" class="text-sm text-purple-600 hover:text-purple-700 font-medium flex items-center group">
                    Voir les commandes
                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg p-6 text-white card-hover animate-fadeIn" style="animation-delay: 0.3s;">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-euro-sign text-white text-xl"></i>
                    </div>
                    <i class="fas fa-chart-line text-2xl opacity-50"></i>
                </div>
                <div class="mb-3">
                    <p class="text-orange-100 text-sm font-medium mb-1">Chiffre d'affaires</p>
                    <h3 class="text-3xl font-bold"><?php echo number_format($chiffre_affaires, 0, ',', ' '); ?> €</h3>
                </div>
                <p class="text-sm text-orange-100 opacity-90">Total des ventes réalisées</p>
            </div>
        </div>
        <!-- ============ SECTION IA ============ -->
<div class="mb-10">
    <div class="flex items-center mb-6">
        <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center shadow-lg mr-4">
            <i class="fas fa-robot text-white text-xl"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-gray-900">Intelligence Artificielle</h3>
            <p class="text-sm text-gray-500">Analyses et recommandations automatiques</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="../ia/recommendations.php" class="group bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-magic text-white text-2xl"></i>
                </div>
                <i class="fas fa-arrow-right text-xl opacity-70 group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h4 class="text-xl font-bold mb-2">Recommandations IA</h4>
            <p class="text-sm opacity-90">Produits à mettre en avant, promotions et cross-selling</p>
        </a>

        <a href="../ia/analyse_ventes.php" class="group bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
                <i class="fas fa-arrow-right text-xl opacity-70 group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h4 class="text-xl font-bold mb-2">Analyse des ventes</h4>
            <p class="text-sm opacity-90">Tendances, opportunités et prévisions IA</p>
        </a>

        <a href="../ia/prediction_stock.php" class="group bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i class="fas fa-warehouse text-white text-2xl"></i>
                </div>
                <i class="fas fa-arrow-right text-xl opacity-70 group-hover:translate-x-2 transition-transform"></i>
            </div>
            <h4 class="text-xl font-bold mb-2">Prédiction Stock</h4>
            <p class="text-sm opacity-90">Anticipation des ruptures et réapprovisionnement</p>
        </a>
    </div>
</div>
<!-- ============ FIN SECTION IA ============ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeIn" style="animation-delay: 0.4s;">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-chart-pie text-primary mr-3"></i>
                    Produits par catégorie
                </h3>
                <div class="space-y-3">
                    <?php foreach ($stats_categories as $stat): ?>
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl hover:bg-blue-50 transition-all duration-200 group">
                            <span class="font-semibold text-gray-700 group-hover:text-primary transition-colors"><?php echo htmlspecialchars($stat['nom']); ?></span>
                            <span class="bg-gradient-to-r from-primary to-secondary text-white px-4 py-1.5 rounded-full text-sm font-bold shadow-sm">
                                <?php echo $stat['nb_produits']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeIn" style="animation-delay: 0.5s;">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-3"></i>
                    Actions rapides
                </h3>
                <div class="space-y-3">
                    <a href="products.php?action=add" class="block p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl hover:shadow-md transition-all duration-200 group border border-green-100">
                        <div class="flex items-center">
                            <i class="fas fa-plus-circle text-green-600 text-xl mr-3"></i>
                            <span class="font-semibold text-green-700 group-hover:text-green-800">Ajouter un produit</span>
                        </div>
                    </a>
                    <a href="commands.php?statut=en_attente" class="block p-4 bg-gradient-to-r from-yellow-50 to-amber-50 rounded-xl hover:shadow-md transition-all duration-200 group border border-yellow-100">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-yellow-600 text-xl mr-3"></i>
                            <span class="font-semibold text-yellow-700 group-hover:text-yellow-800">Commandes en attente</span>
                        </div>
                    </a>
                    <a href="users.php" class="block p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl hover:shadow-md transition-all duration-200 group border border-blue-100">
                        <div class="flex items-center">
                            <i class="fas fa-users-cog text-blue-600 text-xl mr-3"></i>
                            <span class="font-semibold text-blue-700 group-hover:text-blue-800">Gérer les utilisateurs</span>
                        </div>
                    </a>
                    <a href="categories.php" class="block p-4 bg-gradient-to-r from-purple-50 to-violet-50 rounded-xl hover:shadow-md transition-all duration-200 group border border-purple-100">
                        <div class="flex items-center">
                            <i class="fas fa-tags text-purple-600 text-xl mr-3"></i>
                            <span class="font-semibold text-purple-700 group-hover:text-purple-800">Gérer les catégories</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 animate-fadeIn" style="animation-delay: 0.6s;">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                    Alertes stock
                </h3>
                <?php if (count($produits_stock_bas) > 0): ?>
                    <div class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar">
                        <?php foreach ($produits_stock_bas as $produit): ?>
                            <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-lg hover:bg-red-100 transition-all duration-200">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($produit['nom']); ?></p>
                                        <p class="text-xs text-gray-600">
                                            <i class="fas fa-folder text-gray-400 mr-1"></i>
                                            <?php echo htmlspecialchars($produit['categorie_nom']); ?>
                                        </p>
                                    </div>
                                    <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold whitespace-nowrap ml-2">
                                        <i class="fas fa-box mr-1"></i><?php echo $produit['stock']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="p-6 bg-green-50 rounded-xl text-center border border-green-100">
                        <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                        <p class="text-green-700 font-semibold">Tous les stocks sont corrects</p>
                        <p class="text-green-600 text-sm mt-1">Aucune alerte de stock faible</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 animate-fadeIn" style="animation-delay: 0.7s;">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-4">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-receipt text-primary text-2xl mr-3"></i>
                    Commandes récentes
                </h3>
                <a href="commands.php" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-secondary text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-200 group">
                    <span>Voir toutes les commandes</span>
                    <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
            <?php if (count($commandes_recentes) > 0): ?>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <i class="fas fa-hashtag mr-2"></i>Commande
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <i class="fas fa-user mr-2"></i>Client
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2"></i>Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <i class="fas fa-euro-sign mr-2"></i>Montant
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <i class="fas fa-info-circle mr-2"></i>Statut
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($commandes_recentes as $commande): ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-900 bg-gray-100 px-3 py-1.5 rounded-lg">
                                        #<?php echo $commande['id']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                            <span class="text-white text-sm font-semibold">
                                                <?php echo strtoupper(substr($commande['username'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($commande['username']); ?></p>
                                            <p class="text-sm text-gray-500 flex items-center">
                                                <i class="fas fa-envelope text-xs mr-1"></i>
                                                <?php echo htmlspecialchars($commande['email']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">
                                            <i class="far fa-calendar-alt mr-1 text-gray-400"></i>
                                            <?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?>
                                        </p>
                                        <p class="text-gray-500">
                                            <i class="far fa-clock mr-1 text-gray-400"></i>
                                            <?php echo date('H:i', strtotime($commande['date_commande'])); ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-lg bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                                        <?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statut_config = [
                                        'en_attente' => [
                                            'text' => 'En attente',
                                            'icon' => 'fa-clock',
                                            'class' => 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                        ],
                                        'confirmee' => [
                                            'text' => 'Confirmée',
                                            'icon' => 'fa-check-circle',
                                            'class' => 'bg-green-100 text-green-800 border-green-200'
                                        ],
                                        'expedie' => [
                                            'text' => 'Expédiée',
                                            'icon' => 'fa-shipping-fast',
                                            'class' => 'bg-blue-100 text-blue-800 border-blue-200'
                                        ],
                                        'livree' => [
                                            'text' => 'Livrée',
                                            'icon' => 'fa-check-double',
                                            'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'
                                        ],
                                        'annulee' => [
                                            'text' => 'Annulée',
                                            'icon' => 'fa-times-circle',
                                            'class' => 'bg-red-100 text-red-800 border-red-200'
                                        ]
                                    ];
                                    $config = $statut_config[$commande['statut']] ?? [
                                        'text' => $commande['statut'],
                                        'icon' => 'fa-question-circle',
                                        'class' => 'bg-gray-100 text-gray-800 border-gray-200'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold border <?php echo $config['class']; ?>">
                                        <i class="fas <?php echo $config['icon']; ?> mr-1.5"></i>
                                        <?php echo $config['text']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <a href="commands.php?id=<?php echo $commande['id']; ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition-all duration-200 shadow-sm hover:shadow-md group">
                                        <i class="fas fa-eye mr-2"></i>
                                        <span>Détails</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-dashed border-blue-200 rounded-2xl px-6 py-16 text-center">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shopping-cart text-blue-500 text-4xl"></i>
                    </div>
                    <h4 class="text-2xl font-bold text-gray-800 mb-2">Aucune commande</h4>
                    <p class="text-gray-600 mb-6">Vous n'avez reçu aucune commande pour le moment</p>
                    <a href="products.php" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter des produits
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="container mx-auto px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-800">Administration</p>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-sm text-gray-600">
                        &copy; <?php echo date('Y'); ?>. Tous droits réservés.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.animate-fadeIn').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>
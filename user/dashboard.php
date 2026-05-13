<?php
require_once '../db/db.php';

if (!isLoggedIn()) {
    header('Location: user/login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute(params: [$_SESSION['user_id']]);
$user = $stmt->fetch();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_commandes = $stmt->fetchColumn();
$total_pages = ceil($total_commandes / $per_page);

$stmt = $pdo->prepare("SELECT * FROM commandes WHERE user_id = ? ORDER BY date_commande DESC LIMIT ? OFFSET ?");
$stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$commandes = $stmt->fetchAll();

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - SOUILEM LIGHTING</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B4513',
                        secondary: '#D2691E',
                        accent: '#F4A460',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <section class="relative min-h-screen bg-[url('../image/bg.png')] bg-cover bg-center flex flex-col">
        <div class="absolute inset-0 hero-overlay"></div>
        <header class="relative z-50 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="../home.php" class="flex items-center space-x-2 text-2xl font-bold hover:text-yellow-400 transition">
                    <span class="text-3xl">💡</span>
                    <span>SOUILEM LIGHTING</span>
                </a>
                <nav class="hidden md:block">
                    <ul class="flex space-x-8 items-center">
                        <li><a href="../home.php" class="hover:text-yellow-400 transition font-medium">Accueil</a></li>
                        <li><a href="../home.php#produits" class="hover:text-yellow-400 transition font-medium">Produits</a></li>
                        <li><a href="dashboard.php" class="hover:text-yellow-400 transition font-medium">Mon Compte</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="../admin/dashboard.php" class="bg-red-600 px-4 py-2 rounded-lg hover:bg-red-700 transition">Admin</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="hover:text-yellow-400 transition font-medium">Déconnexion</a></li>
                        <li>
                            <a href="panier.php" class="relative hover:text-yellow-400 transition">
                                <span class="text-2xl">🛒</span>
                                <?php if ($cart_count > 0): ?>
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold animate-pulse">
                                        <?php echo $cart_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="flex items-center space-x-4 md:hidden">
                    <a href="panier.php" class="relative hover:text-yellow-400 transition">
                        <span class="text-2xl">🛒</span>
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold animate-pulse">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <button id="mobileMenuBtn" class="text-white focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <nav id="mobileMenu" class="hidden md:hidden pb-4">
                <ul class="space-y-3">
                    <li><a href="../home.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Accueil</a></li>
                    <li><a href="../home.php#produits" class="block py-2 px-4 hover:bg-white/10 rounded transition">Produits</a></li>
                    <li><a href="dashboard.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Mon Compte</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="../admin/dashboard.php" class="block py-2 px-4 bg-red-600 hover:bg-red-700 rounded transition">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Déconnexion</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="relative z-40 flex-grow container mx-auto px-4 py-2 pb-16 max-h-[calc(100vh-140px)] overflow-y-auto no-scrollbar">
        <div class="text-center mb-3 animate-fadeInUp">
            <div class="inline-block p-1.5 bg-gradient-to-br from-primary to-amber-700 rounded-full mb-1.5">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h2 class="text-xl md:text-2xl font-bold text-white text-shadow-lg mb-0.5">Mon Compte</h2>
            <p class="text-white/90 text-xs">Gérez votre profil et vos commandes</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 mb-3">
            <div class="profile-card rounded-2xl shadow-lg p-5 animate-slideInLeft delay-100">
                <div class="flex items-center justify-center w-14 h-14 mx-auto mb-3 bg-gradient-to-br from-primary to-amber-700 rounded-full">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-primary mb-3 text-center">Mon Profil</h3>
                <div class="space-y-2 text-sm text-gray-700">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="font-semibold">Pseudo:</span>&nbsp;<span class="truncate"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="font-semibold">Email:</span>&nbsp;<span class="truncate"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <?php if ($user['nom']): ?>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="font-semibold">Nom:</span>&nbsp;<?php echo htmlspecialchars($user['nom']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['prenom']): ?>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="font-semibold">Prénom:</span>&nbsp;<?php echo htmlspecialchars($user['prenom']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card rounded-2xl shadow-lg p-5 text-center animate-slideInUp delay-200">
                <div class="flex items-center justify-center w-14 h-14 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-primary mb-3">Mes Commandes</h3>
                <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo $total_commandes; ?></div>
                <p class="text-sm text-gray-700">Commande(s) passée(s)</p>
            </div>
            <div class="stat-card rounded-2xl shadow-lg p-5 text-center animate-slideInRight delay-300">
                <div class="flex items-center justify-center w-14 h-14 mx-auto mb-3 bg-gradient-to-br from-orange-500 to-amber-600 rounded-full">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-primary mb-3">Mon Panier</h3>
                <div class="text-4xl font-bold text-orange-600 mb-2"><?php echo $cart_count; ?></div>
                <p class="text-sm text-gray-700 mb-3">Article(s) dans le panier</p>
                <a href="panier.php" class="inline-block bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold px-5 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg btn-hover-effect shine-effect text-sm">
                    <span class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        Voir
                    </span>
                </a>
            </div>
        </div>
        <div class="orders-card rounded-3xl shadow-lg p-4 md:p-5 animate-slideInUp delay-400">
            <h3 class="text-xl font-bold text-primary mb-3 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                Historique des Commandes
            </h3>
            <div id="commandes-container">
            <?php if (count($commandes) > 0): ?>
                <div class="overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gradient-to-r from-primary to-amber-800 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left rounded-tl-lg">N° Commande</th>
                                <th class="px-3 py-2 text-left">Date</th>
                                <th class="px-3 py-2 text-left">Montant</th>
                                <th class="px-3 py-2 text-left rounded-tr-lg">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($commandes as $commande): ?>
                            <tr class="table-row-hover">
                                <td class="px-3 py-2 font-semibold text-gray-900">#<?php echo $commande['id']; ?></td>
                                <td class="px-3 py-2 text-gray-700"><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></td>
                                <td class="px-3 py-2 font-bold text-secondary"><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</td>
                                <td class="px-3 py-2">
                                    <?php
                                    $statut_config = [
                                        'en_attente' => ['text' => '⏳ En attente', 'class' => 'bg-yellow-100 text-yellow-800'],
                                        'confirmee' => ['text' => '✅ Confirmée', 'class' => 'bg-green-100 text-green-800'],
                                        'en_livraison' => ['text' => '📦 En livraison', 'class' => 'bg-blue-100 text-blue-800'],
                                        'livree' => ['text' => '✓ Livrée', 'class' => 'bg-green-200 text-green-900'],
                                        'annulee' => ['text' => '❌ Annulée', 'class' => 'bg-red-100 text-red-800']
                                    ];
                                    $config = $statut_config[$commande['statut']] ?? ['text' => $commande['statut'], 'class' => 'bg-gray-100 text-gray-800'];
                                    ?>
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?php echo $config['class']; ?>">
                                        <?php echo $config['text']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <button onclick="loadCommandes(<?php echo $page - 1; ?>)" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-amber-900 transition text-xs font-semibold">
                                ← Précédent
                            </button>
                        <?php endif; ?>

                        <div class="flex space-x-1">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <button onclick="loadCommandes(<?php echo $i; ?>)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>
                        </div>

                        <?php if ($page < $total_pages): ?>
                            <button onclick="loadCommandes(<?php echo $page + 1; ?>)" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-amber-900 transition text-xs font-semibold">
                                Suivant →
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2 text-center text-xs text-gray-600">
                        Page <?php echo $page; ?> sur <?php echo $total_pages; ?> (<?php echo $total_commandes; ?> commande<?php echo $total_commandes > 1 ? 's' : ''; ?> au total)
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="glass-effect border border-blue-200 text-blue-800 px-6 py-5 rounded-2xl text-center animate-fadeInUp">
                    <div class="flex items-center justify-center mb-3">
                        <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <p class="mb-3">
                        Vous n'avez pas encore passé de commande.
                    </p>
                    <a href="../home.php#produits" class="inline-block bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold px-6 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg btn-hover-effect shine-effect text-sm">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Découvrir nos produits
                        </span>
                    </a>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <footer class="fixed bottom-0 left-0 right-0 z-30 bg-black/30 backdrop-blur-sm text-white py-3">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="text-white/80 text-sm">
                    &copy; <?php echo date('Y'); ?> SOUILEM LIGHTING - Tous droits réservés
                </p>
            </div>
        </div>
    </footer>
    </section>
<script src="js/dashboard.js"></script>
</body>
</html>
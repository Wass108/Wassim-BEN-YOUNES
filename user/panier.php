<?php
require_once '../db/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, pr.nom, pr.prix, pr.marque, pr.type_eclairage, pr.image 
    FROM panier p 
    JOIN produits pr ON p.produit_id = pr.id 
    WHERE p.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$panier_items = $stmt->fetchAll();

$total = 0;
foreach ($panier_items as $item) {
    $total += $item['prix'] * $item['quantite'];
}

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - SOUILEM LIGHTING</title>
    <link rel="stylesheet" href="css/panier.css">
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
                    <li><a href="dashboard.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Mon Compte</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="../admin/dashboard.php" class="block py-2 px-4 bg-red-600 hover:bg-red-700 rounded transition">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Déconnexion</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="relative z-40 flex-grow container mx-auto px-4 py-6 pb-20">
        <div class="text-center mb-6 animate-fadeInUp">
            <div class="inline-block p-3 bg-gradient-to-br from-primary to-amber-700 rounded-full mb-3">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-white text-shadow-lg mb-1">Mon Panier</h2>
            <p class="text-white/90">Finalisez votre commande</p>
        </div>
        <?php if (count($panier_items) > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 space-y-3">
                    <?php
                    $product_icons = ['💡', '🔦', '🏮', '✨', '🌟', '🪔', '💎', '🔆', '🎇', '🎆'];
                    foreach ($panier_items as $index => $item):
                    ?>
                    <div class="cart-item-card rounded-2xl p-4 animate-slideInLeft delay-<?php echo min($index * 100, 500); ?>">
                        <div class="flex flex-col md:flex-row gap-4 items-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-amber-100 to-orange-100 rounded-lg flex items-center justify-center text-4xl flex-shrink-0">
                                <?php if (!empty($item['image']) && file_exists('../' . $item['image'])): ?>
                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>" class="w-full h-full object-cover rounded-lg">
                                <?php else: ?>
                                    <?php echo $product_icons[$item['produit_id'] % count($product_icons)]; ?>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="text-xl font-bold text-primary mb-1"><?php echo htmlspecialchars($item['nom']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?php 
                                        $details = [];
                                        if (!empty($item['marque'])) $details[] = htmlspecialchars($item['marque']);
                                        if (!empty($item['type_eclairage'])) $details[] = htmlspecialchars($item['type_eclairage']);
                                        echo implode(' • ', $details);
                                        if (!empty($details)) echo ' • ';
                                    ?>
                                    <span class="text-secondary font-semibold"><?php echo number_format($item['prix'], 2, ',', ' '); ?> €</span> l'unité
                                </p>
                            </div>
                            <div class="flex flex-col items-center gap-3">
                                <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                                    <form method="POST" action="update_panier.php" class="inline">
                                        <input type="hidden" name="panier_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="decrease">
                                        <button type="submit" class="quantity-btn w-8 h-8 bg-primary text-white rounded-lg hover:bg-amber-900 font-bold text-lg">
                                            -
                                        </button>
                                    </form>
                                    <span class="font-bold text-lg min-w-[2.5rem] text-center"><?php echo $item['quantite']; ?></span>
                                    <form method="POST" action="update_panier.php" class="inline">
                                        <input type="hidden" name="panier_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="action" value="increase">
                                        <button type="submit" class="quantity-btn w-8 h-8 bg-primary text-white rounded-lg hover:bg-amber-900 font-bold text-lg">
                                            +
                                        </button>
                                    </form>
                                </div>
                                <div class="font-bold text-xl text-secondary">
                                    <?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ' '); ?> €
                                </div>

                                <form method="POST" action="update_panier.php" class="inline">
                                    <input type="hidden" name="panier_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" onclick="return confirm('Supprimer cet article du panier ?')" class="text-red-500 hover:text-red-700 transition text-xs font-semibold">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="lg:col-span-1">
                    <div class="summary-card rounded-3xl p-6 sticky top-24 animate-slideInRight delay-200">
                        <h3 class="text-xl font-bold text-primary mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Récapitulatif
                        </h3>

                        <div class="space-y-3 mb-4 text-sm">
                            <div class="flex justify-between text-gray-700">
                                <span>Sous-total :</span>
                                <span class="font-semibold"><?php echo number_format($total, 2, ',', ' '); ?> €</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>Livraison :</span>
                                <span class="font-semibold text-green-600">Gratuite 🎉</span>
                            </div>
                            <div class="border-t-2 border-primary pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-xl font-bold text-primary">Total :</span>
                                    <span class="text-2xl font-bold text-secondary"><?php echo number_format($total, 2, ',', ' '); ?> €</span>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="commander.php">
                            <button type="submit" class="w-full bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold py-3 rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl mb-3 btn-hover-effect shine-effect">
                                <span class="flex items-center justify-center text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Passer la commande
                                </span>
                            </button>
                        </form>
                        <a href="../home.php#produits" class="block w-full bg-white border-2 border-primary hover:bg-primary text-primary hover:text-white font-semibold py-2 rounded-xl text-center transition-all duration-300 transform hover:scale-105 shadow-md text-sm">
                            <span class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Continuer mes achats
                            </span>
                        </a>
                        <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-sm text-green-800 text-center">
                                <span class="font-semibold">✓ Paiement sécurisé</span><br>
                                <span class="text-xs">Vos données sont protégées</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="max-w-2xl mx-auto">
                <div class="glass-effect rounded-3xl p-8 text-center animate-scaleIn">
                    <div class="text-6xl mb-4 animate-fadeInUp">🛒</div>
                    <h3 class="text-2xl font-bold text-primary mb-3 animate-fadeInUp delay-200">Votre panier est vide</h3>
                    <p class="text-gray-600 mb-6 animate-fadeInUp delay-300">Découvrez notre sélection de luminaires et d'éclairage de qualité !</p>
                    <a href="../home.php#produits" class="inline-block bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold px-6 py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl btn-hover-effect shine-effect animate-fadeInUp delay-400">
                        <span class="flex items-center justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Découvrir nos produits
                        </span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
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
<script src="js/panier.js"></script>
</body>
</html>
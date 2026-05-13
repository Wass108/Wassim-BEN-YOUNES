<?php
require_once '../db/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, pr.nom, pr.prix, pr.stock 
    FROM panier p 
    JOIN produits pr ON p.produit_id = pr.id 
    WHERE p.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$panier_items = $stmt->fetchAll();

if (empty($panier_items)) {
    header('Location: panier.php');
    exit;
}

$erreur_stock = false;
foreach ($panier_items as $item) {
    if ($item['quantite'] > $item['stock']) {
        $erreur_stock = true;
        break;
    }
}

$total = 0;
foreach ($panier_items as $item) {
    $total += $item['prix'] * $item['quantite'];
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erreur_stock) {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO commandes (user_id, montant_total, statut) VALUES (?, ?, 'en_attente')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $commande_id = $pdo->lastInsertId();
        
        foreach ($panier_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
            $stmt->execute([$commande_id, $item['produit_id'], $item['quantite'], $item['prix']]);
            
            $stmt = $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantite'], $item['produit_id']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        $success = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Une erreur est survenue lors de la commande : " . $e->getMessage();
    }
}

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commander - SOUILEM LIGHTING</title>
    <link rel="stylesheet" href="css/commander.css">
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
 <section class="relative min-h-screen bg-[url('../image/showroom_bg.jpg')] bg-cover bg-center flex flex-col">
                <div class="absolute inset-0 bg-black/700 bg-gradient-to-b from-black/50 to-black/90"></div>        <div class="absolute inset-0 hero-overlay"></div>
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
    <div class="relative z-40 flex-grow container mx-auto px-4 py-4 mb-16">
        <div class="text-center mb-4 animate-fadeInUp">
            <div class="inline-block p-2 bg-gradient-to-br from-primary to-amber-700 rounded-full mb-2">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl md:text-3xl font-bold text-white text-shadow-lg mb-1">Finaliser la commande</h2>
            <p class="text-white/90 text-sm">Vérifiez votre commande avant validation</p>
        </div>
        <?php if ($success): ?>
            <div class="max-w-lg mx-auto">
                <div class="glass-effect rounded-3xl shadow-2xl p-6 md:p-8 text-center success-animation">
                    <div class="text-5xl md:text-6xl mb-4 animate-scaleIn">✅</div>
                    <h2 class="text-xl md:text-2xl font-bold text-green-600 mb-3">Commande validée !</h2>
                    <p class="text-gray-700 text-sm md:text-base mb-2">Votre commande a été enregistrée avec succès.</p>
                    <p class="text-gray-600 text-sm mb-1">Numéro de commande : <span class="font-bold text-primary">#<?php echo $commande_id; ?></span></p>
                    <p class="text-gray-600 mb-6 text-sm">Montant total : <span class="font-bold text-secondary text-lg md:text-xl"><?php echo number_format($total, 2, ',', ' '); ?> DT</span></p>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6 animate-fadeInUp delay-200">
                        <p class="text-amber-800 text-sm">
                            <span class="font-semibold">📧 Un email de confirmation vous a été envoyé.</span><br>
                            <span class="text-xs">Vous recevrez votre colis sous 24-48h.</span>
                        </p>
                    </div>
                    <div class="space-y-2 md:space-y-3">
                        <a href="dashboard.php" class="block bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold px-6 py-2.5 md:py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl btn-hover-effect shine-effect text-sm md:text-base">
                            <span class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Voir mes commandes
                            </span>
                        </a>
                        <a href="../home.php#produits" class="block bg-white hover:bg-primary text-primary hover:text-white font-semibold px-6 py-2.5 md:py-3 rounded-xl transition-all duration-300 transform hover:scale-105 border-2 border-primary shadow-md text-sm md:text-base">
                            <span class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Continuer mes achats
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="max-w-2xl mx-auto mb-6 animate-fadeInUp">
                    <div class="glass-effect bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl">
                        ❌ <?php echo $error; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($erreur_stock): ?>
                <div class="max-w-2xl mx-auto mb-6 animate-fadeInUp delay-100">
                    <div class="glass-effect bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-2xl">
                        ⚠️ Certains articles de votre panier ne sont plus disponibles en quantité suffisante.
                        <a href="panier.php" class="underline font-semibold hover:text-red-900">Retour au panier</a>
                    </div>
                </div>
            <?php endif; ?>
            <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="lg:col-span-2">
                    <div class="order-summary-card rounded-3xl shadow-lg p-6 md:p-8 animate-slideInLeft delay-200">
                        <h3 class="text-xl md:text-2xl font-bold text-primary mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Récapitulatif de la commande
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($panier_items as $item): ?>
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-200 pb-4 transition hover:bg-amber-50/50 p-2 rounded-lg">
                                    <div class="mb-2 md:mb-0">
                                        <p class="font-semibold text-gray-800 text-base md:text-lg"><?php echo htmlspecialchars($item['nom']); ?></p>
                                        <p class="text-sm text-gray-600">Quantité : <span class="font-semibold"><?php echo $item['quantite']; ?></span> × <?php echo number_format($item['prix'], 2, ',', ' '); ?> DT</p>
                                    </div>
                                    <p class="font-bold text-secondary text-lg md:text-xl"><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ' '); ?> DT</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1">
                    <div class="total-card rounded-3xl shadow-lg p-6 md:p-8 sticky top-24 animate-slideInRight delay-300">
                        <h3 class="text-xl md:text-2xl font-bold text-primary mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Total
                        </h3>
                        <div class="space-y-3 md:space-y-4 mb-6">
                            <div class="flex justify-between text-gray-700 text-sm md:text-base">
                                <span>Sous-total :</span>
                                <span class="font-semibold"><?php echo number_format($total, 2, ',', ' '); ?> DT</span>
                            </div>
                            <div class="flex justify-between text-gray-700 text-sm md:text-base">
                                <span>Livraison :</span>
                                <span class="font-semibold text-green-600">Gratuite 🎉</span>
                            </div>
                            <div class="border-t-2 border-primary pt-3 md:pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg md:text-xl font-bold text-primary">Total :</span>
                                    <span class="text-2xl md:text-3xl font-bold text-secondary"><?php echo number_format($total, 2, ',', ' '); ?> DT</span>
                                </div>
                            </div>
                        </div>
                        <?php if (!$erreur_stock): ?>
                        <form method="POST">
                            <button type="submit" class="w-full bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold py-3 md:py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl text-base md:text-lg mb-3 btn-hover-effect shine-effect">
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Confirmer la commande
                                </span>
                            </button>
                        </form>
                        <?php endif; ?>
                        <a href="panier.php" class="block w-full bg-white hover:bg-primary text-primary hover:text-white font-semibold py-2 md:py-3 rounded-xl text-center transition-all duration-300 transform hover:scale-105 border-2 border-primary shadow-md text-sm md:text-base">
                            <span class="flex items-center justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Retour au panier
                            </span>
                        </a>
                        <div class="mt-4 md:mt-6 p-3 md:p-4 bg-green-50 rounded-lg border border-green-200 animate-fadeInUp delay-400">
                            <p class="text-sm text-green-800 text-center">
                                <span class="font-semibold">🔒 Paiement sécurisé</span><br>
                                <span class="text-xs">Vos données sont protégées</span>
                            </p>
                        </div>
                    </div>
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
<script src="js/commander.js"></script>
</body>
</html>


<?php
//categorie.php
require_once 'db/db.php';

// 1. Récupérer l'ID de la catégorie dans l'URL (ex: categorie.php?id=1)
 $id_categorie = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si l'ID est invalide, on redirige vers l'accueil
if ($id_categorie <= 0) {
    header('Location: home.php');
    exit;
}

// 2. Récupérer les infos de la catégorie (pour le titre)
 $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
 $stmt->execute([$id_categorie]);
 $categorie = $stmt->fetch();

// Si la catégorie n'existe pas, on redirige
if (!$categorie) {
    header('Location: home.php');
    exit;
}

// 3. Récupérer TOUS les produits de cette catégorie
 $stmt = $pdo->prepare("SELECT * FROM produits WHERE categorie_id = ? ORDER BY date_creation DESC");
 $stmt->execute([$id_categorie]);
 $produits = $stmt->fetchAll();

// Compteur panier
 $cart_count = getCartCount($pdo);
?>
    <?php include 'ia/chatbot_widget.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($categorie['nom']) ?> - SOUILEM LIGHTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#1a1a1a', secondary: '#D4AF37', light: '#F5F5F5' } } }
        }
    </script>
</head>
<body class="bg-primary text-light">

    <!-- Header (Copié de home.php pour la cohérence) -->
    <header class="bg-black/90 text-white py-4 sticky top-0 z-50 border-b border-secondary/20">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="home.php" class="flex items-center space-x-2 text-2xl font-bold hover:text-secondary transition">
                <span class="text-3xl">💡</span>
                <span class="text-secondary tracking-wider">SOUILEM LIGHTING</span>
            </a>
            <nav class="flex items-center space-x-6">
                <a href="home.php" class="hover:text-secondary transition">Accueil</a>
                <a href="#produits" class="hover:text-secondary transition">Catalogue</a>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo isLoggedIn() ? 'user/dashboard.php' : 'user/login.php'; ?>" class="hover:text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </a>
                    <a href="<?php echo isLoggedIn() ? 'user/panier.php' : 'user/login.php'; ?>" class="relative hover:text-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        <?php if ($cart_count > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-secondary text-black text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Contenu Principal -->
    <main class="container mx-auto px-4 py-12">
        <!-- Fil d'Ariane -->
        <div class="text-sm text-gray-500 mb-6">
            <a href="home.php" class="hover:text-secondary">Accueil</a> 
            <span class="mx-2">/</span> 
            <span class="text-secondary"><?= htmlspecialchars($categorie['nom']) ?></span>
        </div>

        <div class="flex justify-between items-center mb-12 border-b border-zinc-700 pb-6">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2 uppercase tracking-wider">
                    <?= htmlspecialchars($categorie['nom']) ?>
                </h1>
                <p class="text-gray-400"><?= htmlspecialchars($categorie['description']) ?></p>
            </div>
            <div class="text-secondary font-bold text-lg">
                <?= count($produits) ?> produit(s)
            </div>
        </div>

        <?php if (count($produits) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php 
                // Icônes de secours
                $product_icons = ['💡', '🏮', '🔦', '✨', '🔌', '🕯️']; 
                foreach ($produits as $index => $produit): 
                ?>
                <div class="bg-zinc-900 rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-2 duration-300 border border-zinc-800 hover:border-secondary group relative">
                    
                    <!-- Image / Icône -->
                    <div class="relative h-48 bg-zinc-800 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($produit['image'])): ?>
                            <img src="uploads/<?php echo $produit['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="">
                        <?php else: ?>
                            <div class="text-7xl text-gray-500 group-hover:scale-110 transition duration-500">
                                <?php echo $product_icons[$index % count($product_icons)]; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badges -->
                        <?php if (isset($produit['nouveau']) && $produit['nouveau']): ?>
                            <span class="absolute top-3 right-3 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">Nouveau</span>
                        <?php endif; ?>
                        <?php if (isset($produit['promo']) && $produit['promo']): ?>
                            <span class="absolute top-3 right-3 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">Promo</span>
                        <?php endif; ?>
                    </div>

                    <div class="p-6">
                        <h3 class="text-lg font-bold text-white mb-2 truncate"><?= htmlspecialchars($produit['nom']) ?></h3>
                        <p class="text-xs text-gray-500 mb-3">Réf: <?= htmlspecialchars($produit['reference']) ?></p>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-secondary"><?= number_format($produit['prix'], 2) ?> DT</span>
                            <?php if ($produit['stock'] > 0): ?>
                                <span class="text-xs text-green-500 font-semibold">En stock</span>
                            <?php else: ?>
                                <span class="text-xs text-red-500 font-semibold">Rupture</span>
                            <?php endif; ?>
                        </div>

                        <!-- Bouton Ajouter (Identique à home.php) -->
                        <?php if (isLoggedIn()): ?>
                            <?php if ($produit['stock'] > 0): ?>
                                <form method="POST" action="user/update_panier.php" class="mt-4">
                                    <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">
                                    <button type="submit" class="w-full bg-secondary hover:bg-yellow-500 text-black font-bold py-2 rounded-lg text-sm transition">
                                        Ajouter au panier
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="w-full mt-4 bg-gray-600 text-gray-400 font-bold py-2 rounded-lg text-sm cursor-not-allowed">
                                    Rupture
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="user/login.php" class="block w-full mt-4 text-center bg-zinc-700 hover:bg-zinc-600 text-white font-bold py-2 rounded-lg text-sm transition">
                                Se connecter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 text-gray-500 bg-zinc-900 rounded-xl border border-zinc-800">
                <i class="fas fa-box-open text-5xl mb-4 text-gray-600"></i>
                <p class="text-xl">Aucun produit dans cette catégorie pour le moment.</p>
                <a href="home.php" class="mt-4 inline-block text-secondary hover:underline">Voir tous nos produits</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Simple -->
    <footer class="bg-black py-8 mt-16 border-t border-zinc-800">
        <div class="container mx-auto px-4 text-center text-gray-600 text-sm">
            &copy; <?= date('Y') ?> SOUILEM LIGHTING. Tous droits réservés.
        </div>
    </footer>

</body>
</html>
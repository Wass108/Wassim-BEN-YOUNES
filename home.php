




<?php
require_once 'db/db.php';

// Récupération des produits avec le nom de la catégorie
 $stmt = $pdo->query("SELECT p.*, c.nom as categorie_nom FROM produits p 
                     LEFT JOIN categories c ON p.categorie_id = c.id 
                     ORDER BY p.date_creation DESC");
 $tous_produits = $stmt->fetchAll();

// Récupération des catégories
 $stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
 $categories = $stmt->fetchAll();

 $cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/home.css">
    <title>SOUILEM LIGHTING - Éclairage & Luminaires</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Palette de couleurs pour l'éclairage (Or/Ambré/Sombre)
                        primary: '#1a1a1a',      // Noir profond pour le fond
                        secondary: '#D4AF37',    // Or pour les accents
                        accent: '#FFD700',       // Or brillant
                        light: '#F5F5F5',        // Blanc cassé pour le texte
                    }
                }
            }
        }
    </script>
    <style>
        /* Effet de lueur pour les éléments actifs */
        .glow-effect:hover {
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
        }
        /* Animation pour le bouton IA */
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(212, 175, 55, 0); }
            100% { box-shadow: 0 0 0 0 rgba(212, 175, 55, 0); }
        }
        .ia-btn {
            animation: pulse-glow 2s infinite;
        }
    </style>
</head>
<body class="bg-primary text-light">
    <?php include 'ia/chatbot_widget.php'; ?>

    <!-- Hero Section avec fond sombre pour contraste -->
    <section class="relative h-screen bg-[url('image/showroom_bg.jpg')] bg-cover bg-center">
        <div class="absolute inset-0 bg-black/70 bg-gradient-to-b from-black/50 to-black/90"></div>
        
        <header class="relative z-50 text-white">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center py-4 border-b border-yellow-600/30">
                    <a href="home.php" class="flex items-center space-x-2 text-2xl font-bold hover:text-secondary transition">
                        <span class="text-3xl">💡</span>
                        <span class="text-secondary tracking-wider">SOUILEM LIGHTING</span>
                    </a>
                    
                    <nav class="hidden md:block">
                        <ul class="flex space-x-8 items-center">
                            <li><a href="home.php" class="hover:text-secondary transition font-medium">Accueil</a></li>
                            <li><a href="#produits" class="hover:text-secondary transition font-medium">Catalogue</a></li>
                            <li><a href="#categories" class="hover:text-secondary transition font-medium">Catégories</a></li>
                            <li><a href="about.php" class="hover:text-secondary transition font-medium">Notre Showroom</a></li>
                            <li><a href="contact.php" class="hover:text-secondary transition font-medium">Contact</a></li>
                            
                            <li>
                                <a href="<?php echo isLoggedIn() ? 'user/dashboard.php' : 'user/login.php'; ?>" class="hover:text-secondary transition" title="Mon Compte">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </a>
                            </li>
                            
                            <?php if (isLoggedIn() && isAdmin()): ?>
                                <li><a href="admin/dashboard.php" class="bg-secondary text-black px-4 py-2 rounded-lg hover:bg-yellow-500 transition font-bold">Admin</a></li>
                            <?php endif; ?>
                            
                            <li>
                                <a href="<?php echo isLoggedIn() ? 'user/panier.php' : 'user/login.php'; ?>" class="relative hover:text-secondary transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <?php if ($cart_count > 0): ?>
                                        <span class="absolute -top-2 -right-2 bg-secondary text-black text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                            <?php echo $cart_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    
                    <!-- Mobile Menu Button -->
                    <div class="flex items-center space-x-4 md:hidden">
                        <button id="mobileMenuBtn" class="text-white focus:outline-none">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Navigation -->
                <nav id="mobileMenu" class="hidden md:hidden pb-4">
                    <ul class="space-y-3 pt-4">
                        <li><a href="home.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Accueil</a></li>
                        <li><a href="#produits" class="block py-2 px-4 hover:bg-white/10 rounded transition">Produits</a></li>
                        <li><a href="#categories" class="block py-2 px-4 hover:bg-white/10 rounded transition">Catégories</a></li>
                        <li><a href="about.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Showroom</a></li>
                        <li><a href="contact.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Contact</a></li>
                        <?php if (isLoggedIn() && isAdmin()): ?>
                            <li><a href="admin/dashboard.php" class="block py-2 px-4 bg-secondary text-black rounded transition">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- Hero Content -->
        <div class="relative z-10 h-full flex items-center justify-center px-4 -mt-20">
            <div class="text-center max-w-5xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-extrabold text-white mb-6 leading-tight uppercase tracking-wider">
                    Illuminez votre <br/>
                    <span class="text-secondary">Univers</span>
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 mb-10 font-light italic">
                    Lustres, Spots, LED & Solutions d'éclairage
                </p>
                <a href="#produits" class="inline-block px-12 py-5 bg-secondary hover:bg-yellow-500 text-gray-900 font-bold text-lg rounded-full transition-all duration-300 transform hover:scale-105 shadow-2xl glow-effect">
                    Découvrir le Catalogue
                </a>
                
                <div class="mt-20 flex flex-wrap justify-center gap-16 text-white">
                    <div>
                        <div class="text-4xl font-bold text-secondary"><?php echo count($tous_produits); ?>+</div>
                        <div class="text-sm uppercase tracking-wide mt-1 text-gray-400">Références</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-secondary">Showroom</div>
                        <div class="text-sm uppercase tracking-wide mt-1 text-gray-400">Physique</div>
                    </div>
                    <div>
                        <div class="text-4xl font-bold text-secondary">Livraison</div>
                        <div class="text-sm uppercase tracking-wide mt-1 text-gray-400">Rapide</div>
                    </div>
                </div>
            </div>
        </div>
        
        <a href="#categories" class="absolute bottom-10 left-1/2 transform -translate-x-1/2 text-white animate-bounce z-20">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </a>
    </section>

    <!-- Section Catégories -->
    <section class="py-20 bg-zinc-900" id="categories">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-12 text-white">
                Nos Spécialités
                <div class="w-24 h-1 bg-secondary mx-auto mt-4 rounded"></div>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php 
                // Icônes pour les catégories d'éclairage
                $category_icons = ['🏮', '💡', '✨', '🔌']; 
                // Couleurs de fond légères pour les cartes
                $category_colors = [
                    'from-zinc-800 to-zinc-700 hover:from-zinc-700 hover:to-zinc-600',
                    'from-zinc-800 to-zinc-700 hover:from-zinc-700 hover:to-zinc-600',
                    'from-zinc-800 to-zinc-700 hover:from-zinc-700 hover:to-zinc-600',
                    'from-zinc-800 to-zinc-700 hover:from-zinc-700 hover:to-zinc-600'
                ];
                
                foreach ($categories as $index => $categorie): 
                ?>
                <a href="categorie.php?id=<?php echo $categorie['id']; ?>" class="block bg-gradient-to-br <?php echo $category_colors[$index % 4]; ?> p-8 rounded-2xl shadow-lg border border-zinc-600 hover:border-secondary transition cursor-pointer transform hover:-translate-y-2 duration-300 group">
                    <div class="text-6xl mb-4 text-center transform group-hover:scale-110 transition"><?php echo $category_icons[$index % 4]; ?></div>
                    <h3 class="text-2xl font-bold text-secondary mb-2 text-center uppercase tracking-wide"><?php echo htmlspecialchars($categorie['nom']); ?></h3>
                    <p class="text-gray-400 text-center text-sm"><?php echo htmlspecialchars($categorie['description']); ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section Produits -->
    <section class="py-20 bg-gray-100" id="produits">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-12 text-primary">
                Nos Produits Phares
                <div class="w-24 h-1 bg-secondary mx-auto mt-4 rounded"></div>
            </h2>
            
            <!-- Filtres (Optionnel selon votre JS) -->
            <div class="flex flex-wrap justify-center gap-4 mb-10">
                <button class="px-6 py-2 bg-primary text-white rounded-full font-medium hover:bg-secondary hover:text-black transition">Tous</button>
                <button class="px-6 py-2 bg-white text-primary rounded-full font-medium border border-primary hover:bg-secondary hover:text-black transition">Lustres</button>
                <button class="px-6 py-2 bg-white text-primary rounded-full font-medium border border-primary hover:bg-secondary hover:text-black transition">LED</button>
                <button class="px-6 py-2 bg-white text-primary rounded-full font-medium border border-primary hover:bg-secondary hover:text-black transition">Spots</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php 
                // Icônes de secours si pas d'image
                $product_icons = ['💡', '🏮', '🔦', '✨', '🔌', '🕯️', '💫', '🪔'];
                
                foreach ($tous_produits as $index => $produit): 
                ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-2 duration-300 group relative">
                    
                    <!-- Badge IA : Recommandé (Exemple fictif, à dynamiser plus tard) -->
                    <?php if ($index == 0): // Juste pour l'exemple ?>
                        <div class="absolute top-3 left-3 z-10 bg-blue-600 text-white text-xs px-2 py-1 rounded-full flex items-center shadow-lg">
                            <span class="mr-1">🤖</span> Recommandé
                        </div>
                    <?php endif; ?>

                    <div class="relative h-64 bg-gradient-to-br from-gray-200 to-gray-100 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($produit['image'])): ?>
                            <img src="uploads/<?php echo $produit['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="<?php echo htmlspecialchars($produit['nom']); ?>">
                        <?php else: ?>
                            <div class="text-8xl text-gray-400 group-hover:scale-110 transition duration-500">
                                <?php echo $product_icons[$index % count($product_icons)]; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($produit['nouveau']) && $produit['nouveau']): ?>
                            <span class="absolute top-3 right-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg">Nouveau</span>
                        <?php endif; ?>
                        
                        <?php if (isset($produit['promo']) && $produit['promo']): ?>
                            <span class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg animate-pulse">Promo</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-6">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1"><?php echo htmlspecialchars($produit['categorie_nom']); ?></div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2 truncate"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                        
                        <!-- Référence produit utile pour le B2B -->
                        <p class="text-xs text-gray-400 mb-2">Réf: <?php echo htmlspecialchars($produit['reference'] ?? 'N/A'); ?></p>

                        <div class="flex items-baseline justify-between mb-4">
                            <div>
                                <span class="text-2xl font-bold text-secondary"><?php echo number_format($produit['prix'], 2, ',', ' '); ?> DT</span>
                                <!-- Suppression du "poids" pour mettre "Disponibilité" -->
                            </div>
                            <?php if ($produit['stock'] > 0): ?>
                                <span class="text-xs text-green-600 font-semibold bg-green-100 px-2 py-1 rounded">En stock</span>
                            <?php else: ?>
                                <span class="text-xs text-red-600 font-semibold bg-red-100 px-2 py-1 rounded">Rupture</span>
                            <?php endif; ?>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <?php if ($produit['stock'] > 0): ?>
                                <form method="POST" action="user/update_panier.php">
                                    <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                    <button type="submit" class="w-full bg-primary hover:bg-zinc-800 text-white font-bold py-3 rounded-xl transition text-sm glow-effect">
                                        Ajouter au panier
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-xl text-sm cursor-not-allowed">
                                    Rupture de stock
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="user/login.php" class="block w-full bg-secondary hover:bg-yellow-500 text-gray-900 font-bold py-3 rounded-xl text-center transition text-sm">
                                Connectez-vous pour acheter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Section Avantages (Pourquoi nous choisir) -->
    <section class="py-20 bg-zinc-900 text-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-12">
                Pourquoi <span class="text-secondary">SOUILEM LIGHTING</span> ?
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition duration-300 border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🏭</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Showroom</h3>
                    <p class="text-gray-400">Venez tester nos produits dans notre espace d'exposition physique.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition duration-300 border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🚚</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Service Livraison</h3>
                    <p class="text-gray-400">Équipe dédiée pour une livraison rapide et soignée.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition duration-300 border-b-4 border-secondary">
                    <div class="text-5xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Stock Fiable</h3>
                    <p class="text-gray-400">Gestion optimisée pour éviter les ruptures et les retards.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition duration-300 border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🤝</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Conseil Pro</h3>
                    <p class="text-gray-400">Notre équipe commerciale vous guide dans vos choix.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-gray-400 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <span class="text-3xl">💡</span>
                        <h3 class="text-xl font-bold text-white uppercase">Souilem Lighting</h3>
                    </div>
                    <p class="text-sm leading-relaxed">Votre partenaire de confiance pour l'éclairage résidentiel et professionnel.</p>
                </div>
                <div>
                    <h3 class="text-base font-bold mb-4 text-secondary border-b border-secondary/30 pb-2 inline-block">Navigation</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="home.php" class="hover:text-secondary transition">→ Accueil</a></li>
                        <li><a href="#produits" class="hover:text-secondary transition">→ Catalogue</a></li>
                        <li><a href="admin/dashboard.php" class="hover:text-secondary transition">→ Espace Pro</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base font-bold mb-4 text-secondary border-b border-secondary/30 pb-2 inline-block">Contact</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex items-center"><span class="mr-2">📍</span> Adresse du Showroom, Tunis</li>
                        <li class="flex items-center"><span class="mr-2">📞</span> +216 XX XXX XXX</li>
                        <li class="flex items-center"><span class="mr-2">✉️</span> contact@souilemlighting.tn</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base font-bold mb-4 text-secondary border-b border-secondary/30 pb-2 inline-block">Newsletter</h3>
                    <p class="text-sm mb-3">Recevez nos nouveautés et promotions.</p>
                    <form class="flex">
                        <input type="email" placeholder="Email" class="px-3 py-2 bg-zinc-800 text-white rounded-l-lg w-full focus:outline-none focus:ring-1 focus:ring-secondary">
                        <button class="bg-secondary text-black px-4 rounded-r-lg font-bold hover:bg-yellow-500">OK</button>
                    </form>
                </div>
            </div>
            <div class="border-t border-zinc-800 pt-6 text-center text-xs">
                <p>&copy; <?php echo date('Y'); ?> SOUILEM LIGHTING. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <script src="js/home.js"></script>
    <script>
        // Simple toggle pour le menu mobile
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });
        
        // Placeholder pour l'ouverture du Chatbot
        document.getElementById('chatbot-btn').addEventListener('click', function() {
            alert("L'assistant IA SOUILEM LIGHTING arrive bientôt !"); 
            // Ici, vous injecterez l'interface du chatbot plus tard
        });
    </script>
</body>
</html>
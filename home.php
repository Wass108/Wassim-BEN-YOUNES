

<?php
require_once 'db/db.php';

// Récupération des produits avec le nom de la catégorie
 $stmt = $pdo->query("SELECT p.*, c.nom as categorie_nom FROM produits p 
                     LEFT JOIN categories c ON p.categorie_id = c.id 
                     ORDER BY p.date_creation DESC");
 $tous_produits = $stmt->fetchAll();

// Récupération des catégories pour les filtres
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
                        primary: '#1a1a1a',
                        secondary: '#D4AF37',
                        accent: '#FFD700',
                        light: '#F5F5F5',
                    }
                }
            }
        }
    </script>
    <style>
        /* Effets Globaux */
        .glow-effect:hover { box-shadow: 0 0 20px rgba(212, 175, 55, 0.7); }
        
        /* Animation d'apparition des produits */
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* --- STYLES ASSISTANT IA RÉVOLUTIONNAIRE --- */
        
        /* Animation pulsante lente pour le bouton */
        @keyframes ping-slow {
            0% { transform: scale(1); opacity: 1; }
            75%, 100% { transform: scale(1.5); opacity: 0; }
        }
        .animate-ping-slow { animation: ping-slow 2s cubic-bezier(0, 0, 0.2, 1) infinite; }

        /* Scrollbar personnalisée pour le chat */
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #D4AF37; border-radius: 10px; }
        
        /* Effet Glassmorphism pour la fenêtre de chat */
        .glass-effect {
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="bg-primary text-light">
    <?php include 'ia/chatbot_widget.php'; ?>

    <!-- Hero Section -->
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
                            <li><a href="contact.php" class="hover:text-secondary transition font-medium">Contact</a></li>
                            
                            <li>
                                <a href="<?php echo isLoggedIn() ? 'user/dashboard.php' : 'user/login.php'; ?>" class="hover:text-secondary transition">
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
                $category_icons = ['🏮', '💡', '✨', '🔌']; 
                foreach ($categories as $index => $categorie): 
                ?>
                <a href="categorie.php?id=<?php echo $categorie['id']; ?>" class="block bg-gradient-to-br from-zinc-800 to-zinc-700 p-8 rounded-2xl shadow-lg border border-zinc-600 hover:border-secondary transition cursor-pointer transform hover:-translate-y-2 duration-300 group">
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
            
            <!-- Filtres Dynamiques -->
            <div class="flex flex-wrap justify-center gap-4 mb-10" id="filters">
                <button data-filter="Tous" class="filter-btn active px-6 py-2 bg-primary text-white rounded-full font-medium hover:bg-secondary hover:text-black transition">Tous</button>
                <?php foreach ($categories as $cat): ?>
                    <button data-filter="<?php echo htmlspecialchars($cat['nom']); ?>" class="filter-btn px-6 py-2 bg-white text-primary rounded-full font-medium border border-primary hover:bg-secondary hover:text-black transition">
                        <?php echo htmlspecialchars($cat['nom']); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" id="products-grid">
                <?php 
                $product_icons = ['💡', '🏮', '🔦', '✨', '🔌', '🕯️', '💫', '🪔'];
                
                foreach ($tous_produits as $index => $produit): 
                    $cat_name = htmlspecialchars($produit['categorie_nom']);
                ?>
                <div class="product-card bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition transform hover:-translate-y-2 duration-300 group relative fade-in" data-category="<?= $cat_name ?>">
                    
                    <?php if ($index == 0): ?>
                        <div class="absolute top-3 left-3 z-10 bg-blue-600 text-white text-xs px-2 py-1 rounded-full flex items-center shadow-lg">
                            <span class="mr-1">🤖</span> Recommandé
                        </div>
                    <?php endif; ?>

                    <div class="relative h-64 bg-gradient-to-br from-gray-200 to-gray-100 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($produit['image'])): ?>
                            <img src="<?php echo htmlspecialchars($produit['image']); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500" alt="">
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
                        
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                            <a href="produit.php?id=<?= $produit['id'] ?>" class="bg-white text-primary px-4 py-2 rounded-lg font-bold transform -translate-y-4 group-hover:translate-y-0 transition">
                                Voir détails
                            </a>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1"><?= $cat_name ?></div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2 truncate"><?= htmlspecialchars($produit['nom']) ?></h3>
                        <p class="text-xs text-gray-400 mb-2">Réf: <?php echo htmlspecialchars($produit['reference'] ?? 'N/A'); ?></p>

                        <div class="flex items-baseline justify-between mb-4">
                            <span class="text-2xl font-bold text-secondary"><?= number_format($produit['prix'], 2, ',', ' ') ?> DT</span>
                            <?php if ($produit['stock'] > 0): ?>
                                <span class="text-xs text-green-600 font-semibold bg-green-100 px-2 py-1 rounded">En stock</span>
                            <?php else: ?>
                                <span class="text-xs text-red-600 font-semibold bg-red-100 px-2 py-1 rounded">Rupture</span>
                            <?php endif; ?>
                        </div>

                        <?php if (isLoggedIn()): ?>
                            <?php if ($produit['stock'] > 0): ?>
                                <form method="POST" action="user/update_panier.php">
                                    <input type="hidden" name="produit_id" value="<?= $produit['id'] ?>">
                                    <button type="submit" class="w-full bg-primary hover:bg-zinc-800 text-white font-bold py-3 rounded-xl transition text-sm glow-effect">
                                        Ajouter au panier
                                    </button>
                                </form>
                            <?php else: ?>
                                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-xl text-sm cursor-not-allowed">Rupture de stock</button>
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

    <!-- Section Avantages -->
    <section class="py-20 bg-zinc-900 text-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-12">
                Pourquoi <span class="text-secondary">SOUILEM LIGHTING</span> ?
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🏭</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Showroom</h3>
                    <p class="text-gray-400">Venez tester nos produits dans notre espace d'exposition.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🚚</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Livraison Rapide</h3>
                    <p class="text-gray-400">Équipe dédiée pour une livraison soignée.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition border-b-4 border-secondary">
                    <div class="text-5xl mb-4">⚡</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Stock Fiable</h3>
                    <p class="text-gray-400">Gestion optimisée pour éviter les retards.</p>
                </div>
                <div class="bg-zinc-800 p-8 rounded-2xl text-center hover:bg-zinc-700 transition border-b-4 border-secondary">
                    <div class="text-5xl mb-4">🤝</div>
                    <h3 class="text-xl font-bold text-secondary mb-2">Conseil Pro</h3>
                    <p class="text-gray-400">Notre équipe vous guide dans vos choix.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-gray-400 py-12 border-t border-zinc-800">
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
                        <li class="flex items-center"><span class="mr-2">📍</span> Tunis, Tunisie</li>
                        <li class="flex items-center"><span class="mr-2">📞</span> +216 XX XXX XXX</li>
                        <li class="flex items-center"><span class="mr-2">✉️</span> contact@souilemlighting.tn</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-base font-bold mb-4 text-secondary border-b border-secondary/30 pb-2 inline-block">Newsletter</h3>
                    <p class="text-sm mb-3">Recevez nos nouveautés.</p>
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
        // 1. Menu Mobile
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // 2. Filtres Produits
        const filterButtons = document.querySelectorAll('.filter-btn');
        const productCards = document.querySelectorAll('.product-card');

        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                filterButtons.forEach(b => {
                    b.classList.remove('bg-primary', 'text-white', 'active');
                    b.classList.add('bg-white', 'text-primary');
                });
                btn.classList.remove('bg-white', 'text-primary');
                btn.classList.add('bg-primary', 'text-white', 'active');

                const filter = btn.getAttribute('data-filter');
                productCards.forEach(card => {
                    card.style.display = (filter === 'Tous' || card.getAttribute('data-category') === filter) ? 'block' : 'none';
                });
            });
        });

        // 3. Intelligence Artificielle (Logique)
        const chatInterface = document.getElementById('ai-chat-interface');
        const chatArea = document.getElementById('chat-area');
        const aiInput = document.getElementById('ai-input');
        const aiForm = document.getElementById('ai-form');
        const aiBubble = document.getElementById('ai-bubble');
        let isOpen = false;

        // Ouverture automatique après 3 secondes
        setTimeout(() => {
            if (!isOpen) aiBubble.classList.remove('hidden');
        }, 3000);

        function toggleAI() {
            isOpen = !isOpen;
            aiBubble.classList.add('hidden');
            
            if (isOpen) {
                chatInterface.classList.remove('hidden');
                setTimeout(() => {
                    chatInterface.classList.remove('scale-95', 'opacity-0');
                    chatInterface.classList.add('scale-100', 'opacity-100');
                    aiInput.focus();
                }, 10);
            } else {
                chatInterface.classList.remove('scale-100', 'opacity-100');
                chatInterface.classList.add('scale-95', 'opacity-0');
                setTimeout(() => chatInterface.classList.add('hidden'), 300);
            }
        }

        // Gestion du formulaire
        aiForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = aiInput.value.trim();
            if(!msg) return;

            addMessage(msg, 'user');
            aiInput.value = '';

            // Simulation de réponse locale (pour la démo)
            setTimeout(() => {
                let response = "Je suis connecté à votre serveur pour vous aider.";
                if(msg.toLowerCase().includes("lustre")) response = "Nous avons de superbes lustres cristal et modernes. Je vous invite à regarder notre section 'Lustres'.";
                else if(msg.toLowerCase().includes("spot")) response = "Nos spots LED sont parfaits pour un éclairage efficace. Souhaitez-vous des encastrables ou sur rails ?";
                else response = "C'est une excellente question. Je vous propose de visiter notre showroom ou de consulter le catalogue en bas de page.";
                
                addMessage(response, 'bot');
            }, 800);
        });

        // Boutons de suggestions
        function askAI(text) {
            aiInput.value = text;
            aiForm.dispatchEvent(new Event('submit'));
        }

        // Ajout de messages visuels
        function addMessage(text, type) {
            const div = document.createElement('div');
            div.className = `flex items-end gap-3 ${type === 'user' ? 'flex-row-reverse' : ''}`;
            
            const avatar = type === 'user' 
                ? '<div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0 text-white text-xs">Vous</div>'
                : '<div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center flex-shrink-0 text-black text-xs">IA</div>';

            const bubbleStyle = type === 'user' 
                ? 'bg-blue-600 text-white rounded-2xl rounded-br-none' 
                : 'bg-zinc-800 text-gray-200 rounded-2xl rounded-bl-none';

            div.innerHTML = `${avatar}<div class="${bubbleStyle} px-4 py-3 max-w-[80%] text-sm leading-relaxed shadow-md">${text}</div>`;
            chatArea.appendChild(div);
            chatArea.scrollTop = chatArea.scrollHeight;
        }
    </script>
</body>
</html>
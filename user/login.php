<?php
require_once '../db/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Identifiants incorrects.";
        }
    }
}

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - SOUILEM LIGHTING</title>
    <link rel="stylesheet" href="css/login.css">
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
                            <li>
                                <a href="../home.php" class="hover:text-yellow-400 transition font-medium" title="Accueil">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-8 9 8v8a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4H9v4a2 2 0 01-2 2H3a2 2 0 01-2-2v-8z" />
                                    </svg>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'panier.php'; ?>" class="relative hover:text-yellow-400 transition">
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
                        <a href="<?php echo isLoggedIn() ? 'dashboard.php' : 'panier.php'; ?>" class="relative hover:text-yellow-400 transition">
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
                        <li>
                            <a href="../home.php" class="block py-2 px-4 hover:bg-white/10 rounded transition flex items-center" title="Accueil">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-8 9 8v8a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4H9v4a2 2 0 01-2 2H3a2 2 0 01-2-2v-8z" />
                                </svg>
                                Accueil
                            </a>
                        </li>
                        <li><a href="../home.php#produits" class="block py-2 px-4 hover:bg-white/10 rounded transition">Produits</a></li>
                        <li><a href="../home.php#categories" class="block py-2 px-4 hover:bg-white/10 rounded transition">Catégories</a></li>
                        <li><a href="../about.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">À Propos</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <div class="relative z-40 flex-grow flex items-center justify-center px-4 py-12">
            <div class="max-w-md w-full">
                <div class="glass-effect rounded-3xl p-8 md:p-10 animate-scaleIn">
                    <div class="text-center mb-8">
                        <div class="inline-block p-4 bg-gradient-to-br from-primary to-amber-700 rounded-full mb-4">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <h2 class="text-4xl font-bold text-primary mb-2">Bienvenue</h2>
                        <p class="text-gray-600">Connectez-vous à votre compte</p>
                    </div>
                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6 animate-fadeInUp">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium"><?php echo $error; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg mb-6 animate-fadeInUp">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium"><?php echo $success; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-6">
                        <div class="animate-fadeInUp delay-200">
                            <label for="username" class="block text-sm font-bold text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Nom d'utilisateur ou Email
                                </span>
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus"
                                placeholder="Entrez votre nom d'utilisateur ou email"
                            >
                        </div>
                        <div class="animate-fadeInUp delay-400">
                            <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    Mot de passe
                                </span>
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus"
                                placeholder="Entrez votre mot de passe"
                            >
                        </div>
                        <div class="animate-fadeInUp delay-600">
                            <button 
                                type="submit" 
                                class="w-full bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl btn-hover-effect shine-effect"
                            >
                                <span class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Se connecter
                                </span>
                            </button>
                        </div>
                    </form>
                    <div class="mt-8 text-center animate-fadeInUp delay-600">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white text-gray-500 font-medium">Nouveau client ?</span>
                            </div>
                        </div>
                        
                        <a href="create_account.php" class="inline-block mt-6 px-8 py-3 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-xl">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Créer un compte
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="fixed bottom-0 left-0 right-0 z-30 bg-black/30 backdrop-blur-sm text-white py-3">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="text-white/80 text-sm">
                    &copy; <?php echo date('Y'); ?> SOUILEM LIGHTING - Tous droits réservés
                </p>
            </div>
        </div>
    </footer>
<script src="js/login.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../db/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nom = sanitize($_POST['nom'] ?? '');
    $prenom = sanitize($_POST['prenom'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $errors[] = 'Veuillez remplir les champs obligatoires.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse e-mail invalide.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors[] = 'Un compte avec ce nom d\'utilisateur ou cette adresse e-mail existe déjà.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (username, email, password, nom, prenom) VALUES (?, ?, ?, ?, ?)');
        try {
            $insert->execute([$username, $email, $hash, $nom, $prenom]);
            $success = 'Votre compte a été créé avec succès. Vous pouvez vous connecter.';
            header('Location: login.php?registered=1');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Erreur lors de la création du compte : ' . $e->getMessage();
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
    <title>Créer un compte - SOUILEM LIGHTING</title>
    <link rel="stylesheet" href="css/create_account.css">
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
    <section class="relative h-screen bg-[url('../image/bg.png')] bg-cover bg-center flex flex-col overflow-hidden">
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
                            <li><a href="login.php" class="hover:text-yellow-400 transition font-medium">Connexion</a></li>
                        </ul>
                    </nav>
                    <button id="mobileMenuBtn" class="md:hidden text-white focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <nav id="mobileMenu" class="hidden md:hidden pb-4">
                    <ul class="space-y-3">
                        <li><a href="../home.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Accueil</a></li>
                        <li><a href="../home.php#produits" class="block py-2 px-4 hover:bg-white/10 rounded transition">Produits</a></li>
                        <li><a href="login.php" class="block py-2 px-4 hover:bg-white/10 rounded transition">Connexion</a></li>
                    </ul>
                </nav>
            </div>
        </header>
        <div class="relative z-40 flex-grow flex items-center justify-center px-4 py-4">
            <div class="max-w-2xl w-full mb-16">
                <div class="glass-effect rounded-3xl p-4 md:p-6 animate-scaleIn max-h-[85vh] overflow-y-auto no-scrollbar">
                    <div class="text-center mb-4">
                        <div class="inline-block p-2 bg-gradient-to-br from-primary to-amber-700 rounded-full mb-2">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-primary mb-1">Créer un compte</h1>
                        <p class="text-gray-600 text-xs">Rejoignez la communauté SOUILEM LIGHTING</p>
                    </div>
                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-lg animate-fadeInUp">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <ul class="list-disc list-inside space-y-1 text-sm">
                                <?php foreach ($errors as $err): ?>
                                    <li class="font-medium"><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-lg animate-fadeInUp">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium text-sm"><?php echo htmlspecialchars($success); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <form method="POST" class="space-y-4" novalidate>
                    <div class="animate-fadeInUp delay-200">
                        <label for="username" class="block text-sm font-bold text-gray-700 mb-1">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Nom d'utilisateur *
                            </span>
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                            placeholder="johndoe"
                        >
                    </div>
                    <div class="animate-fadeInUp delay-200">
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Adresse e-mail *
                            </span>
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                            placeholder="john@exemple.com"
                        >
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fadeInUp delay-400">
                        <div>
                            <label for="nom" class="block text-sm font-bold text-gray-700 mb-1">
                                Nom
                            </label>
                            <input 
                                type="text" 
                                id="nom" 
                                name="nom"
                                value="<?php echo isset($nom) ? htmlspecialchars($nom) : ''; ?>"
                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                                placeholder="Doe"
                            >
                        </div>
                        <div>
                            <label for="prenom" class="block text-sm font-bold text-gray-700 mb-1">
                                Prénom
                            </label>
                            <input 
                                type="text" 
                                id="prenom" 
                                name="prenom"
                                value="<?php echo isset($prenom) ? htmlspecialchars($prenom) : ''; ?>"
                                class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                                placeholder="John"
                            >
                        </div>
                    </div>
                    <div class="animate-fadeInUp delay-400">
                        <label for="password" class="block text-sm font-bold text-gray-700 mb-1">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Mot de passe * (min. 8 caractères)
                            </span>
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                            placeholder="••••••••"
                        >
                    </div>
                    <div class="animate-fadeInUp delay-600">
                        <label for="password_confirm" class="block text-sm font-bold text-gray-700 mb-1">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                Confirmer le mot de passe *
                            </span>
                        </label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            required
                            class="w-full px-4 py-2.5 border-2 border-gray-300 rounded-xl focus:border-primary focus:outline-none input-focus text-sm"
                            placeholder="••••••••"
                        >
                    </div>
                    <div class="animate-fadeInUp delay-600 pt-2">
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl btn-hover-effect shine-effect"
                        >
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                </svg>
                                Créer mon compte
                            </span>
                        </button>
                    </div>
                    <div class="mt-6 text-center animate-fadeInUp delay-600">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white text-gray-500 font-medium">Déjà client ?</span>
                            </div>
                        </div>
                        <a href="login.php" class="inline-block mt-4 px-6 py-2.5 bg-white border-2 border-primary text-primary font-bold rounded-xl hover:bg-primary hover:text-white transition-all duration-300 transform hover:scale-105 shadow-md hover:shadow-xl text-sm">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Se connecter
                            </span>
                        </a>
                    </div>
                </form>
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
<script src="js/create_account.js"></script>
</body>
</html>
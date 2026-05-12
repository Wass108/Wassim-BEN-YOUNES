<?php
require_once '../db/db.php';

if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password']) && $user['role'] === 'admin') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Accès refusé. Identifiants administrateur incorrects.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Page de connexion administrateur - Accès sécurisé au panneau d'administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion Admin | Panneau d'administration</title>
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
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }
        
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .animate-float {
            animation: float 3s ease-in-out infinite;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .shine {
            position: relative;
            overflow: hidden;
        }
        
        .shine::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(255, 255, 255, 0.1),
                transparent
            );
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% {
                transform: translateX(-100%) translateY(-100%) rotate(45deg);
            }
            100% {
                transform: translateX(100%) translateY(100%) rotate(45deg);
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 min-h-screen font-sans antialiased">
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-float"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-float" style="animation-delay: 1s;"></div>
        <div class="absolute -bottom-8 left-1/3 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-float" style="animation-delay: 2s;"></div>
    </div>
    <div class="container mx-auto px-4 py-12 min-h-screen flex items-center justify-center relative z-10">
        <div class="max-w-md w-full">
            <div class="text-center mb-8 animate-fadeInUp">
                <div class="inline-block mb-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-secondary rounded-2xl shadow-2xl flex items-center justify-center transform hover:scale-110 transition-transform duration-300 shine">
                        <i class="fas fa-shield-halved text-white text-4xl"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    Administration
                </h1>
                <p class="text-gray-600 text-lg">Accès sécurisé au panneau de contrôle</p>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden animate-fadeInUp" style="animation-delay: 0.2s;">
                <div class="h-2 bg-gradient-to-r from-primary via-secondary to-accent"></div>
                <div class="p-8">
                    <div class="flex items-center justify-center mb-6">
                        <div class="flex items-center space-x-2">
                            <div class="w-8 h-1 bg-primary rounded-full"></div>
                            <h2 class="text-2xl font-bold text-gray-900">Connexion</h2>
                            <div class="w-8 h-1 bg-secondary rounded-full"></div>
                        </div>
                    </div>
                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4 animate-fadeInUp">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-bold text-red-800">Erreur de connexion</h3>
                                    <p class="text-sm text-red-700 mt-1"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-6">
                        <div class="group">
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user text-primary mr-2"></i>
                                Nom d'utilisateur
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-user-shield text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="username" 
                                    name="username" 
                                    required
                                    autocomplete="username"
                                    class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-blue-50 focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400"
                                    placeholder="Entrez votre identifiant admin"
                                >
                            </div>
                        </div>
                        <div class="group">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock text-primary mr-2"></i>
                                Mot de passe
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-key text-gray-400 group-focus-within:text-primary transition-colors"></i>
                                </div>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    autocomplete="current-password"
                                    class="w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-4 focus:ring-blue-50 focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-400"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-secondary hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center space-x-2 group">
                        >
                            <i class="fas fa-sign-in-alt group-hover:scale-110 transition-transform"></i>
                            <span>Se connecter</span>
                            <i class="fas fa-arrow-right opacity-0 group-hover:opacity-100 transform translate-x-0 group-hover:translate-x-1 transition-all"></i>
                        </button>
                    </form>
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <a href="../home.php" class="flex items-center justify-center text-gray-600 hover:text-primary transition-colors group">
                            <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform"></i>
                            <span class="font-medium">Retour au site</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 text-center text-sm text-gray-500 animate-fadeInUp" style="animation-delay: 0.4s;">
                <p>&copy; <?php echo date('Y'); ?>. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</body>
</html>

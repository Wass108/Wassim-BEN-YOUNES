<?php
require_once '../db/db.php';

$success = '';
$error = '';

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
$stmt->execute();
$admin_exists = $stmt->fetch()['count'] > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($admin_exists) {
        $error = "Un compte administrateur existe déjà. Cette page est désactivée.";
    } else {
        $username = 'admin';
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $nom = sanitize($_POST['nom'] ?? '');
        $prenom = sanitize($_POST['prenom'] ?? '');
        
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Adresse email invalide.";
        } elseif (strlen($password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } elseif ($password !== $password_confirm) {
            $error = "Les mots de passe ne correspondent pas.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, nom, prenom, role, date_creation) 
                        VALUES (?, ?, ?, ?, ?, 'admin', NOW())
                    ");
                    $stmt->execute([$username, $email, $hashed_password, $nom, $prenom]);
                    
                    $success = "Compte administrateur créé avec succès ! Vous pouvez maintenant vous connecter.";
                    $admin_exists = true;
                } catch (PDOException $e) {
                    $error = "Erreur lors de la création du compte : " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte administrateur</title>
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
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-red-50 min-h-screen flex items-center justify-center py-12">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-purple-700 mb-2">Créer le compte administrateur principal</h1>
            </div>
            <div class="bg-white rounded-2xl shadow-2xl p-8 border-t-4 border-purple-600">
                <?php if ($admin_exists && !$success): ?>
                    <div class="text-center py-12">
                        <div class="text-7xl mb-6">🔒</div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-4">Compte administrateur déjà créé</h2>
                        <p class="text-gray-600 mb-8">Un compte administrateur existe déjà. Cette page est désormais désactivée pour des raisons de sécurité.</p>
                        <a href="login.php" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold px-8 py-4 rounded-lg transition transform hover:scale-105 shadow-lg">
                            🔐 Aller à la connexion admin
                        </a>
                    </div>
                    
                <?php else: ?>
                    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Créer le compte administrateur</h2>
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                            <span class="text-2xl mr-3">⚠️</span>
                            <div>
                                <p class="font-semibold">Erreur</p>
                                <p class="text-sm"><?php echo $error; ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-start">
                            <span class="text-2xl mr-3">✅</span>
                            <div>
                                <p class="font-semibold">Succès !</p>
                                <p class="text-sm"><?php echo $success; ?></p>
                            </div>
                        </div>
                        <div class="text-center mt-6">
                            <a href="login.php" class="inline-block bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-bold px-8 py-4 rounded-lg transition transform hover:scale-105 shadow-lg">
                                🔐 Se connecter en tant qu'admin
                            </a>
                        </div>
                    <?php else: ?>
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="prenom" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <span class="text-lg mr-2">👤</span>
                                        Prénom
                                    </span>
                                </label>
                                <input 
                                    type="text" 
                                    id="prenom" 
                                    name="prenom" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                                    placeholder="Votre prénom"
                                >
                            </div>
                            <div>
                                <label for="nom" class="block text-sm font-semibold text-gray-700 mb-2">
                                    <span class="flex items-center">
                                        <span class="text-lg mr-2">👤</span>
                                        Nom
                                    </span>
                                </label>
                                <input 
                                    type="text" 
                                    id="nom" 
                                    name="nom" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                                    placeholder="Votre nom"
                                >
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <span class="text-lg mr-2">📧</span>
                                    Email <span class="text-red-500 ml-1">*</span>
                                </span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                                placeholder="admin@example.com"
                            >
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <span class="text-lg mr-2">🔑</span>
                                    Mot de passe <span class="text-red-500 ml-1">*</span>
                                </span>
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                minlength="6"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                                placeholder="Minimum 6 caractères"
                            >
                            <p class="text-xs text-gray-500 mt-1">Le mot de passe doit contenir au moins 6 caractères</p>
                        </div>
                        <div>
                            <label for="password_confirm" class="block text-sm font-semibold text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <span class="text-lg mr-2">🔒</span>
                                    Confirmer le mot de passe <span class="text-red-500 ml-1">*</span>
                                </span>
                            </label>
                            <input 
                                type="password" 
                                id="password_confirm" 
                                name="password_confirm" 
                                required
                                minlength="6"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-purple-600 focus:outline-none transition"
                                placeholder="Confirmez votre mot de passe"
                            >
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <p class="text-sm text-purple-800">
                                <span class="font-semibold">📝 Nom d'utilisateur :</span> <strong>admin</strong> (défini automatiquement)
                            </p>
                        </div>
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold py-4 rounded-lg transition transform hover:scale-105 shadow-lg text-lg"
                        >
                            👑 Créer le compte administrateur
                        </button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <a href="../home.php" class="flex items-center justify-center text-gray-600 hover:text-primary transition">
                        <span class="mr-2">←</span>
                        Retour au site
                    </a>
                </div>
                <div class="mt-6 text-center">
                    <p>&copy; <?php echo date('Y'); ?>. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
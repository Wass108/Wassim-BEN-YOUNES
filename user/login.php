







<?php
require_once '../db/db.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

 $errors = [];
 $email = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    checkCSRF();

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Vérification Rate Limiting
    if (isRateLimited($pdo)) {
        $errors[] = "Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.";
    } else {
        if (empty($email) || empty($password)) {
            $errors[] = "Tous les champs sont requis.";
        } else {
            // Recherche l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Connexion réussie
                logLoginAttempt($pdo, $email, true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['role'] = $user['role'];
                
                // Régénérer l'ID de session pour la sécurité
                session_regenerate_id(true);

                // Redirection selon le rôle
                if (isAdmin()) {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit;
            } else {
                // Échec connexion
                logLoginAttempt($pdo, $email, false);
                $errors[] = "Email ou mot de passe incorrect.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - SOUILEM LIGHTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a1a1a',
                        secondary: '#D4AF37',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-primary min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="../home.php" class="text-3xl font-bold text-secondary">💡 SOUILEM LIGHTING</a>
            <p class="text-gray-400 mt-2">Espace Client</p>
        </div>

        <div class="bg-zinc-900 rounded-2xl shadow-2xl p-8 border border-zinc-800">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Connexion</h2>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                
                <div class="mb-5">
                    <label class="block text-gray-400 mb-2">Adresse Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" 
                           required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-400 mb-2">Mot de passe</label>
                    <input type="password" name="password" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" 
                           required>
                </div>

                <button type="submit" class="w-full bg-secondary hover:bg-yellow-500 text-gray-900 font-bold py-3 rounded-lg transition transform hover:scale-105 glow-effect">
                    Se connecter
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-500">
                    Pas encore de compte ? 
                    <a href="register.php" class="text-secondary hover:underline">S'inscrire</a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
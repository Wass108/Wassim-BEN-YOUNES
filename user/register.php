



<?php
require_once '../db/db.php';

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

 $errors = [];
 $nom = '';
 $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();

    $nom = sanitize($_POST['nom'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validations
    if (empty($nom) || empty($email) || empty($password)) {
        $errors[] = "Tous les champs sont requis.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if ($password !== $password_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Cet email est déjà utilisé.";
    }

    if (empty($errors)) {
        // Hashage sécurisé du mot de passe
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insertion en base
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, password, role) VALUES (?, ?, ?, 'client')");
        if ($stmt->execute([$nom, $email, $hashed_password])) {
            // Connexion automatique après inscription
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['nom'] = $nom;
            $_SESSION['role'] = 'client';
            
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = "Erreur lors de l'inscription. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - SOUILEM LIGHTING</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#1a1a1a', secondary: '#D4AF37' } } }
        }
    </script>
</head>
<body class="bg-primary min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="../home.php" class="text-3xl font-bold text-secondary">💡 SOUILEM LIGHTING</a>
        </div>

        <div class="bg-zinc-900 rounded-2xl shadow-2xl p-8 border border-zinc-800">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Créer un compte</h2>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrfField() ?>
                
                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Nom complet</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Adresse Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-400 mb-2">Mot de passe</label>
                    <input type="password" name="password" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" required>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-400 mb-2">Confirmer le mot de passe</label>
                    <input type="password" name="password_confirm" 
                           class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-4 py-3 text-white focus:border-secondary focus:outline-none transition" required>
                </div>

                <button type="submit" class="w-full bg-secondary hover:bg-yellow-500 text-gray-900 font-bold py-3 rounded-lg transition transform hover:scale-105">
                    S'inscrire
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-500">
                    Déjà un compte ? 
                    <a href="login.php" class="text-secondary hover:underline">Se connecter</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>


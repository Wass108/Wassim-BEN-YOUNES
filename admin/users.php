<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT c.id) as nb_commandes,
           COALESCE(SUM(c.montant_total), 0) as total_depense
    FROM users u
    LEFT JOIN commandes c ON u.id = c.user_id
    GROUP BY u.id
    ORDER BY u.date_creation DESC
");
$users = $stmt->fetchAll();

$total_users = count($users);
$users_avec_commandes = count(array_filter($users, fn($u) => $u['nb_commandes'] > 0));
$total_depenses = array_sum(array_column($users, 'total_depense'));

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && $user['username'] === 'admin') {
            $message = "Impossible de supprimer le compte administrateur !";
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM details_commande WHERE commande_id IN (SELECT id FROM commandes WHERE user_id = ?)");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM commandes WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $message = "Utilisateur supprimé avec succès !";
                $message_type = 'success';
                header('Location: users.php?success=' . urlencode($message));
                exit;
            } catch (PDOException $e) {
                $message = "Erreur lors de la suppression : " . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'add') {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $nom = sanitize($_POST['nom'] ?? '');
        $prenom = sanitize($_POST['prenom'] ?? '');
        
        if (empty($username) || empty($email) || empty($password)) {
            $message = "Veuillez remplir tous les champs obligatoires.";
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Adresse email invalide.";
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $message = "Ce nom d'utilisateur ou cette adresse email existe déjà.";
                $message_type = 'error';
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, nom, prenom, date_creation) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$username, $email, $hashed_password, $nom, $prenom]);
                    
                    $message = "Utilisateur ajouté avec succès !";
                    $message_type = 'success';
                    header('Location: users.php?success=' . urlencode($message));
                    exit;
                } catch (PDOException $e) {
                    $message = "Erreur : " . $e->getMessage();
                    $message_type = 'error';
                }
            }
        }
    }
}

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $message_type = 'success';
}

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion des utilisateurs - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion des Utilisateurs | Admin</title>
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
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen font-sans antialiased flex flex-col">
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm backdrop-blur-lg bg-opacity-90">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="dashboard.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-crown text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Administration</h1>
                    </div>
                </a>
                <ul class="hidden lg:flex items-center space-x-1">
                    <li>
                        <a href="../home.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-home"></i>
                            <span>Site</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-chart-line"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-box"></i>
                            <span>Produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-primary transition-all duration-200 font-medium">
                            <i class="fas fa-users"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>
                </ul>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-3 px-4 py-2 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center">
                            <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                        </div>
                        <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <a href="../user/logout.php" class="hidden lg:flex items-center space-x-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 font-medium shadow-sm hover:shadow-md">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </a>
                    <button id="mobileMenuBtn" class="lg:hidden text-gray-700 focus:outline-none">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <nav id="mobileMenu" class="hidden lg:hidden pb-4 border-t border-gray-200 mt-2 pt-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../home.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-home w-5"></i>
                            <span>Site</span>
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-chart-line w-5"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-box w-5"></i>
                            <span>Produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart w-5"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 px-4 py-3 bg-blue-50 text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-users w-5"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>
                    <li class="pt-2 border-t border-gray-200">
                        <div class="flex items-center space-x-3 px-4 py-2 bg-gray-50 rounded-lg mb-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </div>
                    </li>
                    <li>
                        <a href="../user/logout.php" class="flex items-center justify-center space-x-2 px-5 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 font-medium shadow-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </nav>
    <script>
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });

            const mobileLinks = mobileMenu.querySelectorAll('a');
            mobileLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenu.classList.add('hidden');
                });
            });
        }
    </script>
    <main class="container mx-auto px-6 lg:px-8 py-8 flex-grow">
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-users text-primary mr-2"></i>
                        Gestion des Utilisateurs
                    </h2>
                </div>
                <button onclick="document.getElementById('modalUser').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-user-plus mr-2"></i>
                    Ajouter un utilisateur
                </button>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800' : 'bg-red-50 border-l-4 border-red-500 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
                    <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Utilisateurs</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_users; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Clients actifs</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $users_avec_commandes; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Sans commande</p>
                        <p class="text-3xl font-bold text-orange-600">
                            <?php echo $total_users - $users_avec_commandes; ?>
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-clock text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">CA Total</p>
                        <p class="text-2xl font-bold text-purple-600">
                            <?php echo number_format($total_depenses, 0, ',', ' '); ?>€
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-euro-sign text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Inscription</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Commandes</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total dépensé</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                                        <span class="text-white text-sm font-semibold">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 flex items-center">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['username'] === 'admin'): ?>
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                                    <i class="fas fa-crown mr-1"></i>Admin
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($user['nom'] || $user['prenom']): ?>
                                            <p class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars(trim($user['prenom'] . ' ' . $user['nom'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700 flex items-center">
                                    <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span class="flex items-center">
                                        <i class="far fa-calendar-alt mr-1 text-gray-400"></i>
                                        <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?>
                                    </span>
                                    <span class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('H:i', strtotime($user['date_creation'])); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($user['nb_commandes'] > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                        <i class="fas fa-shopping-cart mr-1.5"></i>
                                        <?php echo $user['nb_commandes']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">
                                        <i class="fas fa-minus mr-1.5"></i>
                                        Aucune
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-lg bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                                    <?php echo number_format($user['total_depense'], 2, ',', ' '); ?> €
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($user['username'] !== 'admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Toutes ses commandes seront également supprimées.')" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                            <i class="fas fa-trash mr-1"></i>
                                            Supprimer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        Protégé
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <div id="modalUser" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-primary to-blue-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-plus mr-2"></i>
                    Ajouter un utilisateur
                </h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Nom d'utilisateur *
                        </label>
                        <input type="text" name="username" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-1"></i>Email *
                        </label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Prénom
                        </label>
                        <input type="text" name="prenom" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Nom
                        </label>
                        <input type="text" name="nom" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1"></i>Mot de passe *
                    </label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 caractères</p>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>
                        Ajouter l'utilisateur
                    </button>
                    <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="container mx-auto px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <p class="font-bold text-gray-800">Administration</p>
                </div>
                <p>&copy; <?php echo date('Y'); ?>. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    <script>
        function closeModal() {
            document.getElementById('modalUser').classList.add('hidden');
            document.querySelector('form').reset();
        }
    </script>
</body>
</html>

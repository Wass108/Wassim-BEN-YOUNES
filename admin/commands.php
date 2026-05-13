<?php
header('Content-Type: text/html; charset=UTF-8');
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$filtre_statut = $_GET['statut'] ?? '';
$filtre_recherche = $_GET['recherche'] ?? '';

$sql = "
    SELECT c.*, u.username, u.email
    FROM commandes c
    JOIN users u ON c.user_id = u.id
    WHERE 1=1
";

$params = [];

if ($filtre_statut) {
    $sql .= " AND c.statut = ?";
    $params[] = $filtre_statut;
}

if ($filtre_recherche) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR c.id LIKE ?)";
    $search_term = '%' . $filtre_recherche . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY c.date_commande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes");
$total_commandes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes WHERE statut = 'en_attente'");
$commandes_en_attente = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes WHERE statut = 'confirmee' OR statut = 'expedie'");
$commandes_en_cours = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM commandes WHERE statut = 'livree'");
$commandes_livrees = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(montant_total) as total FROM commandes WHERE statut != 'annulee'");
$chiffre_affaires = $stmt->fetch()['total'] ?? 0;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_statut') {
        $commande_id = intval($_POST['commande_id'] ?? 0);
        $nouveau_statut = sanitize($_POST['nouveau_statut'] ?? '');

        $statuts_valides = ['en_attente', 'confirmee', 'expedie', 'livree', 'annulee'];

        if (in_array($nouveau_statut, $statuts_valides)) {
            try {
                $stmt = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
                $stmt->execute([$nouveau_statut, $commande_id]);
                $message = "Statut de la commande mis à jour avec succès !";
                $message_type = 'success';
                header('Location: commands.php?success=' . urlencode($message));
                exit;
            } catch (PDOException $e) {
                $message = "Erreur lors de la mise à jour : " . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $commande_id = intval($_POST['commande_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM details_commande WHERE commande_id = ?");
            $stmt->execute([$commande_id]);

            $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
            $stmt->execute([$commande_id]);

            $message = "Commande supprimée avec succès !";
            $message_type = 'success';
            header('Location: commands.php?success=' . urlencode($message));
            exit;
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $message_type = 'success';
}

$cart_count = getCartCount($pdo);

$commande_details = null;
if (isset($_GET['details'])) {
    $details_id = intval($_GET['details']);
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.email, u.telephone
        FROM commandes c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$details_id]);
    $commande_details = $stmt->fetch();

    if ($commande_details) {
        $stmt = $pdo->prepare("
            SELECT dc.*, p.nom as produit_nom
            FROM details_commande dc
            JOIN produits p ON dc.produit_id = p.id
            WHERE dc.commande_id = ?
        ");
        $stmt->execute([$details_id]);
        $commande_details['items'] = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion des commandes - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion des Commandes | Admin</title>
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
                        <a href="dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 text-primary transition-all duration-200 font-medium">
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
                        <a href="categories.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-tags"></i>
                            <span>Catégories</span>
                        </a>
                    </li>
                    <li>
                        <a href="stock.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-warehouse"></i>
                            <span>Stock</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="factures.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
                            <i class="fas fa-file-invoice"></i>
                            <span>Factures</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
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
                        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 bg-blue-50 text-primary rounded-lg transition-all duration-200 font-medium">
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
                        <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-tags w-5"></i>
                            <span>Catégories</span>
                        </a>
                    </li>
                    <li>
                        <a href="stock.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-warehouse w-5"></i>
                            <span>Stock</span>
                        </a>
                    </li>
                    <li>
                        <a href="commands.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-shopping-cart w-5"></i>
                            <span>Commandes</span>
                        </a>
                    </li>
                    <li>
                        <a href="factures.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-file-invoice w-5"></i>
                            <span>Factures</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
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
                        <i class="fas fa-shopping-cart text-primary mr-2"></i>
                        Gestion des Commandes
                    </h2>
                    <p class="text-gray-600">Gérez et suivez toutes les commandes de votre boutique</p>
                </div>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Commandes</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_commandes; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-receipt text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">En Attente</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $commandes_en_attente; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">En Cours</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $commandes_en_cours; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shipping-fast text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Livrées</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $commandes_livrees; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-orange-100 text-sm font-medium mb-1">Total Ventes</p>
                        <p class="text-2xl font-bold"><?php echo number_format($chiffre_affaires, 0, ',', ' '); ?> DT</p>
                    </div>
                    <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-euro-sign text-white text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-filter mr-1"></i>Filtrer par statut
                    </label>
                    <select name="statut" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?php echo $filtre_statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="confirmee" <?php echo $filtre_statut === 'confirmee' ? 'selected' : ''; ?>>Confirmée</option>
                        <option value="expedie" <?php echo $filtre_statut === 'expedie' ? 'selected' : ''; ?>>Expédiée</option>
                        <option value="livree" <?php echo $filtre_statut === 'livree' ? 'selected' : ''; ?>>Livrée</option>
                        <option value="annulee" <?php echo $filtre_statut === 'annulee' ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-search mr-1"></i>Rechercher
                    </label>
                    <input type="text" name="recherche" value="<?php echo htmlspecialchars($filtre_recherche); ?>"
                           placeholder="N° commande, client, email..."
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-search mr-2"></i>Filtrer
                    </button>
                    <a href="commands.php" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-all duration-200">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">N° Commande</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php if (count($commandes) > 0): ?>
                            <?php foreach ($commandes as $commande): ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-gray-900 bg-gray-100 px-3 py-1.5 rounded-lg">
                                        #<?php echo $commande['id']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white text-sm font-semibold">
                                                <?php echo strtoupper(substr($commande['username'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($commande['username']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($commande['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900">
                                            <i class="far fa-calendar-alt mr-1 text-gray-400"></i>
                                            <?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?>
                                        </p>
                                        <p class="text-gray-500">
                                            <i class="far fa-clock mr-1 text-gray-400"></i>
                                            <?php echo date('H:i', strtotime($commande['date_commande'])); ?>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-bold text-lg text-orange-600">
                                        <?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> DT
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statut_config = [
                                        'en_attente' => [
                                            'text' => 'En attente',
                                            'icon' => 'fa-clock',
                                            'class' => 'bg-yellow-100 text-yellow-800'
                                        ],
                                        'confirmee' => [
                                            'text' => 'Confirmée',
                                            'icon' => 'fa-check-circle',
                                            'class' => 'bg-green-100 text-green-800'
                                        ],
                                        'expedie' => [
                                            'text' => 'Expédiée',
                                            'icon' => 'fa-shipping-fast',
                                            'class' => 'bg-blue-100 text-blue-800'
                                        ],
                                        'livree' => [
                                            'text' => 'Livrée',
                                            'icon' => 'fa-check-double',
                                            'class' => 'bg-emerald-100 text-emerald-800'
                                        ],
                                        'annulee' => [
                                            'text' => 'Annulée',
                                            'icon' => 'fa-times-circle',
                                            'class' => 'bg-red-100 text-red-800'
                                        ]
                                    ];
                                    $config = $statut_config[$commande['statut']] ?? [
                                        'text' => $commande['statut'],
                                        'icon' => 'fa-question-circle',
                                        'class' => 'bg-gray-100 text-gray-800'
                                    ];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold <?php echo $config['class']; ?>">
                                        <i class="fas <?php echo $config['icon']; ?> mr-1.5"></i>
                                        <?php echo $config['text']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="?details=<?php echo $commande['id']; ?>"
                                           onclick="event.preventDefault(); showDetails(<?php echo htmlspecialchars(json_encode($commande)); ?>);"
                                           class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="showEditModal(<?php echo $commande['id']; ?>, '<?php echo $commande['statut']; ?>')"
                                                class="inline-flex items-center px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="commande_id" value="<?php echo $commande['id']; ?>">
                                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-shopping-cart text-gray-400 text-4xl"></i>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-800 mb-2">Aucune commande trouvée</h3>
                                        <p class="text-gray-600">Il n'y a pas de commandes correspondant à vos critères.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <div id="modalDetails" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-primary to-blue-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-receipt mr-2"></i>
                    <span id="detailsTitle">Détails de la commande</span>
                </h3>
                <button onclick="closeDetailsModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="detailsContent" class="p-6">
            </div>
        </div>
    </div>
    <div id="modalEditStatut" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="sticky top-0 bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier le statut
                </h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action" value="update_statut">
                <input type="hidden" name="commande_id" id="editCommandeId">

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-tasks mr-1"></i>Nouveau statut
                    </label>
                    <select name="nouveau_statut" id="editStatut" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        <option value="en_attente">En attente</option>
                        <option value="confirmee">Confirmée</option>
                        <option value="expedie">Expédiée</option>
                        <option value="livree">Livrée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>
                        Mettre à jour
                    </button>
                    <button type="button" onclick="closeEditModal()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-all duration-200">
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
        function showDetails(commande) {
            fetch(`?details=${commande.id}`)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const content = `
                        <div class="space-y-6">
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">N° Commande</p>
                                        <p class="font-bold text-gray-900">#${commande.id}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Date</p>
                                        <p class="font-semibold text-gray-900">${new Date(commande.date_commande).toLocaleDateString('fr-FR')}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Client</p>
                                        <p class="font-semibold text-gray-900">${commande.username}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Email</p>
                                        <p class="font-semibold text-gray-900">${commande.email}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Montant Total</p>
                                        <p class="font-bold text-orange-600 text-xl">${parseFloat(commande.montant_total).toFixed(2)} DT</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Statut</p>
                                        <p class="font-semibold text-gray-900">${commande.statut}</p>
                                    </div>
                                </div>
                            </div>

                            ${commande.adresse_livraison ? `
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2 flex items-center">
                                    <i class="fas fa-map-marker-alt text-primary mr-2"></i>
                                    Adresse de livraison
                                </h4>
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-gray-700">${commande.adresse_livraison}</p>
                                </div>
                            </div>
                            ` : ''}

                            <div>
                                <h4 class="font-bold text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-box text-primary mr-2"></i>
                                    Articles commandés
                                </h4>
                                <p class="text-gray-600 italic">Chargement des articles...</p>
                            </div>
                        </div>
                    `;

                    document.getElementById('detailsContent').innerHTML = content;
                    document.getElementById('modalDetails').classList.remove('hidden');
                });
        }

        function closeDetailsModal() {
            document.getElementById('modalDetails').classList.add('hidden');
        }

        function showEditModal(commandeId, currentStatut) {
            document.getElementById('editCommandeId').value = commandeId;
            document.getElementById('editStatut').value = currentStatut;
            document.getElementById('modalEditStatut').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('modalEditStatut').classList.add('hidden');
        }

        document.getElementById('modalDetails')?.addEventListener('click', function(e) {
            if (e.target === this) closeDetailsModal();
        });

        document.getElementById('modalEditStatut')?.addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>
</html>

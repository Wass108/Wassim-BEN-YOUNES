<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Filtres et recherche
$search = $_GET['search'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_seuil = $_GET['seuil'] ?? '';

// Récupérer les produits avec statut de stock
$sql = "
    SELECT p.*, c.nom as categorie_nom,
           CASE 
               WHEN p.stock = 0 THEN 'rupture'
               WHEN p.stock <= 5 THEN 'faible'
               ELSE 'disponible'
           END as statut_stock
    FROM produits p
    LEFT JOIN categories c ON p.categorie_id = c.id
    WHERE 1=1
";

$params = [];

if ($search) {
    $sql .= " AND (p.nom LIKE ? OR p.reference_produit LIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($filtre_statut) {
    if ($filtre_statut === 'rupture') {
        $sql .= " AND p.stock = 0";
    } elseif ($filtre_statut === 'faible') {
        $sql .= " AND p.stock > 0 AND p.stock <= 5";
    } elseif ($filtre_statut === 'ok') {
        $sql .= " AND p.stock > 5";
    }
}

$sql .= " ORDER BY p.stock ASC, p.nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

// Statistiques de stock
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE stock = 0");
$ruptures = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE stock > 0 AND stock <= 5");
$stock_faible = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE stock > 5");
$stock_ok = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(stock) as total FROM produits");
$total_stock = $stmt->fetch()['total'] ?? 0;

// Historique des mouvements
$stmt = $pdo->query("
    SELECT ms.*, p.nom as produit_nom, p.reference_produit
    FROM mouvements_stock ms
    JOIN produits p ON ms.produit_id = p.id
    ORDER BY ms.date_mouvement DESC
    LIMIT 20
");
$mouvements = $stmt->fetchAll();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'ajouter_stock') {
        $produit_id = intval($_POST['produit_id'] ?? 0);
        $quantite = intval($_POST['quantite'] ?? 0);
        $commentaire = sanitize($_POST['commentaire'] ?? '');
        
        if ($produit_id > 0 && $quantite > 0) {
            try {
                // Ajouter le mouvement
                $stmt = $pdo->prepare("
                    INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, commentaire)
                    VALUES (?, 'entree', ?, ?)
                ");
                $stmt->execute([$produit_id, $quantite, $commentaire]);
                
                // Mettre à jour le stock du produit
                $stmt = $pdo->prepare("
                    UPDATE produits 
                    SET stock = stock + ?, disponibilite = 1
                    WHERE id = ?
                ");
                $stmt->execute([$quantite, $produit_id]);
                
                $message = "Stock augmenté de $quantite unité(s) avec succès !";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = "Erreur : " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Veuillez remplir tous les champs correctement.";
            $message_type = 'error';
        }
    } elseif ($action === 'retirer_stock') {
        $produit_id = intval($_POST['produit_id'] ?? 0);
        $quantite = intval($_POST['quantite'] ?? 0);
        $commentaire = sanitize($_POST['commentaire'] ?? '');
        
        if ($produit_id > 0 && $quantite > 0) {
            // Vérifier qu'on ne retire pas plus qu'il y a
            $stmt = $pdo->prepare("SELECT stock FROM produits WHERE id = ?");
            $stmt->execute([$produit_id]);
            $stock_actuel = $stmt->fetch()['stock'];
            
            if ($stock_actuel < $quantite) {
                $message = "Stock insuffisant ! Stock actuel : $stock_actuel unité(s)";
                $message_type = 'error';
            } else {
                try {
                    // Ajouter le mouvement
                    $stmt = $pdo->prepare("
                        INSERT INTO mouvements_stock (produit_id, type_mouvement, quantite, commentaire)
                        VALUES (?, 'sortie', ?, ?)
                    ");
                    $stmt->execute([$produit_id, $quantite, $commentaire]);
                    
                    // Mettre à jour le stock
                    $stmt = $pdo->prepare("
                        UPDATE produits 
                        SET stock = stock - ?, disponibilite = IF(stock - ? > 0, 1, 0)
                        WHERE id = ?
                    ");
                    $stmt->execute([$quantite, $quantite, $produit_id]);
                    
                    $message = "Stock diminué de $quantite unité(s) avec succès !";
                    $message_type = 'success';
                } catch (PDOException $e) {
                    $message = "Erreur : " . $e->getMessage();
                    $message_type = 'error';
                }
            }
        } else {
            $message = "Veuillez remplir tous les champs correctement.";
            $message_type = 'error';
        }
    }
    
    header('Location: stock.php');
    exit;
}

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion du Stock - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion du Stock | Admin</title>
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
                        <a href="commands.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
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

    <main class="container mx-auto px-6 lg:px-8 py-8 flex-grow">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-warehouse text-primary mr-2"></i>
                Gestion du Stock
            </h2>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800' : 'bg-red-50 border-l-4 border-red-500 text-red-800'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-xl"></i>
                    <p class="font-semibold"><?php echo htmlspecialchars($message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Stock</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($total_stock); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-boxes text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Stock OK</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stock_ok; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Stock Faible</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $stock_faible; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Ruptures</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $ruptures; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rechercher</label>
                    <form method="GET" class="flex gap-2">
                        <input type="text" name="search" placeholder="Produit ou référence..." value="<?php echo htmlspecialchars($search); ?>" 
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Filtre Statut</label>
                    <form method="GET" id="filterForm">
                        <select name="statut" onchange="document.getElementById('filterForm').submit()" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Tous</option>
                            <option value="ok" <?php echo $filtre_statut === 'ok' ? 'selected' : ''; ?>>Stock OK (> 5)</option>
                            <option value="faible" <?php echo $filtre_statut === 'faible' ? 'selected' : ''; ?>>Stock Faible (≤ 5)</option>
                            <option value="rupture" <?php echo $filtre_statut === 'rupture' ? 'selected' : ''; ?>>Rupture (= 0)</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Liste des produits -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produit</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($produits as $produit): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($produit['nom']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($produit['categorie_nom'] ?? 'N/A'); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-barcode mr-1 text-xs"></i>
                                    <?php echo htmlspecialchars($produit['reference_produit'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <p class="font-bold text-lg"><?php echo $produit['stock']; ?></p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($produit['statut_stock'] === 'rupture'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>Rupture
                                    </span>
                                <?php elseif ($produit['statut_stock'] === 'faible'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Faible
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>OK
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="openModalAjouter(<?php echo $produit['id']; ?>, '<?php echo htmlspecialchars($produit['nom']); ?>')" 
                                            class="inline-flex items-center px-3 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button onclick="openModalRetirer(<?php echo $produit['id']; ?>, '<?php echo htmlspecialchars($produit['nom']); ?>', <?php echo $produit['stock']; ?>)" 
                                            class="inline-flex items-center px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-minus-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Historique des mouvements -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-history text-primary mr-2"></i>
                    Historique des Mouvements (20 derniers)
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produit</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Quantité</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Commentaire</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($mouvements as $mvt): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($mvt['produit_nom']); ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($mvt['reference_produit']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <?php if ($mvt['type_mouvement'] === 'entree'): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-arrow-up mr-1"></i>Entrée
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-arrow-down mr-1"></i>Sortie
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center font-bold"><?php echo $mvt['quantite']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mvt['commentaire']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <?php echo date('d/m/Y H:i', strtotime($mvt['date_mouvement'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Ajouter Stock -->
    <div id="modalAjouter" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Ajouter du Stock
                </h3>
                <button onclick="closeModalAjouter()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="ajouter_stock">
                <input type="hidden" name="produit_id" id="modalAjouterProduitId">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Produit</label>
                    <p id="modalAjouterProduit" class="text-lg font-bold text-primary"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-boxes mr-1"></i>Quantité à ajouter *
                    </label>
                    <input type="number" name="quantite" required min="1" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Commentaire</label>
                    <textarea name="commentaire" rows="2" placeholder="Exemple: Achat fournisseur" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" onclick="closeModalAjouter()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Retirer Stock -->
    <div id="modalRetirer" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-minus-circle mr-2"></i>
                    Retirer du Stock
                </h3>
                <button onclick="closeModalRetirer()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="retirer_stock">
                <input type="hidden" name="produit_id" id="modalRetirerProduitId">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Produit</label>
                    <p id="modalRetirerProduit" class="text-lg font-bold text-primary"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Stock actuel</label>
                    <p id="modalRetirerStockActuel" class="text-lg font-bold text-orange-600"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-boxes mr-1"></i>Quantité à retirer *
                    </label>
                    <input type="number" name="quantite" required min="1" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Raison</label>
                    <textarea name="commentaire" rows="2" placeholder="Exemple: Retour client, casse" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg font-semibold hover:shadow-lg transition">
                        <i class="fas fa-check mr-2"></i>Confirmer
                    </button>
                    <button type="button" onclick="closeModalRetirer()" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                        <i class="fas fa-times mr-2"></i>Annuler
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
        function openModalAjouter(produitId, produitNom) {
            document.getElementById('modalAjouterProduitId').value = produitId;
            document.getElementById('modalAjouterProduit').textContent = produitNom;
            document.getElementById('modalAjouter').classList.remove('hidden');
        }

        function closeModalAjouter() {
            document.getElementById('modalAjouter').classList.add('hidden');
        }

        function openModalRetirer(produitId, produitNom, stock) {
            document.getElementById('modalRetirerProduitId').value = produitId;
            document.getElementById('modalRetirerProduit').textContent = produitNom;
            document.getElementById('modalRetirerStockActuel').textContent = stock + ' unité(s)';
            document.getElementById('modalRetirer').classList.remove('hidden');
        }

        function closeModalRetirer() {
            document.getElementById('modalRetirer').classList.add('hidden');
        }
    </script>
</body>
</html>

<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, c.nom as categorie_nom 
    FROM produits p 
    LEFT JOIN categories c ON p.categorie_id = c.id 
    ORDER BY p.date_creation DESC
");
$produits = $stmt->fetchAll();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $nom = sanitize($_POST['nom'] ?? '');
        $reference_produit = sanitize($_POST['reference_produit'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $prix = floatval($_POST['prix'] ?? 0);
        $type_eclairage = sanitize($_POST['type_eclairage'] ?? '');
        $couleur = sanitize($_POST['couleur'] ?? '');
        $categorie_id = intval($_POST['categorie_id'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $nouveau = isset($_POST['nouveau']) ? 1 : 0;
        $promo = isset($_POST['promo']) ? 1 : 0;
        
        if (empty($nom) || $prix <= 0) {
            $message = "Veuillez remplir tous les champs obligatoires.";
            $message_type = 'error';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO produits (nom, reference_produit, description, prix, type_eclairage, couleur, categorie_id, stock, nouveau, promo, disponibilite) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $disponibilite = ($stock > 0) ? 1 : 0;
                    $stmt->execute([$nom, $reference_produit, $description, $prix, $type_eclairage, $couleur, $categorie_id, $stock, $nouveau, $promo, $disponibilite]);
                    $message = "Produit ajouté avec succès !";
                    $message_type = 'success';
                } else {
                    $produit_id = intval($_POST['produit_id'] ?? 0);
                    $stmt = $pdo->prepare("
                        UPDATE produits 
                        SET nom = ?, reference_produit = ?, description = ?, prix = ?, type_eclairage = ?, couleur = ?, categorie_id = ?, stock = ?, nouveau = ?, promo = ?, disponibilite = ?
                        WHERE id = ?
                    ");
                    $disponibilite = ($stock > 0) ? 1 : 0;
                    $stmt->execute([$nom, $reference_produit, $description, $prix, $type_eclairage, $couleur, $categorie_id, $stock, $nouveau, $promo, $disponibilite, $produit_id]);
                    $message = "Produit modifié avec succès !";
                    $message_type = 'success';
                }
                
                header('Location: products.php?success=' . urlencode($message));
                exit;
            } catch (PDOException $e) {
                $message = "Erreur : " . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $produit_id = intval($_POST['produit_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
            $stmt->execute([$produit_id]);
            $message = "Produit supprimé avec succès !";
            $message_type = 'success';
            header('Location: products.php?success=' . urlencode($message));
            exit;
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

$produit_edit = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$edit_id]);
    $produit_edit = $stmt->fetch();
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
    <meta name="description" content="Gestion des produits - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion des Produits | Admin</title>
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
                        <a href="products.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-primary transition-all duration-200 font-medium">
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
                        <a href="stock.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg text-gray-600 hover:text-primary hover:bg-blue-50 transition-all duration-200 font-medium">
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
                        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-chart-line w-5"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php" class="flex items-center space-x-3 px-4 py-3 bg-blue-50 text-primary rounded-lg transition-all duration-200 font-medium">
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
                        <a href="stock.php" class="flex items-center space-x-3 px-4 py-3 text-gray-600 hover:bg-blue-50 hover:text-primary rounded-lg transition-all duration-200 font-medium">
                            <i class="fas fa-warehouse w-5"></i>
                            <span>Stock</span>
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
                        <i class="fas fa-box text-primary mr-2"></i>
                        Gestion des Produits
                    </h2>
                </div>
                <button onclick="document.getElementById('modalProduit').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter un produit
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
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Produits</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo count($produits); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">En Stock</p>
                        <p class="text-3xl font-bold text-green-600">
                            <?php echo count(array_filter($produits, fn($p) => $p['stock'] > 0)); ?>
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Rupture Stock</p>
                        <p class="text-3xl font-bold text-red-600">
                            <?php echo count(array_filter($produits, fn($p) => $p['stock'] == 0)); ?>
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Catégories</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo count($categories); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-tags text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Produit</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catégorie</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Badges</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <?php foreach ($produits as $produit): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <td class="px-6 py-4">
                                <?php if (!empty($produit['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($produit['image']); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="h-12 w-12 rounded-lg object-cover">
                                <?php else: ?>
                                    <div class="h-12 w-12 rounded-lg bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($produit['nom']); ?></p>
                                    <p class="text-sm text-gray-500 line-clamp-1"><?php echo htmlspecialchars($produit['description']); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                    <i class="fas fa-barcode mr-1.5 text-xs"></i>
                                    <?php echo htmlspecialchars($produit['reference_produit'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-lg text-orange-600">
                                    <?php echo number_format($produit['prix'], 2, ',', ' '); ?> DT
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($produit['stock'] > 5): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1.5"></i>
                                        <?php echo $produit['stock']; ?>
                                    </span>
                                <?php elseif ($produit['stock'] > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i>
                                        <?php echo $produit['stock']; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1.5"></i>
                                        Rupture
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-tag mr-1.5 text-xs"></i>
                                    <?php echo htmlspecialchars($produit['categorie_nom'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <?php if (isset($produit['nouveau']) && $produit['nouveau']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-star mr-1"></i>Nouveau
                                        </span>
                                    <?php endif; ?>
                                    <?php if (isset($produit['promo']) && $produit['promo']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            <i class="fas fa-percent mr-1"></i>Promo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="?edit=<?php echo $produit['id']; ?>" 
                                       onclick="editProduct(<?php echo htmlspecialchars(json_encode($produit)); ?>)"
                                       class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <div id="modalProduit" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-primary to-blue-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-box mr-2"></i>
                    <span id="modalTitle">Ajouter un produit</span>
                </h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form id="productForm" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="produit_id" id="produitId">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-tag mr-1"></i>Nom du produit *
                        </label>
                        <input type="text" name="nom" id="produitNom" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-barcode mr-1"></i>Référence
                        </label>
                        <input type="text" name="reference_produit" id="produitReference" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-1"></i>Description
                    </label>
                    <textarea name="description" id="produitDescription" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-euro-sign mr-1"></i>Prix (DT) *
                        </label>
                        <input type="number" name="prix" id="produitPrix" step="0.01" min="0" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-image mr-1"></i>Image
                        </label>
                        <div class="space-y-2">
                            <input type="hidden" name="image" id="produitImage" value="">
                            <div id="imagePreview" class="hidden mt-2">
                                <img id="previewImg" src="" alt="Aperçu" class="max-w-32 max-h-32 rounded-lg border border-gray-300">
                            </div>
                            <div class="flex gap-2">
                                <button type="button" id="importImageBtn" onclick="document.getElementById('imageFile').click()" 
                                        class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                    <i class="fas fa-upload mr-1"></i>Importer
                                </button>
                                <span id="imageStatus" class="text-sm text-gray-500 self-center">Aucune image sélectionnée</span>
                            </div>
                            <input type="file" id="imageFile" accept="image/*" class="hidden">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-boxes mr-1"></i>Stock
                        </label>
                        <input type="number" name="stock" id="produitStock" min="0" value="0" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-folder mr-1"></i>Catégorie
                        </label>
                        <select name="categorie_id" id="produitCategorie" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                            <option value="0">Sans catégorie</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-lightbulb mr-1"></i>Type d'éclairage
                        </label>
                        <input type="text" name="type_eclairage" id="produitTypeEclairage" placeholder="ex: LED, Halogène, Incandescence" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-palette mr-1"></i>Couleur
                        </label>
                        <input type="text" name="couleur" id="produitCouleur" placeholder="ex: Blanc, Noir, Chromé" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition">
                    </div>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="nouveau" id="produitNouveau" class="w-5 h-5 text-primary rounded focus:ring-primary">
                        <span class="text-sm font-medium text-gray-700">
                            <i class="fas fa-star text-green-600 mr-1"></i>Nouveau produit
                        </span>
                    </label>
                    
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="promo" id="produitPromo" class="w-5 h-5 text-primary rounded focus:ring-primary">
                        <span class="text-sm font-medium text-gray-700">
                            <i class="fas fa-percent text-red-600 mr-1"></i>En promotion
                        </span>
                    </label>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>
                        <span id="submitText">Ajouter le produit</span>
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
            document.getElementById('modalProduit').classList.add('hidden');
            document.getElementById('formAction').value = 'add';
            document.getElementById('produitId').value = '';
            document.getElementById('modalTitle').textContent = 'Ajouter un produit';
            document.getElementById('submitText').textContent = 'Ajouter le produit';
            document.getElementById('productForm').reset();
            document.getElementById('imageFile').value = '';
            updateImagePreview('');
            updateImageStatus('');
        }

        function editProduct(produit) {
            event.preventDefault();
            document.getElementById('modalProduit').classList.remove('hidden');
            document.getElementById('formAction').value = 'edit';
            document.getElementById('produitId').value = produit.id;
            document.getElementById('produitNom').value = produit.nom;
            document.getElementById('produitReference').value = produit.reference_produit || '';
            document.getElementById('produitDescription').value = produit.description || '';
            document.getElementById('produitPrix').value = produit.prix;
            document.getElementById('produitImage').value = produit.image || '';
            document.getElementById('produitCategorie').value = produit.categorie_id || 0;
            document.getElementById('produitStock').value = produit.stock;
            document.getElementById('produitTypeEclairage').value = produit.type_eclairage || '';
            document.getElementById('produitCouleur').value = produit.couleur || '';
            document.getElementById('produitNouveau').checked = produit.nouveau == 1;
            document.getElementById('produitPromo').checked = produit.promo == 1;
            document.getElementById('modalTitle').textContent = 'Modifier le produit';
            document.getElementById('submitText').textContent = 'Modifier le produit';
            
            // Mettre à jour l'aperçu et le statut si une image existe
            updateImagePreview(produit.image || '');
            updateImageStatus(produit.image || '');
        }

        function updateImagePreview(imagePath) {
            const previewDiv = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            
            if (imagePath && imagePath.trim() !== '') {
                previewImg.src = imagePath;
                previewDiv.classList.remove('hidden');
            } else {
                previewDiv.classList.add('hidden');
            }
        }

        function updateImageStatus(imagePath) {
            const status = document.getElementById('imageStatus');
            if (imagePath && imagePath.trim() !== '') {
                status.textContent = 'Image prête à être enregistrée';
            } else {
                status.textContent = 'Aucune image sélectionnée';
            }
        }

        document.getElementById('imageFile').addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) {
                return;
            }

            const importBtn = document.getElementById('importImageBtn');
            const originalText = importBtn.innerHTML;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Importation...';
            importBtn.disabled = true;

            const formData = new FormData();
            formData.append('image_file', file);

            try {
                const response = await fetch('import_image.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    document.getElementById('produitImage').value = result.image_path;
                    updateImagePreview(result.image_path);
                    updateImageStatus(result.image_path);
                    alert('Image importée avec succès !');
                } else {
                    alert('Erreur lors de l\'importation : ' + result.error);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'importation de l\'image.');
            } finally {
                importBtn.innerHTML = originalText;
                importBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

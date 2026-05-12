<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

// Récupérer toutes les catégories avec nombre de produits
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) as nb_produits
    FROM categories c
    LEFT JOIN produits p ON c.id = p.categorie_id
    GROUP BY c.id, c.nom, c.description
    ORDER BY c.nom ASC
");
$categories = $stmt->fetchAll();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $nom = sanitize($_POST['nom'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($nom)) {
            $message = "Le nom de la catégorie est obligatoire.";
            $message_type = 'error';
        } else {
            try {
                if ($action === 'add') {
                    // Vérifier que la catégorie n'existe pas déjà
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ?");
                    $stmt->execute([$nom]);
                    
                    if ($stmt->fetch()) {
                        $message = "Une catégorie avec ce nom existe déjà.";
                        $message_type = 'error';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO categories (nom, description) 
                            VALUES (?, ?)
                        ");
                        $stmt->execute([$nom, $description]);
                        $message = "Catégorie créée avec succès !";
                        $message_type = 'success';
                    }
                } else {
                    $categorie_id = intval($_POST['categorie_id'] ?? 0);
                    
                    if ($categorie_id > 0) {
                        // Vérifier que le nouveau nom n'existe pas (autre catégorie)
                        $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = ? AND id != ?");
                        $stmt->execute([$nom, $categorie_id]);
                        
                        if ($stmt->fetch()) {
                            $message = "Une autre catégorie avec ce nom existe déjà.";
                            $message_type = 'error';
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE categories 
                                SET nom = ?, description = ?
                                WHERE id = ?
                            ");
                            $stmt->execute([$nom, $description, $categorie_id]);
                            $message = "Catégorie modifiée avec succès !";
                            $message_type = 'success';
                        }
                    }
                }
            } catch (PDOException $e) {
                $message = "Erreur : " . $e->getMessage();
                $message_type = 'error';
            }
        }
    } elseif ($action === 'delete') {
        $categorie_id = intval($_POST['categorie_id'] ?? 0);
        
        if ($categorie_id > 0) {
            try {
                // Vérifier que la catégorie n'a pas de produits
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?");
                $stmt->execute([$categorie_id]);
                $count = $stmt->fetch()['total'];
                
                if ($count > 0) {
                    $message = "Impossible de supprimer cette catégorie : elle contient $count produit(s). Réassignez d'abord les produits.";
                    $message_type = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$categorie_id]);
                    $message = "Catégorie supprimée avec succès !";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = "Erreur : " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
    
    header('Location: categories.php');
    exit;
}

// Récupérer la catégorie à modifier si edit
$categorie_edit = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $categorie_edit = $stmt->fetch();
}

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $message_type = 'success';
}

// Statistiques
$stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
$total_categories = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits");
$total_produits = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT c.id, COUNT(p.id) as nb_produits
    FROM categories c
    LEFT JOIN produits p ON c.id = p.categorie_id
    GROUP BY c.id
");
$cat_stats = $stmt->fetchAll();
$categories_avec_produits = count(array_filter($cat_stats, fn($c) => $c['nb_produits'] > 0));

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion des Catégories - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion des Catégories | Admin</title>
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
                        <a href="categories.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-primary transition-all duration-200 font-medium">
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
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-6 lg:px-8 py-8 flex-grow">
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-tags text-primary mr-2"></i>
                        Gestion des Catégories
                    </h2>
                </div>
                <button onclick="document.getElementById('modalCategorie').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter une catégorie
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

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Catégories</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_categories; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-tags text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Catégories Utilisées</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $categories_avec_produits; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Produits</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $total_produits; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des catégories -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if (empty($categories)): ?>
                <div class="p-12 text-center">
                    <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500 text-lg mb-6">Aucune catégorie trouvée</p>
                    <button onclick="document.getElementById('modalCategorie').classList.remove('hidden')" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>
                        Créer une catégorie
                    </button>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catégorie</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Produits</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-tag text-white"></i>
                                        </div>
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($cat['nom']); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-gray-600 line-clamp-2"><?php echo htmlspecialchars($cat['description']); ?></p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($cat['nb_produits'] > 0): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <i class="fas fa-box mr-1"></i>
                                            <?php echo $cat['nb_produits']; ?> produit<?php echo $cat['nb_produits'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-600">
                                            <i class="fas fa-minus-circle mr-1"></i>
                                            Vide
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="?edit=<?php echo $cat['id']; ?>" 
                                           onclick="editCategorie(<?php echo htmlspecialchars(json_encode($cat)); ?>)"
                                           class="inline-flex items-center px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-semibold transition-all duration-200">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?<?php echo $cat['nb_produits'] > 0 ? '\n\nATTENTION: Cette catégorie contient ' . $cat['nb_produits'] . ' produit(s)!' : ''; ?>')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="categorie_id" value="<?php echo $cat['id']; ?>">
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
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Catégorie -->
    <div id="modalCategorie" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="sticky top-0 bg-gradient-to-r from-primary to-blue-600 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-tag mr-2"></i>
                    <span id="modalTitle">Ajouter une catégorie</span>
                </h3>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="categorie_id" id="categorieId">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tag mr-1"></i>Nom de la catégorie *
                    </label>
                    <input type="text" name="nom" id="categorieNom" required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                           placeholder="ex: Lustres, Appliques, Lampadaires">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-1"></i>Description
                    </label>
                    <textarea name="description" id="categorieDescription" rows="3" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition"
                              placeholder="Description de la catégorie (optionnel)"></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all duration-200">
                        <i class="fas fa-check mr-2"></i>
                        <span id="submitText">Ajouter</span>
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
            document.getElementById('modalCategorie').classList.add('hidden');
            document.getElementById('formAction').value = 'add';
            document.getElementById('categorieId').value = '';
            document.getElementById('modalTitle').textContent = 'Ajouter une catégorie';
            document.getElementById('submitText').textContent = 'Ajouter';
            document.querySelector('form').reset();
        }

        function editCategorie(categorie) {
            event.preventDefault();
            document.getElementById('modalCategorie').classList.remove('hidden');
            document.getElementById('formAction').value = 'edit';
            document.getElementById('categorieId').value = categorie.id;
            document.getElementById('categorieNom').value = categorie.nom;
            document.getElementById('categorieDescription').value = categorie.description || '';
            document.getElementById('modalTitle').textContent = 'Modifier la catégorie';
            document.getElementById('submitText').textContent = 'Modifier';
        }
    </script>
</body>
</html>

<?php
require_once '../db/db.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Récupérer les commandes
$stmt = $pdo->query("
    SELECT c.*, u.username, u.email, u.nom, u.prenom, u.adresse, u.ville, u.code_postal
    FROM commandes c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.date_commande DESC
");
$commandes = $stmt->fetchAll();

// Récupérer une commande spécifique pour l'affichage
$commande_details = null;
if (isset($_GET['view'])) {
    $commande_id = intval($_GET['view']);
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.email, u.nom, u.prenom, u.adresse, u.ville, u.code_postal, u.telephone
        FROM commandes c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commande_id]);
    $commande_details = $stmt->fetch();
    
    if ($commande_details) {
        $stmt = $pdo->prepare("
            SELECT dc.*, p.nom as produit_nom, p.reference_produit
            FROM details_commande dc
            JOIN produits p ON dc.produit_id = p.id
            WHERE dc.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        $commande_details['items'] = $stmt->fetchAll();
    }
}

// Statistiques
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(montant_total) as montant_total
    FROM commandes
");
$stats = $stmt->fetch();

$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM commandes 
    WHERE statut IN ('en_attente', 'confirmee')
");
$commandes_en_attente = $stmt->fetch()['total'];

$cart_count = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestion des Factures - Administration">
    <meta name="robots" content="noindex, nofollow">
    <title>Gestion des Factures | Admin</title>
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
        @media print {
            .no-print { display: none; }
            body { background: white; }
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
                        <a href="factures.php" class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-primary transition-all duration-200 font-medium">
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
        <?php if (!$commande_details): ?>
            <!-- Liste des Factures -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-file-invoice text-primary mr-2"></i>
                    Gestion des Factures
                </h2>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Commandes</p>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Montant Total</p>
                            <p class="text-3xl font-bold text-green-600">
                                <?php echo number_format($stats['montant_total'] ?? 0, 2, ',', ' '); ?> €
                            </p>
                        </div>
                        <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-euro-sign text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">En Attente</p>
                            <p class="text-3xl font-bold text-orange-600"><?php echo $commandes_en_attente; ?></p>
                        </div>
                        <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-hourglass-half text-orange-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Commande</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Paiement</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php foreach ($commandes as $cmd): ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">#<?php echo str_pad($cmd['id'], 5, '0', STR_PAD_LEFT); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($cmd['username']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($cmd['email']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-lg text-orange-600">
                                        <?php echo number_format($cmd['montant_total'], 2, ',', ' '); ?> €
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                    $statut_colors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-800',
                                        'confirmee' => 'bg-blue-100 text-blue-800',
                                        'en_livraison' => 'bg-purple-100 text-purple-800',
                                        'livree' => 'bg-green-100 text-green-800',
                                        'annulee' => 'bg-red-100 text-red-800'
                                    ];
                                    $color = $statut_colors[$cmd['statut']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?php echo $color; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cmd['statut'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $mode_colors = [
                                        'livraison' => 'bg-indigo-100 text-indigo-800',
                                        'carte' => 'bg-green-100 text-green-800',
                                        'virement' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $mode_color = $mode_colors[$cmd['mode_paiement']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold <?php echo $mode_color; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $cmd['mode_paiement'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo date('d/m/Y', strtotime($cmd['date_commande'])); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="?view=<?php echo $cmd['id']; ?>" 
                                       class="inline-flex items-center px-3 py-2 bg-primary hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition">
                                        <i class="fas fa-eye mr-1"></i>Voir
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage Facture -->
            <div class="no-print mb-6">
                <a href="factures.php" class="inline-flex items-center px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-8 max-w-4xl mx-auto">
                <!-- En-tête Facture -->
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-4xl font-bold text-primary mb-1">FACTURE</h1>
                            <p class="text-gray-600">
                                N° <span class="font-bold text-xl"><?php echo str_pad($commande_details['id'], 5, '0', STR_PAD_LEFT); ?></span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600 mb-1"><strong>Date d'émission:</strong></p>
                            <p class="text-lg font-bold"><?php echo date('d/m/Y', strtotime($commande_details['date_commande'])); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-gray-600 text-sm mb-3"><strong>DE:</strong></p>
                            <div class="border-l-4 border-primary pl-3">
                                <p class="font-bold text-lg">Lustre & Lumière</p>
                                <p class="text-gray-600 text-sm">Vos coordonnées commerciales</p>
                                <p class="text-gray-600 text-sm">Ville, Code Postal</p>
                                <p class="text-gray-600 text-sm">contact@lustrelumiere.com</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-3"><strong>POUR:</strong></p>
                            <div class="border-l-4 border-orange-500 pl-3">
                                <p class="font-bold text-lg"><?php echo htmlspecialchars($commande_details['prenom'] . ' ' . $commande_details['nom']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($commande_details['adresse']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($commande_details['code_postal'] . ' ' . $commande_details['ville']); ?></p>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($commande_details['email']); ?></p>
                                <?php if (!empty($commande_details['telephone'])): ?>
                                    <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($commande_details['telephone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des articles -->
                <div class="mb-6">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th class="px-4 py-3 text-left font-semibold">Référence</th>
                                <th class="px-4 py-3 text-left font-semibold">Description</th>
                                <th class="px-4 py-3 text-center font-semibold w-20">Quantité</th>
                                <th class="px-4 py-3 text-right font-semibold w-32">Prix U.</th>
                                <th class="px-4 py-3 text-right font-semibold w-32">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commande_details['items'] as $item): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($item['reference_produit'] ?? 'N/A'); ?></td>
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($item['produit_nom']); ?></td>
                                <td class="px-4 py-3 text-center font-bold"><?php echo $item['quantite']; ?></td>
                                <td class="px-4 py-3 text-right font-bold"><?php echo number_format($item['prix_unitaire'], 2, ',', ' '); ?> €</td>
                                <td class="px-4 py-3 text-right font-bold">
                                    <?php echo number_format($item['prix_unitaire'] * $item['quantite'], 2, ',', ' '); ?> €
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Résumé financier -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div></div>
                    <div class="space-y-3">
                        <div class="flex justify-between pb-2 border-b border-gray-300">
                            <span class="text-gray-600">Sous-total:</span>
                            <span class="font-bold"><?php echo number_format($commande_details['montant_total'] * 0.9, 2, ',', ' '); ?> €</span>
                        </div>
                        <div class="flex justify-between pb-2 border-b border-gray-300">
                            <span class="text-gray-600">TVA (20%):</span>
                            <span class="font-bold"><?php echo number_format($commande_details['montant_total'] * 0.1, 2, ',', ' '); ?> €</span>
                        </div>
                        <div class="flex justify-between pb-2 pt-2 bg-gradient-to-r from-primary to-blue-600 text-white px-4 py-2 rounded-lg">
                            <span class="font-bold text-lg">Montant Total:</span>
                            <span class="font-bold text-xl"><?php echo number_format($commande_details['montant_total'], 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                </div>

                <!-- Informations de commande -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm mb-1"><strong>Statut de la commande:</strong></p>
                            <p class="font-bold text-lg capitalize"><?php echo str_replace('_', ' ', $commande_details['statut']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1"><strong>Mode de paiement:</strong></p>
                            <p class="font-bold text-lg capitalize"><?php echo str_replace('_', ' ', $commande_details['mode_paiement']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm mb-1"><strong>Adresse de livraison:</strong></p>
                            <p class="text-sm"><?php echo htmlspecialchars(substr($commande_details['adresse_livraison'], 0, 50)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Conditions et mentions -->
                <div class="border-t border-gray-200 pt-6 text-center text-gray-600 text-sm">
                    <p class="mb-2">Merci pour votre commande!</p>
                    <p>Pour toute question, veuillez contacter notre service client à contact@lustrelumiere.com</p>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-center gap-4 mt-8 no-print">
                <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-primary hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                    <i class="fas fa-print mr-2"></i>Imprimer
                </button>
                <a href="factures.php" class="inline-flex items-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition">
                    <i class="fas fa-times mr-2"></i>Fermer
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-8 no-print">
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
</body>
</html>

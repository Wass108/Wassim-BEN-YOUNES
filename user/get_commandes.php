<?php
require_once '../db/db.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_commandes = $stmt->fetchColumn();
$total_pages = ceil($total_commandes / $per_page);

$stmt = $pdo->prepare("SELECT * FROM commandes WHERE user_id = ? ORDER BY date_commande DESC LIMIT ? OFFSET ?");
$stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$commandes = $stmt->fetchAll();

$statut_config = [
    'en_attente' => ['text' => '⏳ En attente', 'class' => 'bg-yellow-100 text-yellow-800'],
    'confirmee' => ['text' => '✅ Confirmée', 'class' => 'bg-green-100 text-green-800'],
    'expedie' => ['text' => '📦 Expédiée', 'class' => 'bg-blue-100 text-blue-800'],
    'livree' => ['text' => '✓ Livrée', 'class' => 'bg-green-200 text-green-900']
];

ob_start();
?>
<?php if (count($commandes) > 0): ?>
    <div class="overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gradient-to-r from-primary to-amber-800 text-white">
                <tr>
                    <th class="px-3 py-2 text-left rounded-tl-lg">N° Commande</th>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Montant</th>
                    <th class="px-3 py-2 text-left rounded-tr-lg">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($commandes as $commande): ?>
                <tr class="table-row-hover">
                    <td class="px-3 py-2 font-semibold text-gray-900">#<?php echo $commande['id']; ?></td>
                    <td class="px-3 py-2 text-gray-700"><?php echo date('d/m/Y à H:i', strtotime($commande['date_commande'])); ?></td>
                    <td class="px-3 py-2 font-bold text-secondary"><?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €</td>
                    <td class="px-3 py-2">
                        <?php
                        $config = $statut_config[$commande['statut']] ?? ['text' => $commande['statut'], 'class' => 'bg-gray-100 text-gray-800'];
                        ?>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold <?php echo $config['class']; ?>">
                            <?php echo $config['text']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-center items-center space-x-2">
            <?php if ($page > 1): ?>
                <button onclick="loadCommandes(<?php echo $page - 1; ?>)" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-amber-900 transition text-xs font-semibold">
                    ← Précédent
                </button>
            <?php endif; ?>

            <div class="flex space-x-1">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <button onclick="loadCommandes(<?php echo $i; ?>)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold transition <?php echo $i === $page ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
            </div>

            <?php if ($page < $total_pages): ?>
                <button onclick="loadCommandes(<?php echo $page + 1; ?>)" class="px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-amber-900 transition text-xs font-semibold">
                    Suivant →
                </button>
            <?php endif; ?>
        </div>

        <div class="mt-2 text-center text-xs text-gray-600">
            Page <?php echo $page; ?> sur <?php echo $total_pages; ?> (<?php echo $total_commandes; ?> commande<?php echo $total_commandes > 1 ? 's' : ''; ?> au total)
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="glass-effect border border-blue-200 text-blue-800 px-6 py-5 rounded-2xl text-center animate-fadeInUp">
        <div class="flex items-center justify-center mb-3">
            <svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
        </div>
        <p class="mb-3">
            Vous n'avez pas encore passé de commande.
        </p>
        <a href="../home.php#produits" class="inline-block bg-gradient-to-r from-primary to-amber-700 hover:from-amber-800 hover:to-primary text-white font-bold px-6 py-2 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg btn-hover-effect shine-effect text-sm">
            <span class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
                Découvrir nos produits
            </span>
        </a>
    </div>
<?php endif; ?>

<?php
$html = ob_get_clean();
echo json_encode([
    'html' => $html,
    'total_commandes' => $total_commandes,
    'total_pages' => $total_pages,
    'current_page' => $page
]);

<?php
/**
 * Moteur de Recommandation de Produits
 * Basé sur : filtrage collaboratif + catégorie + popularité
 */

require_once __DIR__ . '/config.php';

class ProductRecommender {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Recommande des produits à un utilisateur connecté
     * Méthode : produits achetés par des clients ayant un comportement similaire
     */
    public function recommendForUser($user_id, $limit = RECO_MAX_PRODUCTS) {
        // 1. Récupérer les produits déjà achetés par l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT cd.produit_id 
            FROM commande_details cd
            JOIN commandes c ON cd.commande_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $user_products = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($user_products)) {
            // Nouveau client → recommander les best-sellers
            return $this->getBestSellers($limit);
        }

        // 2. Trouver les utilisateurs ayant acheté les mêmes produits
        $placeholders = implode(',', array_fill(0, count($user_products), '?'));
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT c.user_id 
            FROM commande_details cd
            JOIN commandes c ON cd.commande_id = c.id
            WHERE cd.produit_id IN ($placeholders)
            AND c.user_id != ?
        ");
        $params = array_merge($user_products, [$user_id]);
        $stmt->execute($params);
        $similar_users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($similar_users)) {
            return $this->getBestSellers($limit);
        }

        // 3. Récupérer les produits achetés par ces utilisateurs (hors ceux déjà achetés)
        $user_placeholders = implode(',', array_fill(0, count($similar_users), '?'));
        $product_placeholders = implode(',', array_fill(0, count($user_products), '?'));

        $sql = "
            SELECT p.*, cat.nom as categorie_nom, COUNT(*) as score
            FROM commande_details cd
            JOIN commandes c ON cd.commande_id = c.id
            JOIN produits p ON cd.produit_id = p.id
            LEFT JOIN categories cat ON p.categorie_id = cat.id
            WHERE c.user_id IN ($user_placeholders)
            AND cd.produit_id NOT IN ($product_placeholders)
            AND p.stock > 0
            GROUP BY p.id
            ORDER BY score DESC
            LIMIT ?
        ";
        $stmt = $this->pdo->prepare($sql);
        $params = array_merge($similar_users, $user_products, [$limit]);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recommande des produits similaires à un produit donné
     */
    public function recommendSimilar($product_id, $limit = RECO_MAX_PRODUCTS) {
        // Récupérer le produit cible
        $stmt = $this->pdo->prepare("SELECT * FROM produits WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) return [];

        // Produits de la même catégorie + fourchette de prix similaire
        $prix_min = $product['prix'] * 0.6;
        $prix_max = $product['prix'] * 1.5;

        $stmt = $this->pdo->prepare("
            SELECT p.*, c.nom as categorie_nom
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id = c.id
            WHERE p.categorie_id = ?
            AND p.id != ?
            AND p.stock > 0
            AND p.prix BETWEEN ? AND ?
            ORDER BY ABS(p.prix - ?) ASC
            LIMIT ?
        ");
        $stmt->execute([
            $product['categorie_id'], 
            $product_id, 
            $prix_min, 
            $prix_max, 
            $product['prix'], 
            $limit
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Produits "souvent achetés ensemble"
     */
    public function frequentlyBoughtTogether($product_id, $limit = 3) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.nom as categorie_nom, COUNT(*) as freq
            FROM commande_details cd1
            JOIN commande_details cd2 ON cd1.commande_id = cd2.commande_id
            JOIN produits p ON cd2.produit_id = p.id
            LEFT JOIN categories c ON p.categorie_id = c.id
            WHERE cd1.produit_id = ?
            AND cd2.produit_id != ?
            AND p.stock > 0
            GROUP BY p.id
            ORDER BY freq DESC
            LIMIT ?
        ");
        $stmt->execute([$product_id, $product_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Best-sellers (pour nouveaux utilisateurs)
     */
    public function getBestSellers($limit = RECO_MAX_PRODUCTS) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.nom as categorie_nom, COALESCE(SUM(cd.quantite), 0) as ventes
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id = c.id
            LEFT JOIN commande_details cd ON p.id = cd.produit_id
            WHERE p.stock > 0
            GROUP BY p.id
            ORDER BY ventes DESC, p.date_creation DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ===== USAGE DIRECT (API AJAX) =====
if (basename($_SERVER['PHP_SELF']) === 'recommendations.php') {
    require_once __DIR__ . '/../db/db.php';
    header('Content-Type: application/json');

    $recommender = new ProductRecommender($pdo);
    $type = $_GET['type'] ?? 'user';
    $id = intval($_GET['id'] ?? 0);

    switch ($type) {
        case 'user':
            $results = $recommender->recommendForUser($id);
            break;
        case 'similar':
            $results = $recommender->recommendSimilar($id);
            break;
        case 'together':
            $results = $recommender->frequentlyBoughtTogether($id);
            break;
        case 'bestsellers':
        default:
            $results = $recommender->getBestSellers();
    }

    echo json_encode(['success' => true, 'products' => $results]);
}
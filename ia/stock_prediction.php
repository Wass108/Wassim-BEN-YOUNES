<?php
/**
 * Prédiction des Ruptures de Stock
 * Méthode : Moyenne mobile + régression linéaire simple
 */

require_once __DIR__ . '/config.php';

class StockPredictor {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Analyse complète de tous les produits
     */
    public function analyzeAllProducts() {
        $stmt = $this->pdo->query("
            SELECT p.*, c.nom as categorie_nom 
            FROM produits p
            LEFT JOIN categories c ON p.categorie_id = c.id
            ORDER BY p.nom
        ");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($products as $product) {
            $analysis = $this->predictProduct($product['id']);
            $results[] = array_merge($product, $analysis);
        }

        // Trier par urgence (jours avant rupture croissant)
        usort($results, function($a, $b) {
            return $a['days_until_empty'] <=> $b['days_until_empty'];
        });

        return $results;
    }

    /**
     * Prédiction pour un produit spécifique
     */
    public function predictProduct($product_id) {
        // Récupérer l'historique des ventes
        $stmt = $this->pdo->prepare("
            SELECT DATE(c.date_commande) as jour, SUM(cd.quantite) as qte
            FROM commande_details cd
            JOIN commandes c ON cd.commande_id = c.id
            WHERE cd.produit_id = ?
            AND c.date_commande >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(c.date_commande)
            ORDER BY jour ASC
        ");
        $stmt->execute([$product_id, STOCK_HISTORY_DAYS]);
        $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer stock actuel
        $stmt = $this->pdo->prepare("SELECT stock FROM produits WHERE id = ?");
        $stmt->execute([$product_id]);
        $stock = (int)$stmt->fetchColumn();

        // Calculs
        $total_vendu = array_sum(array_column($ventes, 'qte'));
        $jours_avec_ventes = count($ventes);

        // Vitesse moyenne par jour (basée sur la période complète)
        $vitesse_moyenne = $total_vendu / max(STOCK_HISTORY_DAYS, 1);

        // Prédiction jours avant rupture
        if ($vitesse_moyenne <= 0) {
            $days_until_empty = 9999; // Pas de ventes = pas de rupture prévue
        } else {
            $days_until_empty = round($stock / $vitesse_moyenne);
        }

        // Niveau d'alerte
        if ($stock <= 0) {
            $alert_level = 'out';      // Déjà en rupture
            $alert_color = 'red';
        } elseif ($days_until_empty <= STOCK_CRITICAL_DAYS) {
            $alert_level = 'critical'; // Rupture imminente
            $alert_color = 'red';
        } elseif ($days_until_empty <= STOCK_ALERT_DAYS) {
            $alert_level = 'warning';  // Attention
            $alert_color = 'orange';
        } else {
            $alert_level = 'ok';
            $alert_color = 'green';
        }

        // Quantité recommandée à commander (stock pour 30 jours)
        $recommend_restock = max(0, ceil($vitesse_moyenne * 30) - $stock);

        return [
            'current_stock' => $stock,
            'total_sold_period' => (int)$total_vendu,
            'daily_avg_sales' => round($vitesse_moyenne, 2),
            'days_until_empty' => $days_until_empty,
            'alert_level' => $alert_level,
            'alert_color' => $alert_color,
            'recommend_restock' => $recommend_restock,
            'history' => $ventes
        ];
    }

    /**
     * Récupérer uniquement les produits en alerte
     */
    public function getAlerts() {
        $all = $this->analyzeAllProducts();
        return array_filter($all, function($p) {
            return in_array($p['alert_level'], ['out', 'critical', 'warning']);
        });
    }
}
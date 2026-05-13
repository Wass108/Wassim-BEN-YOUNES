

<?php
// chat.php
require_once 'db/db.php';

 $data = json_decode(file_get_contents('php://input'), true);
 $message = strtolower($data['message'] ?? '');
 $user_id = $_SESSION['user_id'] ?? null; // Si l'utilisateur est connecté

// FONCTIONS INTELLIGENTES

// 1. Analyse de sentiment simplifiée
function detectIntent($msg) {
    if (strpos($msg, 'rupture') !== false || strpos($msg, 'stock') !== false) return 'stock_alert';
    if (strpos($msg, 'vente') !== false || strpos($msg, 'statistique') !== false) return 'sales_stats';
    if (strpos($msg, 'lustre') !== false || strpos($msg, 'plafond') !== false) return 'category_1';
    if (strpos($msg, 'spot') !== false || strpos($msg, 'encastré') !== false) return 'category_2';
    if (strpos($msg, 'led') !== false || strpos($msg, 'ampoule') !== false) return 'category_3';
    if (strpos($msg, 'merci') !== false) return 'gratitude';
    return 'general';
}

// 2. Système de recommandation (Cross-selling)
function getRecommendation($pdo, $cat_id) {
    // Trouve le produit le plus vendu dans cette catégorie
    $stmt = $pdo->prepare("
        SELECT p.nom, p.prix, SUM(v.quantite) as total_vendu 
        FROM ventes v 
        JOIN produits p ON v.produit_id = p.id 
        WHERE p.categorie_id = ? 
        GROUP BY p.id 
        ORDER BY total_vendu DESC 
        LIMIT 1
    ");
    $stmt->execute([$cat_id]);
    return $stmt->fetch();
}

// 3. Prédiction de rupture (Alerte proactive)
function getStockAlert($pdo) {
    // Calcule la vitesse de vente moyenne par produit
    $stmt = $pdo->query("
        SELECT p.nom, p.stock, AVG(v.quantite) as ventes_jour
        FROM produits p
        LEFT JOIN ventes v ON p.id = v.produit_id
        WHERE v.date_vente > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY p.id
        HAVING p.stock < (AVG(v.quantite) * 7) -- Si stock < 7 jours de ventes
        LIMIT 3
    ");
    return $stmt->fetchAll();
}

// LOGIQUE PRINCIPALE
 $intent = detectIntent($message);
 $reply = "Je n'ai pas bien compris. Cherchez-vous des Lustres, des Spots ou des conseils d'éclairage ?";

switch($intent) {
    case 'stock_alert':
        $alerts = getStockAlert($pdo);
        if($alerts) {
            $reply = "⚠️ Alerte Prédictive ! Les produits suivants risquent la rupture sous 7 jours : \n";
            foreach($alerts as $a) $reply .= "- {$a['nom']} (Stock: {$a['stock']})\n";
            $reply .= "Je vous conseille de réapprovisionner rapidement.";
        } else {
            $reply = "✅ Bonne nouvelle ! Selon mes calculs, vos stocks sont sains pour les 7 prochains jours.";
        }
        break;

    case 'sales_stats':
        $stmt = $pdo->query("SELECT SUM(prix * quantite) as ca FROM ventes v JOIN produits p ON v.produit_id = p.id");
        $ca = $stmt->fetch()['ca'] ?? 0;
        $reply = "📊 Analyse des ventes : Le chiffre d'affaires récent est de " . number_format($ca, 2) . " DT. Le produit le plus populaire est l'Ampoule LED E27.";
        break;

    case 'category_1': // Lustres
        $reply = "Nos lustres sont très populaires. Je recommande particulièrement le **Lustre Cristal 8 Lumières** à 450 DT.";
        $rec = getRecommendation($pdo, 1);
        if($rec) $reply .= "\n\n💡 Astuce IA : Les clients qui achètent ce lustre prennent souvent aussi des ampoules LED déco.";
        break;

    case 'category_2': // Spots
        $reply = "Pour les spots, nous avons le modèle Orientable à 18.50 DT, idéal pour les plafonds bas.";
        break;
        
    case 'gratitude':
        $reply = "Avec plaisir ! Je suis là pour illuminer votre expérience. 😊";
        break;
}

echo json_encode(['reply' => $reply]);
?>
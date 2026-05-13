<?php
/**
 * API Backend du Chatbot intelligent
 * Reçoit les messages du client et répond via Gemini AI
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/config.php';

// Vérification méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_message = trim($input['message'] ?? '');

if (empty($user_message)) {
    echo json_encode(['success' => false, 'message' => 'Message vide']);
    exit;
}

// ===== RÉCUPÉRATION DU CONTEXTE CATALOGUE =====
try {
    $stmt = $pdo->query("SELECT p.nom, p.prix, p.stock, p.description, c.nom as categorie 
                         FROM produits p 
                         LEFT JOIN categories c ON p.categorie_id = c.id 
                         WHERE p.stock > 0
                         ORDER BY p.date_creation DESC 
                         LIMIT 30");
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT nom, description FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $produits = [];
    $categories = [];
}

// Construction du contexte
$contexte = "CATALOGUE SOUILEM LIGHTING :\n\nCATÉGORIES :\n";
foreach ($categories as $c) {
    $contexte .= "- {$c['nom']} : {$c['description']}\n";
}
$contexte .= "\nPRODUITS DISPONIBLES :\n";
foreach ($produits as $p) {
    $contexte .= "- {$p['nom']} ({$p['categorie']}) - {$p['prix']} DT - Stock: {$p['stock']} - {$p['description']}\n";
}

// ===== PROMPT SYSTÈME =====
$system_prompt = "Tu es l'assistant virtuel de SOUILEM LIGHTING, une entreprise tunisienne spécialisée dans l'éclairage (lustres, spots, LED, luminaires).

RÈGLES :
- Réponds TOUJOURS en français, de manière chaleureuse et professionnelle.
- Sois concis (3-5 phrases maximum sauf si demande détaillée).
- Utilise les informations du catalogue ci-dessous pour recommander des produits.
- Si le client cherche un produit, propose 2-3 références avec prix en DT (Dinar Tunisien).
- Pour les commandes, oriente vers le site (connexion/panier).
- Si tu ne sais pas, propose de contacter l'équipe : contact@souilemlighting.tn
- Utilise des emojis avec modération (💡 ✨ 🏮).

$contexte

Réponds maintenant à la question du client.";

// ===== HISTORIQUE DE CONVERSATION =====
if (!isset($_SESSION['chatbot_history'])) {
    $_SESSION['chatbot_history'] = [];
}

// Construction des messages pour Gemini
$contents = [];
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $system_prompt]]
];
$contents[] = [
    'role' => 'model',
    'parts' => [['text' => 'Bonjour ! Je suis l\'assistant SOUILEM LIGHTING 💡 Comment puis-je éclairer votre journée ?']]
];

// Ajouter l'historique récent
$history = array_slice($_SESSION['chatbot_history'], -CHATBOT_MAX_HISTORY);
foreach ($history as $msg) {
    $contents[] = [
        'role' => $msg['role'],
        'parts' => [['text' => $msg['text']]]
    ];
}

// Ajouter le message actuel
$contents[] = [
    'role' => 'user',
    'parts' => [['text' => $user_message]]
];

// ===== APPEL API GEMINI =====
$api_response = callGeminiAPI($contents);

if ($api_response['success']) {
    // Sauvegarder dans l'historique
    $_SESSION['chatbot_history'][] = ['role' => 'user', 'text' => $user_message];
    $_SESSION['chatbot_history'][] = ['role' => 'model', 'text' => $api_response['reply']];

    echo json_encode([
        'success' => true,
        'reply' => $api_response['reply']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'reply' => "Désolé, je rencontre un problème technique. Contactez-nous au +216 XX XXX XXX ou contact@souilemlighting.tn 💡",
        'error' => $api_response['error']
    ]);
}

/**
 * Appel à l'API Gemini
 */
function callGeminiAPI($contents) {
    $url = GEMINI_API_URL . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 500,
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, CHATBOT_TIMEOUT);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    if ($curl_err) {
        return ['success' => false, 'error' => 'Erreur cURL: ' . $curl_err];
    }

    if ($http_code !== 200) {
        return ['success' => false, 'error' => 'HTTP ' . $http_code . ': ' . $response];
    }

    $result = json_decode($response, true);
    $reply = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$reply) {
        return ['success' => false, 'error' => 'Réponse vide'];
    }

    return ['success' => true, 'reply' => $reply];
}
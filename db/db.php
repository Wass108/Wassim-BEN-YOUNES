<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée de la session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
    
    // Régénération de l'ID de session pour prévenir le session fixation
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
    }
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com https://fonts.gstatic.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com;");

// ============================================
// PROTECTION CSRF
// ============================================

/**
 * Génère un token CSRF et le stocke en session
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du token CSRF
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Retourne un input hidden avec le token CSRF
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Vérifie le token CSRF dans une requête POST
 */
function checkCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            die('Erreur de sécurité : Token CSRF invalide. Veuillez réessayer.');
        }
    }
}

// ============================================
// RATE LIMITING
// ============================================

/**
 * Table pour stocker les tentatives de connexion
 */
function createRateLimitTable($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                username VARCHAR(100),
                attempt_time DATETIME NOT NULL,
                success BOOLEAN DEFAULT FALSE,
                INDEX idx_ip_time (ip_address, attempt_time),
                INDEX idx_username_time (username, attempt_time)
            )
        ");
    } catch (PDOException $e) {
        // Table existe déjà
    }
}

// Créer la table au chargement
createRateLimitTable($pdo);

/**
 * Enregistre une tentative de connexion
 */
function logLoginAttempt($pdo, $username, $success = false) {
    $ip = getClientIP();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time, success) VALUES (?, ?, NOW(), ?)");
    $stmt->execute([$ip, $username, $success]);
}

/**
 * Vérifie si l'utilisateur/IP est limité
 */
function isRateLimited($pdo, $username = null) {
    $ip = getClientIP();
    
    // Vérifier les tentatives par IP (5 tentatives max en 15 minutes)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE ip_address = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        AND success = FALSE
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    if ($result['attempts'] >= 5) {
        return true;
    }
    
    // Vérifier les tentatives par username si fourni (3 tentatives max en 15 minutes)
    if ($username) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE username = ? 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            AND success = FALSE
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        
        if ($result['attempts'] >= 3) {
            return true;
        }
    }
    
    return false;
}

/**
 * Nettoie les anciennes tentatives (> 24h)
 */
function cleanOldLoginAttempts($pdo) {
    // Exécuter aléatoirement (1% de chance)
    if (rand(1, 100) === 1) {
        $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    }
}

cleanOldLoginAttempts($pdo);

/**
 * Obtient l'IP réelle du client
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Valider l'IP
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        $ip = '0.0.0.0';
    }
    
    return $ip;
}

// ============================================
// VALIDATION ET SANITIZATION
// ============================================

/**
 * Nettoie les données avec protection XSS améliorée
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valide et nettoie un email
 */
function sanitizeEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Valide un numéro de téléphone
 */
function sanitizePhone($phone) {
    $phone = preg_replace('/[^0-9+\s\-\(\)]/', '', $phone);
    return trim($phone);
}

/**
 * Valide une URL
 */
function sanitizeURL($url) {
    $url = filter_var(trim($url), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
}

/**
 * Valide un nombre entier
 */
function sanitizeInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int)$value : false;
}

/**
 * Valide un nombre décimal
 */
function sanitizeFloat($value) {
    return filter_var($value, FILTER_VALIDATE_FLOAT) !== false ? (float)$value : false;
}

// ============================================
// VALIDATION DES FICHIERS UPLOADÉS
// ============================================

/**
 * Valide un fichier image uploadé
 */
function validateImageUpload($file, $maxSize = 5242880) { // 5MB par défaut
    $errors = [];
    
    // Vérifier si le fichier existe
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'errors' => ['Aucun fichier sélectionné']];
    }
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors du téléchargement du fichier';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Vérifier la taille
    if ($file['size'] > $maxSize) {
        $errors[] = 'Le fichier est trop volumineux (max ' . ($maxSize / 1048576) . 'MB)';
    }
    
    // Vérifier le type MIME réel
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowedMimes)) {
        $errors[] = 'Type de fichier non autorisé. Formats acceptés : JPG, PNG, GIF, WEBP';
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'Extension de fichier non autorisée';
    }
    
    // Vérifier que c'est vraiment une image avec getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        $errors[] = 'Le fichier n\'est pas une image valide';
    }
    
    if (count($errors) > 0) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'mime_type' => $mimeType,
        'extension' => $extension,
        'width' => $imageInfo[0],
        'height' => $imageInfo[1]
    ];
}

/**
 * Génère un nom de fichier sécurisé et unique
 */
function generateSecureFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(16));
    return $safeName . '.' . $extension;
}

/**
 * Déplace un fichier uploadé de manière sécurisée
 */
function secureFileUpload($file, $uploadDir, $maxSize = 5242880) {
    // Créer le dossier s'il n'existe pas
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Valider le fichier
    $validation = validateImageUpload($file, $maxSize);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Générer un nom sécurisé
    $newFilename = generateSecureFilename($file['name']);
    $targetPath = rtrim($uploadDir, '/') . '/' . $newFilename;
    
    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'errors' => ['Erreur lors de l\'enregistrement du fichier']];
    }
    
    // Définir les permissions
    chmod($targetPath, 0644);
    
    return [
        'success' => true,
        'filename' => $newFilename,
        'path' => $targetPath
    ];
}

// ============================================
// FONCTIONS EXISTANTES
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCartCount($pdo) {
    if (!isLoggedIn()) {
        return 0;
    }
    
    $stmt = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    return $result['total'] ?? 0;
}
?>

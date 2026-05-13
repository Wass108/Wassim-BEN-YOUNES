<?php
require_once '../db/db.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

header('Content-Type: application/json');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier qu'un fichier a été uploadé
if (empty($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Aucun fichier image reçu ou erreur lors de l\'upload']);
    exit;
}

$file = $_FILES['image_file'];

// Vérifier le type MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp'
];

if (!isset($allowedTypes[$mimeType])) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non supporté']);
    exit;
}

$extension = $allowedTypes[$mimeType];

// Vérifier la taille (max 5MB)
if ($file['size'] > 5242880) {
    echo json_encode(['success' => false, 'error' => 'Image trop volumineuse (max 5MB)']);
    exit;
}

// Créer le dossier images s'il n'existe pas
$uploadDir = '../image/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Générer un nom de fichier unique
$filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Impossible de sauvegarder le fichier image']);
    exit;
}

chmod($filepath, 0644);

$relativePath = 'image/' . $filename;

echo json_encode([
    'success' => true,
    'image_path' => $relativePath,
    'message' => 'Image importée avec succès'
]);
?>
<?php
require_once '../db/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Gestion des actions du panier (augmenter, diminuer, supprimer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $panier_id = isset($_POST['panier_id']) ? (int)$_POST['panier_id'] : 0;
    
    if ($action === 'increase' && $panier_id > 0) {
        // Augmenter la quantité
        $stmt = $pdo->prepare("UPDATE panier SET quantite = quantite + 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$panier_id, $_SESSION['user_id']]);
    } 
    elseif ($action === 'decrease' && $panier_id > 0) {
        // Diminuer la quantité (minimum 1)
        $stmt = $pdo->prepare("SELECT quantite FROM panier WHERE id = ? AND user_id = ?");
        $stmt->execute([$panier_id, $_SESSION['user_id']]);
        $item = $stmt->fetch();
        
        if ($item && $item['quantite'] > 1) {
            $stmt = $pdo->prepare("UPDATE panier SET quantite = quantite - 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$panier_id, $_SESSION['user_id']]);
        } else {
            // Si la quantité est 1, supprimer l'article
            $stmt = $pdo->prepare("DELETE FROM panier WHERE id = ? AND user_id = ?");
            $stmt->execute([$panier_id, $_SESSION['user_id']]);
        }
    } 
    elseif ($action === 'remove' && $panier_id > 0) {
        // Supprimer l'article du panier
        $stmt = $pdo->prepare("DELETE FROM panier WHERE id = ? AND user_id = ?");
        $stmt->execute([$panier_id, $_SESSION['user_id']]);
    }
    
    header('Location: panier.php');
    exit;
}

// Ajout d'un produit au panier depuis la page d'accueil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produit_id'])) {
    $produit_id = (int)$_POST['produit_id'];
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM panier WHERE user_id = ? AND produit_id = ?");
    $stmt->execute([$user_id, $produit_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE panier SET quantite = quantite + 1 WHERE user_id = ? AND produit_id = ?");
        $stmt->execute([$user_id, $produit_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO panier (user_id, produit_id, quantite) VALUES (?, ?, 1)");
        $stmt->execute([$user_id, $produit_id]);
    }
    
    header('Location: panier.php');
    exit;
}

header('Location: panier.php');
exit;
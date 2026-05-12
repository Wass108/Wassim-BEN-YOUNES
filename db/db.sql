CREATE DATABASE IF NOT EXISTS ecommerce;
USE ecommerce;

-- =========================
-- USERS
-- =========================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,

    role ENUM('client', 'admin', 'commercial', 'gestion_stock')
    DEFAULT 'client',

    nom VARCHAR(100),
    prenom VARCHAR(100),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    telephone VARCHAR(20),

    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- CATEGORIES
-- =========================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT
);

-- =========================
-- PRODUITS
-- =========================
CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nom VARCHAR(200) NOT NULL,

    reference_produit VARCHAR(100) UNIQUE,

    description TEXT,

    prix DECIMAL(10,2) NOT NULL,

    marque VARCHAR(100),

    type_eclairage VARCHAR(100),

    couleur VARCHAR(50),

    image VARCHAR(255),

    stock INT DEFAULT 0,

    categorie_id INT,

    nouveau BOOLEAN DEFAULT FALSE,

    promo BOOLEAN DEFAULT FALSE,

    disponibilite BOOLEAN DEFAULT TRUE,

    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (categorie_id)
    REFERENCES categories(id)
);

-- =========================
-- COMMANDES
-- =========================
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    montant_total DECIMAL(10,2) NOT NULL,

    statut ENUM(
        'en_attente',
        'confirmee',
        'en_livraison',
        'livree',
        'annulee'
    ) DEFAULT 'en_attente',

    mode_paiement ENUM(
        'livraison',
        'carte',
        'virement'
    ) DEFAULT 'livraison',

    adresse_livraison TEXT,

    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id)
);

-- =========================
-- DETAILS COMMANDE
-- =========================
CREATE TABLE IF NOT EXISTS details_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,

    commande_id INT,

    produit_id INT,

    quantite INT NOT NULL,

    prix_unitaire DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (commande_id)
    REFERENCES commandes(id),

    FOREIGN KEY (produit_id)
    REFERENCES produits(id)
);

-- =========================
-- PANIER
-- =========================
CREATE TABLE IF NOT EXISTS panier (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    produit_id INT,

    quantite INT NOT NULL,

    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
    REFERENCES users(id),

    FOREIGN KEY (produit_id)
    REFERENCES produits(id)
);

-- =========================
-- HISTORIQUE STOCK
-- =========================
CREATE TABLE IF NOT EXISTS mouvements_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,

    produit_id INT,

    type_mouvement ENUM('entree', 'sortie'),

    quantite INT NOT NULL,

    date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    commentaire TEXT,

    FOREIGN KEY (produit_id)
    REFERENCES produits(id)
);
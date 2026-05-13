# SOUILEM LIGHTING

Application e-commerce de gestion et vente de luminaires, développée en PHP avec une interface d'administration, un catalogue de produits, un panier utilisateur et un assistant chatbot intégré.

## 📌 Présentation

Ce projet permet de gérer un catalogue de produits d'éclairage, d'afficher des catégories, de proposer un panier client, et d'administrer les produits via une interface de back-office.

## 🚀 Fonctionnalités principales

- Gestion des produits (ajout, modification, suppression)
- Upload et affichage d'images produits
- Filtrage par catégorie
- Panier utilisateur et mise à jour des quantités
- Authentification client et administration
- Chatbot intégré pour assistance utilisateur
- Tableau de bord admin pour les commandes, factures et stock

## 🧱 Structure du projet

- `admin/` : interface d'administration
- `user/` : pages et scripts pour les clients
- `db/` : configuration et connexion à la base de données
- `ia/` : assistant chatbot et API de discussion
- `css/` : styles universels du site
- `js/` : scripts JavaScript globaux
- `image/` : images des produits et du site
- `categorie.php`, `home.php` : pages publiques principales

## ⚙️ Prérequis

- PHP 7.4 ou supérieur
- Serveur web (Apache via XAMPP, WAMP, etc.)
- Base de données MySQL/MariaDB

## 🛠️ Installation

1. Copier le dossier du projet dans le répertoire de votre serveur local, par exemple :
   - `C:\xampp\htdocs\Wassim BEN YOUNES`

2. Importer la base de données depuis `db/db.sql` dans PhpMyAdmin ou un outil équivalent.

3. Mettre à jour les informations de connexion dans `db/db.php` si nécessaire :
   - hôte
   - nom de la base
   - utilisateur
   - mot de passe

4. Lancer le serveur local et ouvrir la page d'accueil :
   - `http://localhost/Wassim BEN YOUNES/home.php`

## 🔧 Configuration

- `db/db.php` : connexion PDO et fonctions utilitaires
- `ia/config.php` : configuration du chatbot
- `admin/products.php` : gestion des produits et upload d'images

## 🧾 Utilisation

- `home.php` : page d'accueil du catalogue public
- `categorie.php` : affichage des produits par catégorie
- `user/login.php` : connexion client
- `user/register.php` : inscription client
- `user/panier.php` : gestion du panier
- `user/dashboard.php` : tableau de bord utilisateur
- `admin/dashboard.php` : administration globale

## 💡 Notes importantes

- Les images produits sont stockées dans le dossier `image/`.
- Le chatbot est inclus via `ia/chatbot_widget.php` et utilise `js/chatbot.js`.
- Les pages utilisateur dans `user/` incluent `../` pour atteindre correctement les ressources.

## 🛡️ Bonne pratique

- Vérifiez que le dossier `image/` est accessible en écriture pour les uploads.
- Protégez les pages d'administration par un contrôle de session et de rôle.

## 📄 Licence

Ce projet est fourni tel quel pour usage personnel ou démonstration.

---

*Projet e-commerce développé pour un hackathon, version maintenue et documentée.*

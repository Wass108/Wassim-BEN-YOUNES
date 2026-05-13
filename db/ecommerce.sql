-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 13 mai 2026 à 08:22
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecommerce`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`) VALUES
(1, 'Lustres', ''),
(2, 'Mini Spot LED', ''),
(3, 'Lampe LED', ''),
(4, 'Appliques murales', ''),
(5, 'Accessoire électronique', '');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','confirmee','en_livraison','livree','annulee') DEFAULT 'en_attente',
  `mode_paiement` enum('livraison','carte','virement') DEFAULT 'livraison',
  `adresse_livraison` text DEFAULT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `user_id`, `montant_total`, `statut`, `mode_paiement`, `adresse_livraison`, `date_commande`) VALUES
(1, 1, 1774.00, 'en_attente', 'livraison', NULL, '2026-05-13 03:46:12'),
(2, 1, 887.00, 'en_attente', 'livraison', NULL, '2026-05-13 03:52:04');

-- --------------------------------------------------------

--
-- Structure de la table `details_commande`
--

CREATE TABLE `details_commande` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) DEFAULT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `details_commande`
--

INSERT INTO `details_commande` (`id`, `commande_id`, `produit_id`, `quantite`, `prix_unitaire`) VALUES
(1, 1, 1, 2, 887.00),
(2, 2, 1, 1, 887.00);

-- --------------------------------------------------------

--
-- Structure de la table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `date_mouvement` timestamp NOT NULL DEFAULT current_timestamp(),
  `commentaire` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `reference_produit` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `type_eclairage` varchar(100) DEFAULT NULL,
  `couleur` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `categorie_id` int(11) DEFAULT NULL,
  `nouveau` tinyint(1) DEFAULT 0,
  `promo` tinyint(1) DEFAULT 0,
  `disponibilite` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `nom`, `reference_produit`, `description`, `prix`, `marque`, `type_eclairage`, `couleur`, `image`, `stock`, `categorie_id`, `nouveau`, `promo`, `disponibilite`, `date_creation`) VALUES
(1, 'MINI SPOT LED ROND COB GOLD 5W CW', 'MCB-AB7005', 'Mini Spot LED rond COB 5W, finition dorée, lumière blanche neutre 4000K,\r\n450 lumens, compact (55x45 mm), angle 24°.', 30.00, NULL, '', 'Gold', 'image/product_1778651590_6a0411c69373f.jpg', 75, 2, 1, 0, 1, '2026-05-12 22:01:48'),
(3, 'MINI-SPOT-LED-ROND-COB-ROSE-GOLD-3W-DL', 'MCB-AB8003', 'Mini Spot LED rond COB 3W, finition rose gold, lumière neutre 4000K, \r\ncompact (50x42 mm), 240 lumens, angle 24°, en aluminium robuste.', 23.00, NULL, '', 'Noir', 'image/product_1778651375_6a0410efe2eda.jpg', 100, 2, 1, 0, 1, '2026-05-13 05:49:11'),
(4, 'SPOT-FIXE-CRYSTAL-CLAIR-ROND-SMD-3WGU10', 'MCR-CB1360-1130', 'Spot fixe rond Crystal clair SMD 3W GU10, finition transparente élégante, lumière blanche neutre 4000K,\r\ncompact et décoratif, idéal pour un éclairage raffiné.', 28.00, NULL, '', 'Silver', 'image/product_1778651750_6a041266d3592.jpg', 68, 2, 1, 0, 1, '2026-05-13 05:56:13'),
(5, 'LUSTRE-MODERNE-A-3-LAMPES-NOIR', '5581', 'Lustre Moderne à 3 Lampes - Forme : Arc - Matériel : Métal - Style : Moderne - Type de Lumière : LED - Luminaire mural intérieur - design moderne - Idéal pour une maison,\r\nun magasin ou un restaurant - Couleur : Noir', 229.00, NULL, '', 'Noir', 'image/product_1778651956_6a0413341c377.jpg', 10, 1, 1, 0, 1, '2026-05-13 05:59:32'),
(6, 'LUSTRE-EN-CRISTAL-ROND-DIAMAITRE-80-CM', 'Y17/800', 'description : Lustre En Cristal  - Forme : Rond \r\n- Dimensions : diamètre 80 cm \r\n- Matériel : Crystal - Style : Moderne \r\n- Type de lumière : LED - Corps en cuivre - Design Esthétique élégant - Qualité Supérieure - Des Cristaux Transparents luxe \r\n- Espace Pour Une Utilisation: Salon / Restaurant / Study / Chambre / The Bar', 389.00, NULL, '', 'Bronze', 'image/product_1778652065_6a0413a149d43.jpg', 12, 1, 1, 0, 1, '2026-05-13 06:01:23'),
(7, 'sfg-bx4030-1040', 'SPOT LED ROND 30W', 'Le modèle SFG-BX4030-1040 est un spot LED rond de 30W, destiné à un usage commercial, \r\nmais il n’est actuellement plus en stock chez Polylighting Tunisie.', 22.00, NULL, '', 'Blanc', 'image/product_1778652168_6a0414089dcfe.jpg', 80, 3, 1, 0, 1, '2026-05-13 06:03:25'),
(8, 'hublot-led-round-15w-blanc-ip65', 'MTH-YB2015-1060', 'Hublot LED Round 15W Blanc IP65 : un plafonnier rond moderne, \r\nétanche et puissant, conçu pour l’éclairage intérieur et extérieur.', 25.00, NULL, '', 'Blanc', 'image/product_1778652245_6a04145582d62.jpg', 70, 3, 1, 0, 1, '2026-05-13 06:04:21'),
(9, 'applique-murale-avec-bras-articule-reno-e27', 'L3066-CR', 'Applique murale “Reno” avec bras articulé : un luminaire élégant et pratique, \r\ndoté d’un abat-jour en tissu blanc et d’une structure en aluminium, compatible avec ampoule LED E27 (non incluse).', 98.00, NULL, '', 'Blanc', 'image/product_1778652351_6a0414bf609a2.jpg', 41, 4, 1, 0, 1, '2026-05-13 06:06:08'),
(10, 'applique-murale-interieur-kukka-avec-prise-et-interrupteur', 'LN4003-EU-B', ': Applique murale intérieure “Kukka” avec prise et interrupteur : un luminaire au style scandinave moderne, orientable,\r\npratique grâce à son câble tressé avec fiche et interrupteur, compatible avec ampoule E14 (max 60W, non incluse).', 88.00, NULL, '', 'Blanc', 'image/product_1778652433_6a0415116321d.jpg', 50, 4, 1, 1, 1, '2026-05-13 06:07:29'),
(11, 'disjoncteur-magnetothermique-ik60n-2p-16a-courbe-c-courbe-c-6ka-a9k17216', 'A9K17216', 'Disjoncteur Schneider IK60N 2P 16A courbe C – 6kA :\r\nCompact et fiable, il protège vos circuits contre les surcharges et courts-circuits. Idéal pour installations résidentielles et tertiaires.', 109.00, NULL, '', '', 'image/product_1778652554_6a04158ad767b.webp', 20, 5, 1, 0, 1, '2026-05-13 06:09:32'),
(12, 'Fil-electrique-H07V-U-rigide-rouge-2.5mm²-', '2.5 H07V-U R', 'Le fil électrique rigide H07V-U 2,5 mm² rouge est conçu pour le câblage domestique et industriel, \r\nvendu en bobine de 100 m, robuste et conforme aux normes de sécurité.', 119.00, NULL, '', '', 'image/product_1778652618_6a0415caf0941.jpg', 22, 5, 1, 1, 1, '2026-05-13 06:10:34');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','admin','commercial','gestion_stock') DEFAULT 'client',
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `nom`, `prenom`, `adresse`, `ville`, `code_postal`, `telephone`, `date_creation`) VALUES
(1, 'admin', 'alaameur33@gmail.com', '$2y$10$JPuCbKwQVlJhrdGN8LKZ2uKFmtJI6YThMXXOtZ1mKuEw9HOwMbEJa', 'admin', 'ala', 'ameur', NULL, NULL, NULL, NULL, '2026-05-12 21:36:31');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempt_time`),
  ADD KEY `idx_username_time` (`username`,`attempt_time`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference_produit` (`reference_produit`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `details_commande`
--
ALTER TABLE `details_commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD CONSTRAINT `details_commande_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `details_commande_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `panier_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `panier_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

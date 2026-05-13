<?php
/**
 * Configuration centrale des fonctionnalités IA
 * SOUILEM LIGHTING
 */

// ===== API GEMINI (Google - gratuit) =====
// Obtenir une clé gratuite : https://aistudio.google.com/app/apikey
define('GEMINI_API_KEY', 'AIzaSyCMB5gbi4DR1fbjjo1leXm-AEkLsvVD_is');
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/');

// ===== SEUILS PRÉDICTION STOCK =====
define('STOCK_ALERT_DAYS', 14);      // Alerte orange
define('STOCK_CRITICAL_DAYS', 7);    // Alerte rouge
define('STOCK_HISTORY_DAYS', 60);    // Historique à analyser

// ===== RECOMMANDATIONS =====
define('RECO_MAX_PRODUCTS', 4);      // Nombre max de produits recommandés
define('RECO_MIN_SIMILARITY', 0.1);  // Seuil minimum de similarité

// ===== CHATBOT =====
define('CHATBOT_MAX_HISTORY', 10);   // Nombre de messages à garder en mémoire
define('CHATBOT_TIMEOUT', 30);       // Timeout API en secondes
<?php
/**
 * Widget Chatbot - À inclure dans toutes les pages
 * Usage : <?php include 'ia/chatbot_widget.php'; ?>
 */
?>
<link rel="stylesheet" href="css/chatbot.css">

<!-- Bouton flottant -->
<div id="chatbot-container">
    <button id="chatbot-toggle" class="chatbot-toggle" aria-label="Ouvrir le chat">
        <svg id="chatbot-icon-open" xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
        </svg>
        <svg id="chatbot-icon-close" xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    <!-- Fenêtre du chat -->
    <div id="chatbot-window" class="chatbot-window">
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <span class="chatbot-avatar">💡</span>
                <div>
                    <div class="chatbot-title">Assistant SOUILEM</div>
                    <div class="chatbot-status"><span class="status-dot"></span> En ligne</div>
                </div>
            </div>
        </div>

        <div id="chatbot-messages" class="chatbot-messages">
            <div class="chatbot-message bot">
                <div class="chatbot-bubble">
                    Bonjour ! 👋 Je suis l'assistant SOUILEM LIGHTING. 
                    Je peux vous conseiller sur nos lustres, spots LED, luminaires...
                    Que recherchez-vous ? 💡
                </div>
            </div>
        </div>

        <div class="chatbot-suggestions" id="chatbot-suggestions">
            <button class="suggestion-btn" data-text="Quels sont vos lustres disponibles ?">Vos lustres ?</button>
            <button class="suggestion-btn" data-text="Je cherche un spot LED pour mon salon">Spot LED salon</button>
            <button class="suggestion-btn" data-text="Quels sont vos tarifs ?">Tarifs</button>
        </div>

        <form id="chatbot-form" class="chatbot-input-area">
            <input type="text" id="chatbot-input" placeholder="Écrivez votre message..." autocomplete="off" required>
            <button type="submit" aria-label="Envoyer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                </svg>
            </button>
        </form>
    </div>
</div>

<script src="js/chatbot.js"></script>
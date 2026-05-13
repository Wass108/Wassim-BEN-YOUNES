/**
 * Chatbot SOUILEM LIGHTING
 * Gestion de l'interface et des appels API
 */

(function() {
    const toggleBtn = document.getElementById('chatbot-toggle');
    const chatWindow = document.getElementById('chatbot-window');
    const iconOpen = document.getElementById('chatbot-icon-open');
    const iconClose = document.getElementById('chatbot-icon-close');
    const messagesContainer = document.getElementById('chatbot-messages');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const suggestions = document.getElementById('chatbot-suggestions');

    // URL de l'API (adapter selon votre structure)
    const basePath = window.chatbotBasePath || '';
    const API_URL = basePath + 'ia/chatbot_api.php';

    // Ouvrir/fermer le chat
    toggleBtn.addEventListener('click', () => {
        const isOpen = chatWindow.classList.toggle('open');
        iconOpen.style.display = isOpen ? 'none' : 'block';
        iconClose.style.display = isOpen ? 'block' : 'none';
        if (isOpen) input.focus();
    });

    // Boutons de suggestions
    suggestions.querySelectorAll('.suggestion-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const text = btn.dataset.text;
            sendMessage(text);
            suggestions.style.display = 'none';
        });
    });

    // Envoi du formulaire
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        sendMessage(message);
        input.value = '';
        suggestions.style.display = 'none';
    });

    /**
     * Ajouter un message à l'affichage
     */
    function addMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `chatbot-message ${sender}`;
        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.textContent = text;
        msgDiv.appendChild(bubble);
        messagesContainer.appendChild(msgDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        return msgDiv;
    }

    /**
     * Afficher l'animation "en train d'écrire"
     */
    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-message bot';
        typingDiv.id = 'typing-indicator';
        typingDiv.innerHTML = `
            <div class="chatbot-bubble">
                <div class="chatbot-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>`;
        messagesContainer.appendChild(typingDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }

    /**
     * Envoyer un message au backend
     */
    async function sendMessage(message) {
        addMessage(message, 'user');
        showTyping();

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            const data = await response.json();
            removeTyping();

            if (data.success) {
                addMessage(data.reply, 'bot');
            } else {
                addMessage(data.reply || "Désolé, une erreur s'est produite. Réessayez.", 'bot');
                console.error('Erreur API:', data.error);
            }
        } catch (error) {
            removeTyping();
            addMessage("⚠️ Problème de connexion. Vérifiez votre internet.", 'bot');
            console.error('Erreur fetch:', error);
        }
    }
})();
/**
 * AI Chat handler för AI Module Maker
 * @author Ljustema Sverige AB
 */

class AiChatHandler {
    constructor(config) {
        this.config = config;
        this.conversation = [];
        this.isProcessing = false;
        this.currentContext = null;
        
        // Cache DOM elements
        this.chatContainer = $('.ai-chat-container');
        this.messagesContainer = $('.chat-messages');
        this.inputField = $('.message-input');
        this.sendButton = $('.send-message');
        this.modelSelector = $('.ai-model-selector');
        this.clearButton = $('.clear-chat');
        this.exportButton = $('.export-chat');
        
        this.initializeChat();
        this.bindEvents();
    }

    /**
     * Initialisera chatten
     */
    initializeChat() {
        // Ladda tidigare konversation om det finns
        const savedChat = localStorage.getItem('aiModuleMakerChat');
        if (savedChat) {
            try {
                const chatData = JSON.parse(savedChat);
                this.conversation = chatData.messages || [];
                this.displaySavedMessages();
            } catch (e) {
                console.error('Failed to load saved chat:', e);
            }
        }

        // Visa välkomstmeddelande om det är en ny konversation
        if (this.conversation.length === 0) {
            this.addMessage({
                role: 'assistant',
                content: this.config.welcomeMessage
            });
        }

        // Initiera syntax highlighting
        this.initializeCodeHighlighting();
    }

    /**
     * Bind event handlers
     */
    bindEvents() {
        // Send message events
        this.sendButton.on('click', () => this.sendMessage());
        this.inputField.on('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        // Other controls
        this.clearButton.on('click', () => this.clearChat());
        this.exportButton.on('click', () => this.exportChat());
        this.modelSelector.on('change', () => this.handleModelChange());

        // Context handling
        $(document).on('updateAiContext', (e, context) => {
            this.updateContext(context);
        });
    }

    /**
     * Skicka meddelande till AI
     */
    async sendMessage() {
        if (this.isProcessing || !this.inputField.val().trim()) {
            return;
        }

        const message = this.inputField.val().trim();
        this.inputField.val('');
        this.isProcessing = true;

        // Lägg till användarens meddelande
        this.addMessage({
            role: 'user',
            content: message
        });

        // Visa typing indicator
        this.showTypingIndicator();

        try {
            const response = await this.callAiApi({
                message: message,
                conversation: this.conversation,
                model: this.modelSelector.val(),
                context: this.currentContext
            });

            // Lägg till AI:s svar
            this.addMessage({
                role: 'assistant',
                content: response.content
            });

            // Spara konversationen
            this.saveConversation();

        } catch (error) {
            this.handleError(error);
        } finally {
            this.hideTypingIndicator();
            this.isProcessing = false;
        }
    }

    /**
     * Anropa AI API
     */
    async callAiApi(data) {
        const response = await $.ajax({
            url: this.config.apiEndpoint,
            method: 'POST',
            data: {
                action: 'aiChat',
                ...data
            }
        });

        if (!response.success) {
            throw new Error(response.error || 'AI request failed');
        }

        return response;
    }

    /**
     * Lägg till meddelande i chatten
     */
    addMessage(message) {
        this.conversation.push(message);
        
        const messageHtml = this.createMessageHtml(message);
        this.messagesContainer.append(messageHtml);
        
        // Uppdatera syntax highlighting för nya kodblock
        this.highlightCode();
        
        // Scrolla till botten
        this.scrollToBottom();
    }

    /**
     * Skapa HTML för ett meddelande
     */
    createMessageHtml(message) {
        const isUser = message.role === 'user';
        const formattedContent = this.formatMessageContent(message.content);
        
        return `
            <div class="message ${isUser ? 'user' : 'assistant'}">
                <div class="message-content">
                    <div class="message-header">
                        <i class="icon icon-${isUser ? 'user' : 'robot'}"></i>
                        <span>${isUser ? 'You' : 'AI Assistant'}</span>
                        <span class="message-time">${this.formatTime(new Date())}</span>
                    </div>
                    <div class="message-text">${formattedContent}</div>
                </div>
            </div>
        `;
    }

    /**
     * Formatera meddelandeinnehåll
     */
    formatMessageContent(content) {
        // Hantera kodblock med syntax highlighting
        content = content.replace(/```(\w+)?\n([\s\S]+?)```/g, (match, lang, code) => {
            return `<pre><code class="language-${lang || 'plaintext'}">${this.escapeHtml(code.trim())}</code></pre>`;
        });

        // Hantera inline kod
        content = content.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Konvertera länkar
        content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>');

        // Hantera radbrytningar
        content = content.replace(/\n/g, '<br>');

        return content;
    }

    /**
     * Initiera syntax highlighting
     */
    initializeCodeHighlighting() {
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
    }

    /**
     * Uppdatera kontext
     */
    updateContext(context) {
        this.currentContext = context;
        
        // Visa kontextindikator
        if (context) {
            this.chatContainer.addClass('has-context');
            $('.context-indicator').text(`Context: ${context.type}`);
        } else {
            this.chatContainer.removeClass('has-context');
        }
    }

    /**
     * Rensa chatten
     */
    clearChat() {
        if (confirm(this.config.translations.confirmClear)) {
            this.conversation = [];
            this.messagesContainer.empty();
            localStorage.removeItem('aiModuleMakerChat');
            
            // Visa välkomstmeddelande igen
            this.addMessage({
                role: 'assistant',
                content: this.config.welcomeMessage
            });
        }
    }

    /**
     * Exportera chathistorik
     */
    exportChat() {
        const chatLog = this.conversation.map(msg => {
            return `[${msg.role}] ${msg.content}`;
        }).join('\n\n');

        const blob = new Blob([chatLog], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `ai-chat-export-${new Date().toISOString()}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Hantera modellbyte
     */
    handleModelChange() {
        const model = this.modelSelector.val();
        this.addMessage({
            role: 'system',
            content: `Switched to ${model} model`
        });
    }

    /**
     * Hjälpmetoder
     */
    showTypingIndicator() {
        $('.typing-indicator').show();
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        $('.typing-indicator').hide();
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
    }

    highlightCode() {
        if (typeof Prism !== 'undefined') {
            Prism.highlightAllUnder(this.messagesContainer[0]);
        }
    }

    formatTime(date) {
        return date.toLocaleTimeString('sv-SE', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    saveConversation() {
        localStorage.setItem('aiModuleMakerChat', JSON.stringify({
            messages: this.conversation,
            timestamp: new Date().toISOString()
        }));
    }

    displaySavedMessages() {
        this.conversation.forEach(message => {
            const messageHtml = this.createMessageHtml(message);
            this.messagesContainer.append(messageHtml);
        });
        this.highlightCode();
        this.scrollToBottom();
    }

    handleError(error) {
        this.addMessage({
            role: 'system',
            content: `Error: ${error.message}`
        });
        console.error('AI Chat Error:', error);
    }
}

// Initialisera när dokumentet är klart
$(document).ready(() => {
    if ($('.ai-chat-container').length) {
        window.AiChat = new AiChatHandler({
            apiEndpoint: prestashop.urls.base_ajax_url,
            welcomeMessage: 'Hello! I\'m your AI assistant. How can I help you today?',
            translations: {
                confirmClear: 'Are you sure you want to clear the chat history?'
            }
        });
    }
});
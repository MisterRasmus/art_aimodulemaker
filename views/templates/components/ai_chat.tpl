<div class="ai-chat-component">
    <div class="chat-container panel">
        <div class="panel-heading">
            <div class="row">
                <div class="col-md-6">
                    <i class="icon icon-comments"></i> {l s='AI Assistant' mod='rl_aimodulemaker'}
                </div>
                <div class="col-md-6 text-right">
                    <select class="ai-model-selector form-control-inline">
                        {foreach $ai_models as $model}
                            <option value="{$model.id|escape:'html':'UTF-8'}">{$model.name|escape:'html':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
        
        <div class="panel-body">
            <div class="chat-messages" style="height: 300px; overflow-y: auto; margin-bottom: 15px;">
                {* Välkomstmeddelande *}
                <div class="message assistant">
                    <div class="message-content">
                        <div class="message-header">
                            <i class="icon icon-robot"></i> AI Assistant
                        </div>
                        <div class="message-text">
                            {l s='Hello! I\'m your AI assistant. I can help you with:' mod='rl_aimodulemaker'}
                            <ul>
                                <li>{l s='Creating new module features' mod='rl_aimodulemaker'}</li>
                                <li>{l s='Understanding existing code' mod='rl_aimodulemaker'}</li>
                                <li>{l s='Debugging issues' mod='rl_aimodulemaker'}</li>
                                <li>{l s='Suggesting improvements' mod='rl_aimodulemaker'}</li>
                            </ul>
                            {l s='How can I assist you today?' mod='rl_aimodulemaker'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-input">
                <div class="input-group">
                    <input type="text" 
                           class="form-control message-input" 
                           placeholder="{l s='Type your message here...' mod='rl_aimodulemaker'}"
                           data-context="{$chat_context|escape:'html':'UTF-8'}">
                    <span class="input-group-btn">
                        <button class="btn btn-primary send-message" type="button">
                            <i class="icon icon-paper-plane"></i> {l s='Send' mod='rl_aimodulemaker'}
                        </button>
                    </span>
                </div>
                <div class="chat-actions margin-top-1">
                    <button class="btn btn-default btn-xs show-context" type="button">
                        <i class="icon icon-code"></i> {l s='Show Context' mod='rl_aimodulemaker'}
                    </button>
                    <button class="btn btn-default btn-xs clear-chat" type="button">
                        <i class="icon icon-trash"></i> {l s='Clear Chat' mod='rl_aimodulemaker'}
                    </button>
                    <button class="btn btn-default btn-xs export-chat" type="button">
                        <i class="icon icon-download"></i> {l s='Export Chat' mod='rl_aimodulemaker'}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {* Context Modal *}
    <div class="modal fade" id="context-modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">{l s='Current Context' mod='rl_aimodulemaker'}</h4>
                </div>
                <div class="modal-body">
                    <pre class="context-content"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

{* Styles för chatkomponenten *}
<style>
    .ai-chat-component .message {
        margin-bottom: 15px;
        clear: both;
    }

    .ai-chat-component .message.user {
        float: right;
        max-width: 80%;
    }

    .ai-chat-component .message.assistant {
        float: left;
        max-width: 80%;
    }

    .ai-chat-component .message-content {
        padding: 10px;
        border-radius: 10px;
        background: #f8f9fa;
    }

    .ai-chat-component .message.user .message-content {
        background: #e3f2fd;
    }

    .ai-chat-component .message-header {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }

    .ai-chat-component .message-text {
        white-space: pre-wrap;
    }

    .ai-chat-component .message-text code {
        display: block;
        background: #f1f1f1;
        padding: 10px;
        margin: 5px 0;
        border-radius: 4px;
    }

    .ai-chat-component .chat-actions {
        font-size: 12px;
    }

    .ai-chat-component .typing-indicator {
        padding: 10px;
        display: none;
    }

    .ai-chat-component .typing-indicator span {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #90949c;
        border-radius: 50%;
        margin: 0 2px;
        animation: typing 1s infinite;
    }

    @keyframes typing {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
</style>

{* JavaScript för chatkomponenten *}
<script type="text/javascript">
    class AiChatComponent {
        constructor(element, config) {
            this.element = element;
            this.config = config;
            this.messages = [];
            this.isProcessing = false;
            
            this.initializeElements();
            this.bindEvents();
        }

        initializeElements() {
            this.messagesContainer = this.element.find('.chat-messages');
            this.messageInput = this.element.find('.message-input');
            this.sendButton = this.element.find('.send-button');
            this.modelSelector = this.element.find('.ai-model-selector');
            this.addTypingIndicator();
        }

        bindEvents() {
            this.messageInput.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            this.sendButton.on('click', () => this.sendMessage());
            this.element.find('.clear-chat').on('click', () => this.clearChat());
            this.element.find('.export-chat').on('click', () => this.exportChat());
            this.element.find('.show-context').on('click', () => this.showContext());
        }

        async sendMessage() {
            if (this.isProcessing || !this.messageInput.val().trim()) return;

            const message = this.messageInput.val();
            this.messageInput.val('');
            this.addMessage(message, 'user');
            this.isProcessing = true;
            this.showTypingIndicator();

            try {
                const response = await this.callAiApi(message);
                this.addMessage(response, 'assistant');
            } catch (error) {
                this.addMessage('Error: ' + error.message, 'assistant error');
            } finally {
                this.isProcessing = false;
                this.hideTypingIndicator();
            }
        }

        addMessage(text, type) {
            const messageHtml = this.createMessageHtml(text, type);
            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
            this.messages.push({ text, type });
        }

        createMessageHtml(text, type) {
            return `
                <div class="message ${type}">
                    <div class="message-content">
                        <div class="message-header">
                            <i class="icon icon-${type === 'user' ? 'user' : 'robot'}"></i>
                            ${type === 'user' ? 'You' : 'AI Assistant'}
                        </div>
                        <div class="message-text">${this.formatMessage(text)}</div>
                    </div>
                </div>
            `;
        }

        formatMessage(text) {
            // Konvertera kodblock
            text = text.replace(/```(\w+)?\n([\s\S]+?)```/g, 
                              (_, lang, code) => `<code class="language-${lang || 'plaintext'}">${this.escapeHtml(code)}</code>`);
            
            // Konvertera radbrytningar
            text = text.replace(/\n/g, '<br>');
            
            return text;
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        scrollToBottom() {
            this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
        }

        addTypingIndicator() {
            this.typingIndicator = $(`
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `);
            this.messagesContainer.append(this.typingIndicator);
        }

        showTypingIndicator() {
            this.typingIndicator.show();
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.typingIndicator.hide();
        }

        async callAiApi(message) {
            const response = await $.ajax({
                url: this.config.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'aiChat',
                    message: message,
                    model: this.modelSelector.val(),
                    context: this.messageInput.data('context')
                }
            });

            if (!response.success) {
                throw new Error(response.error);
            }

            return response.response;
        }

        clearChat() {
            if (confirm(this.config.translations.confirmClear)) {
                this.messagesContainer.html('');
                this.messages = [];
                this.addTypingIndicator();
            }
        }

        exportChat() {
            const chatLog = this.messages.map(m => `[${m.type}] ${m.text}`).join('\n\n');
            const blob = new Blob([chatLog], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'chat-export.txt';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        showContext() {
            const context = this.messageInput.data('context');
            $('#context-modal .context-content').text(JSON.stringify(context, null, 2));
            $('#context-modal').modal('show');
        }
    }

    // Initialisera komponenten
    $(document).ready(function() {
        $('.ai-chat-component').each(function() {
            new AiChatComponent($(this), {
                ajaxUrl: '{$link->getAdminLink('AdminRlAiModuleMaker')|addslashes}',
                translations: {
                    confirmClear: '{l s='Are you sure you want to clear the chat history?' mod='rl_aimodulemaker' js=1}'
                }
            });
        });
    });
</script>
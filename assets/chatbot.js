jQuery(document).ready(function($) {
    let isOpen = false;
    let isLoading = false;
    
    // Load saved messages on page load (if persistence is enabled)
    if (chatbot_ajax.message_persistence === '1') {
        loadSavedMessages();
    }
    
    // Toggle chatbot
    $('#chatbot-toggle').on('click', function() {
        if (isOpen) {
            closeChatbot();
        } else {
            openChatbot();
        }
    });
    
    // Close chatbot
    $('#chatbot-close').on('click', function() {
        closeChatbot();
    });
    
    // Clear chat history
    $('#chatbot-clear').on('click', function() {
        if (confirm('Are you sure you want to clear the chat history?')) {
            clearChatHistory();
        }
    });
    
    // Send message on button click
    $('#chatbot-send').on('click', function() {
        sendMessage();
    });
    
    // Send message on Enter key
    $('#chatbot-input').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    function openChatbot() {
        $('#chatbot-container').fadeIn(300);
        isOpen = true;
        $('#chatbot-input').focus();
    }
    
    function closeChatbot() {
        $('#chatbot-container').fadeOut(300);
        isOpen = false;
    }
    
    function sendMessage() {
        if (isLoading) return;
        
        const message = $('#chatbot-input').val().trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage(message, 'user');
        
        // Clear input
        $('#chatbot-input').val('');
        
        // Show loading
        showLoading();
        
        // Send AJAX request
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'chatbot_message',
                message: message,
                nonce: chatbot_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    addMessage(response.data, 'bot');
                } else {
                    let errorMessage = 'Sorry, I encountered an error. Please try again.';
                    
                    // Show detailed error in debug mode
                    if (chatbot_ajax.debug_mode === '1' && response.data) {
                        errorMessage = 'Error: ' + response.data;
                    }
                    
                    addMessage(errorMessage, 'bot');
                    
                    // Always log to console for debugging
                    console.error('Chatbot error:', response.data);
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                let errorMessage = 'Sorry, I\'m having trouble connecting. Please try again later.';
                
                // Show detailed error in debug mode
                if (chatbot_ajax.debug_mode === '1') {
                    errorMessage = 'Connection error: ' + error + ' (Status: ' + status + ')';
                }
                
                addMessage(errorMessage, 'bot');
                console.error('AJAX error:', error, xhr);
            }
        });
    }
    
    function addMessage(content, type, save = true) {
        // Process content for bot messages to handle links
        let processedContent = content;
        if (type === 'bot') {
            // First, convert markdown links [text](url) to HTML
            processedContent = processedContent.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
            
            // Escape HTML for safety, but preserve our link tags
            const linkPlaceholders = [];
            let linkIndex = 0;
            
            // Extract existing HTML links and replace with placeholders
            processedContent = processedContent.replace(/<a\s+href="([^"]*)"[^>]*>([^<]*)<\/a>/gi, function(match, url, text) {
                const placeholder = `__LINK_${linkIndex++}__`;
                linkPlaceholders.push(`<a href="${url}" target="_blank" rel="noopener">${text}</a>`);
                return placeholder;
            });
            
            // Escape remaining HTML
            processedContent = escapeHtml(processedContent);
            
            // Restore links
            linkPlaceholders.forEach((link, index) => {
                processedContent = processedContent.replace(`__LINK_${index}__`, link);
            });
        } else {
            processedContent = escapeHtml(content);
        }
        
        const messageHtml = `
            <div class="message ${type}-message">
                <div class="message-content">${processedContent}</div>
            </div>
        `;
        
        $('#chatbot-messages').append(messageHtml);
        scrollToBottom();
        
        // Messages are now automatically saved on the server side
        // No need to save here as it's handled in the AJAX response
    }
    
    function showLoading() {
        isLoading = true;
        $('#chatbot-loading').show();
        $('#chatbot-send').prop('disabled', true);
        scrollToBottom();
    }
    
    function hideLoading() {
        isLoading = false;
        $('#chatbot-loading').hide();
        $('#chatbot-send').prop('disabled', false);
    }
    
    function scrollToBottom() {
        const messagesContainer = $('#chatbot-messages');
        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Close chatbot when clicking outside
    $(document).on('click', function(e) {
        if (isOpen && !$(e.target).closest('#ai-chatbot-widget').length) {
            closeChatbot();
        }
    });
    
    // Prevent closing when clicking inside the chatbot
    $('#ai-chatbot-widget').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Auto-resize input area
    $('#chatbot-input').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
    
    // Message persistence functions (now using database)
    function loadSavedMessages() {
        if (chatbot_ajax.message_persistence !== '1') {
            return;
        }
        
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_chat_history',
                nonce: chatbot_ajax.nonce,
                session_id: chatbot_ajax.session_id
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Clear existing messages except welcome message
                    $('#chatbot-messages .message:not(:first)').remove();
                    
                    // Restore saved messages
                    response.data.forEach(function(message) {
                        addMessage(message.message, message.type, false); // false = don't save again
                    });
                }
            },
            error: function(xhr, status, error) {
                console.warn('Could not load chat history:', error);
            }
        });
    }
    
    function clearChatHistory() {
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_chat_history',
                nonce: chatbot_ajax.nonce,
                session_id: chatbot_ajax.session_id
            },
            success: function(response) {
                if (response.success) {
                    $('#chatbot-messages .message:not(:first)').remove();
                    console.log('Chat history cleared');
                }
            },
            error: function(xhr, status, error) {
                console.warn('Could not clear chat history:', error);
            }
        });
    }
});

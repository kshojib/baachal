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
    
    // Clear chat history (only if button exists)
    if ($('#chatbot-clear').length) {
        $('#chatbot-clear').on('click', function() {
            if (confirm('Are you sure you want to clear the chat history?')) {
                clearChatHistory();
            }
        });
    }
    
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
        // Trigger custom event before opening
        $(document).trigger('baachal_before_open', {widget: $('#ai-chatbot-widget')});
        
        $('#chatbot-container').fadeIn(300);
        isOpen = true;
        $('#chatbot-input').focus();
        
        // Trigger custom event after opening
        $(document).trigger('baachal_after_open', {widget: $('#ai-chatbot-widget')});
    }

    function closeChatbot() {
        // Trigger custom event before closing
        $(document).trigger('baachal_before_close', {widget: $('#ai-chatbot-widget')});
        
        $('#chatbot-container').fadeOut(300);
        isOpen = false;
        
        // Trigger custom event after closing
        $(document).trigger('baachal_after_close', {widget: $('#ai-chatbot-widget')});
    }    function sendMessage() {
        if (isLoading) return;
        
        const message = $('#chatbot-input').val().trim();
        if (!message) return;

        // Trigger custom event before sending message - allow modification or cancellation
        const beforeSendEvent = $.Event('baachal_before_send_message', {
            message: message,
            cancelled: false
        });
        $(document).trigger(beforeSendEvent);
        
        // Allow other scripts to cancel the message sending
        if (beforeSendEvent.cancelled) {
            return;
        }
        
        // Use potentially modified message
        const finalMessage = beforeSendEvent.message;
        
        // Add user message to chat
        addMessage(finalMessage, 'user');
        
        // Clear input
        $('#chatbot-input').val('');
        
        // Show loading
        showLoading();
        
        // Trigger custom event when sending starts
        $(document).trigger('baachal_sending_started', {message: finalMessage});
        
        // Send AJAX request
        $.ajax({
            url: chatbot_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'chatbot_message',
                message: finalMessage,
                nonce: chatbot_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                // Trigger custom event for response received
                $(document).trigger('baachal_response_received', {
                    response: response,
                    originalMessage: finalMessage
                });
                
                if (response.success) {
                    // Allow filtering of bot response before display
                    const filteredResponse = $(document).triggerHandler('baachal_filter_bot_response', [response.data]) || response.data;
                    addMessage(filteredResponse, 'bot');
                    
                    // Trigger custom event after successful response
                    $(document).trigger('baachal_response_success', {
                        botMessage: filteredResponse,
                        userMessage: finalMessage
                    });
                } else {
                    let errorMessage = 'Sorry, I encountered an error. Please try again.';
                    
                    // Show detailed error in debug mode
                    if (chatbot_ajax.debug_mode === '1' && response.data) {
                        errorMessage = 'Error: ' + response.data;
                    }
                    
                    // Allow filtering of error message
                    errorMessage = $(document).triggerHandler('baachal_filter_error_message', [errorMessage, response]) || errorMessage;
                    
                    addMessage(errorMessage, 'bot');
                    
                    // Trigger custom event for error
                    $(document).trigger('baachal_response_error', {
                        error: errorMessage,
                        response: response,
                        userMessage: finalMessage
                    });
                    
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
                
                // Allow filtering of connection error message
                errorMessage = $(document).triggerHandler('baachal_filter_connection_error', [errorMessage, xhr, status, error]) || errorMessage;
                
                addMessage(errorMessage, 'bot');
                
                // Trigger custom event for connection error
                $(document).trigger('baachal_connection_error', {
                    error: errorMessage,
                    xhr: xhr,
                    status: status,
                    originalError: error,
                    userMessage: finalMessage
                });
                
                console.error('AJAX error:', error, xhr);
            }
        });
    }
    
    function addMessage(content, type, save = true) {
        // Trigger custom event before adding message - allow modification
        const beforeAddEvent = $.Event('baachal_before_add_message', {
            content: content,
            type: type,
            save: save,
            cancelled: false
        });
        $(document).trigger(beforeAddEvent);
        
        // Allow cancellation
        if (beforeAddEvent.cancelled) {
            return;
        }
        
        // Use potentially modified values
        content = beforeAddEvent.content;
        type = beforeAddEvent.type;
        save = beforeAddEvent.save;
        
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
            <div class="message ${type}-message" data-type="${type}" data-timestamp="${Date.now()}">
                <div class="message-content">${processedContent}</div>
            </div>
        `;
        
        $('#chatbot-messages').append(messageHtml);
        scrollToBottom();
        
        // Trigger custom event after adding message
        $(document).trigger('baachal_after_add_message', {
            content: content,
            processedContent: processedContent,
            type: type,
            messageElement: $('#chatbot-messages .message').last()
        });
        
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

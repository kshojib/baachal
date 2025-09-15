<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$welcome_message = get_option('chatbot_welcome_message', 'Hello! How can I help you today?');
?>

<div id="ai-chatbot-widget">
    <div id="chatbot-toggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="white"/>
            <circle cx="7" cy="9" r="1" fill="white"/>
            <circle cx="12" cy="9" r="1" fill="white"/>
            <circle cx="17" cy="9" r="1" fill="white"/>
        </svg>
    </div>
    
    <div id="chatbot-container" style="display: none;">
        <div id="chatbot-header">
            <h4>Baachal</h4>
            <div class="chatbot-header-buttons">
                <button id="chatbot-clear" title="Clear Chat History">🗑️</button>
                <button id="chatbot-close">×</button>
            </div>
        </div>
        
        <div id="chatbot-messages">
            <div class="message bot-message">
                <div class="message-content"><?php echo esc_html($welcome_message); ?></div>
            </div>
        </div>
        
        <div id="chatbot-input-area">
            <input type="text" id="chatbot-input" placeholder="Type your message..." />
            <button id="chatbot-send">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
                </svg>
            </button>
        </div>
        
        <div id="chatbot-loading" style="display: none;">
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</div>

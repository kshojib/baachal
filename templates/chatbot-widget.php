<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$welcome_message = get_option('chatbot_welcome_message', 'Hello! How can I help you today?');
$show_clear_history = get_option('chatbot_show_clear_history', '1');

// Allow filtering of widget settings
$welcome_message = apply_filters('baachal_welcome_message', $welcome_message);
$show_clear_history = apply_filters('baachal_show_clear_history', $show_clear_history);

// Allow other plugins to add content before chatbot widget
do_action('baachal_before_chatbot_widget');
?>

<div id="ai-chatbot-widget">
    <?php
    // Allow other plugins to add content before toggle button
    do_action('baachal_before_toggle_button');
    ?>
    
    <div id="chatbot-toggle"><?php
        // Allow filtering of toggle button content
        $toggle_content = apply_filters('baachal_toggle_button_content', '
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="white"/>
            <circle cx="7" cy="9" r="1" fill="white"/>
            <circle cx="12" cy="9" r="1" fill="white"/>
            <circle cx="17" cy="9" r="1" fill="white"/>
        </svg>
        ');
        echo $toggle_content;
        ?>
    </div>
    
    <?php
    // Allow other plugins to add content after toggle button
    do_action('baachal_after_toggle_button');
    ?>
    
    <div id="chatbot-container" style="display: none;">
        <?php
        // Allow other plugins to add content before header
        do_action('baachal_before_chatbot_header');
        ?>
        
        <div id="chatbot-header">
            <h4><?php echo apply_filters('baachal_chatbot_title', 'Baachal'); ?></h4>
            <div class="chatbot-header-buttons">
                <?php
                // Allow other plugins to add custom header buttons before clear button
                do_action('baachal_chatbot_header_buttons_before');
                
                if ($show_clear_history == '1'): ?>
                <button id="chatbot-clear" title="Clear Chat History">🗑️</button>
                <?php endif;
                
                // Allow other plugins to add custom header buttons after clear button
                do_action('baachal_chatbot_header_buttons_after');
                ?>
                <button id="chatbot-close">×</button>
            </div>
        </div>
        
        <?php
        // Allow other plugins to add content after header
        do_action('baachal_after_chatbot_header');
        ?>
        
        <div id="chatbot-messages">
            <?php
            // Allow other plugins to add content before welcome message
            do_action('baachal_before_welcome_message');
            ?>
            
            <div class="message bot-message">
                <div class="message-content"><?php echo esc_html($welcome_message); ?></div>
            </div>
            
            <?php
            // Allow other plugins to add content after welcome message
            do_action('baachal_after_welcome_message');
            ?>
        </div>
        
        <?php
        // Allow other plugins to add content before input area
        do_action('baachal_before_input_area');
        ?>
        
        <div id="chatbot-input-area">
            <input type="text" id="chatbot-input" placeholder="<?php echo esc_attr(apply_filters('baachal_input_placeholder', 'Type your message...')); ?>" />
            <button id="chatbot-send"><?php
                // Allow filtering of send button content
                $send_button_content = apply_filters('baachal_send_button_content', '
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 21L23 12L2 3V10L17 12L2 14V21Z" fill="currentColor"/>
                </svg>
                ');
                echo $send_button_content;
                ?>
            </button>
        </div>
        
        <?php
        // Allow other plugins to add content after input area
        do_action('baachal_after_input_area');
        ?>
        
        <div id="chatbot-loading" style="display: none;">
            <?php
            // Allow filtering of loading content
            $loading_content = apply_filters('baachal_loading_content', '
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            ');
            echo $loading_content;
            ?>
        </div>
        
        <?php
        // Allow other plugins to add content before container end
        do_action('baachal_before_container_end');
        ?>
    </div>
    
    <?php
    // Allow other plugins to add content after chatbot container
    do_action('baachal_after_chatbot_container');
    ?>
</div>

<?php
// Allow other plugins to add content after chatbot widget
do_action('baachal_after_chatbot_widget');
?>

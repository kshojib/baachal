<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    check_admin_referer('chatbot_settings');
    
    update_option('chatbot_gemini_api_key', sanitize_text_field($_POST['chatbot_gemini_api_key']));
    update_option('chatbot_enabled', isset($_POST['chatbot_enabled']) ? '1' : '0');
    update_option('chatbot_welcome_message', sanitize_textarea_field($_POST['chatbot_welcome_message']));
    update_option('chatbot_debug_mode', isset($_POST['chatbot_debug_mode']) ? '1' : '0');
    update_option('chatbot_gemini_model', sanitize_text_field($_POST['chatbot_gemini_model']));
    update_option('chatbot_woocommerce_integration', isset($_POST['chatbot_woocommerce_integration']) ? '1' : '0');
    update_option('chatbot_message_persistence', isset($_POST['chatbot_message_persistence']) ? '1' : '0');
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

$api_key = get_option('chatbot_gemini_api_key', '');
$enabled = get_option('chatbot_enabled', '1');
$welcome_message = get_option('chatbot_welcome_message', 'Hello! How can I help you today?');
$debug_mode = get_option('chatbot_debug_mode', '0');
$gemini_model = get_option('chatbot_gemini_model', 'gemini-2.0-flash-exp');
$woocommerce_integration = get_option('chatbot_woocommerce_integration', '1');
$message_persistence = get_option('chatbot_message_persistence', '1');
?>

<div class="wrap">
    <h1>Baachal AI bot Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('chatbot_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="chatbot_enabled">Enable Baachal AI bot</label>
                </th>
                <td>
                    <input type="checkbox" id="chatbot_enabled" name="chatbot_enabled" value="1" <?php checked($enabled, '1'); ?> />
                    <p class="description">Check to enable the chatbot on your website</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_gemini_api_key">Gemini API Key</label>
                </th>
                <td>
                    <input type="text" id="chatbot_gemini_api_key" name="chatbot_gemini_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                    <p class="description">
                        Enter your Google Gemini API key. Get it from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_gemini_model">Gemini Model</label>
                </th>
                <td>
                    <select id="chatbot_gemini_model" name="chatbot_gemini_model">
                        <option value="gemini-2.0-flash-exp" <?php selected($gemini_model, 'gemini-2.0-flash-exp'); ?>>Gemini 2.0 Flash (Experimental) - Fastest & Latest</option>
                        <option value="gemini-1.5-flash" <?php selected($gemini_model, 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash - Fast & Reliable</option>
                        <option value="gemini-1.5-flash-latest" <?php selected($gemini_model, 'gemini-1.5-flash-latest'); ?>>Gemini 1.5 Flash Latest</option>
                        <option value="gemini-1.5-pro" <?php selected($gemini_model, 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro - Most Capable</option>
                        <option value="gemini-pro" <?php selected($gemini_model, 'gemini-pro'); ?>>Gemini Pro - Legacy</option>
                    </select>
                    <p class="description">Choose the AI model. Gemini 2.0 Flash is recommended for best performance.</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_welcome_message">Welcome Message</label>
                </th>
                <td>
                    <textarea id="chatbot_welcome_message" name="chatbot_welcome_message" rows="3" class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                    <p class="description">The initial message shown when the chatbot opens</p>
                </td>
            </tr>
            
            <?php if (class_exists('WooCommerce')): ?>
            <tr>
                <th scope="row">
                    <label for="chatbot_woocommerce_integration">WooCommerce Integration</label>
                </th>
                <td>
                    <input type="checkbox" id="chatbot_woocommerce_integration" name="chatbot_woocommerce_integration" value="1" <?php checked($woocommerce_integration, '1'); ?> />
                    <p class="description">Enable product knowledge for the chatbot to help customers find products</p>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_message_persistence">Message Persistence</label>
                </th>
                <td>
                    <input type="checkbox" id="chatbot_message_persistence" name="chatbot_message_persistence" value="1" <?php checked($message_persistence, '1'); ?> />
                    <p class="description">Save chat messages in browser so they persist across page reloads</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_debug_mode">Debug Mode</label>
                </th>
                <td>
                    <input type="checkbox" id="chatbot_debug_mode" name="chatbot_debug_mode" value="1" <?php checked($debug_mode, '1'); ?> />
                    <p class="description">Enable to see detailed error messages in browser console (for troubleshooting)</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="card" style="max-width: 600px; margin-top: 20px;">
        <h3>How to get your Gemini API Key:</h3>
        <ol>
            <li>Go to <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
            <li>Sign in with your Google account</li>
            <li>Click "Create API Key"</li>
            <li>Copy the API key and paste it in the field above</li>
        </ol>
        
        <h3>Features:</h3>
        <ul>
            <li>✅ AI-powered responses using Google Gemini</li>
            <li>✅ Website context awareness</li>
            <li>✅ Clean, simple interface</li>
            <li>✅ Bottom-right positioning</li>
            <li>✅ Easy to customize</li>
            <li>✅ Selectable AI models including Gemini 2.0 Flash</li>
            <li>✅ Fast responses with single model requests</li>
            <li>✅ Message persistence across page reloads</li>
            <?php if (class_exists('WooCommerce')): ?>
            <li>✅ WooCommerce integration - knows about your products</li>
            <?php else: ?>
            <li>❌ WooCommerce integration (WooCommerce not detected)</li>
            <?php endif; ?>
        </ul>
        
        <?php if (class_exists('WooCommerce')): ?>
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>WooCommerce Detected!</strong> The chatbot can now help customers find products from your store. <?php 
            $product_count = wp_count_posts('product')->publish;
            echo "Found {$product_count} published products.";
            ?></p>
        </div>
        <?php endif; ?>
        
        <h3>AI Models:</h3>
        <ul>
            <li><strong>Gemini 2.0 Flash (Experimental):</strong> Latest and fastest model - recommended</li>
            <li><strong>Gemini 1.5 Flash:</strong> Fast and reliable for general use</li>
            <li><strong>Gemini 1.5 Pro:</strong> Most capable for complex queries</li>
            <li><strong>Gemini Pro:</strong> Legacy model for compatibility</li>
        </ul>
        
        <h3>Troubleshooting:</h3>
        <ul>
            <li><strong>"Model is overloaded" error:</strong> Try switching to a different model in settings.</li>
            <li><strong>"Rate limit exceeded":</strong> You've reached your API quota. Wait a few minutes or check your Google AI Studio usage.</li>
            <li><strong>"API key not configured":</strong> Make sure you've entered a valid API key above.</li>
            <li><strong>No response:</strong> Enable debug mode to see detailed error messages.</li>
        </ul>
    </div>
</div>

<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    check_admin_referer('chatbot_settings');
    
    // Allow other plugins to modify settings before saving
    do_action('baachal_before_save_settings', $_POST);
    
    update_option('chatbot_gemini_api_key', sanitize_text_field($_POST['chatbot_gemini_api_key']));
    update_option('chatbot_enabled', isset($_POST['chatbot_enabled']) ? '1' : '0');
    update_option('chatbot_welcome_message', sanitize_textarea_field($_POST['chatbot_welcome_message']));
    update_option('chatbot_debug_mode', isset($_POST['chatbot_debug_mode']) ? '1' : '0');
    update_option('chatbot_gemini_model', sanitize_text_field($_POST['chatbot_gemini_model']));
    update_option('chatbot_woocommerce_integration', isset($_POST['chatbot_woocommerce_integration']) ? '1' : '0');
    update_option('chatbot_message_persistence', isset($_POST['chatbot_message_persistence']) ? '1' : '0');
    update_option('chatbot_show_clear_history', isset($_POST['chatbot_show_clear_history']) ? '1' : '0');
    
    // Dynamic term extraction settings
    update_option('chatbot_max_terms', intval($_POST['chatbot_max_terms']));
    update_option('chatbot_min_term_length', intval($_POST['chatbot_min_term_length']));
    update_option('chatbot_cache_duration', intval($_POST['chatbot_cache_duration']));
    
    // Handle exclude terms as comma-separated values
    $exclude_terms = sanitize_textarea_field($_POST['chatbot_exclude_terms']);
    $exclude_terms_array = array_map('trim', explode(',', $exclude_terms));
    update_option('chatbot_exclude_terms', $exclude_terms_array);
    
    // Clear dynamic terms cache when settings change
    delete_transient('baachal_dynamic_terms');
    
    // Allow other plugins to save additional settings
    do_action('baachal_save_additional_settings', $_POST);
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

$api_key = get_option('chatbot_gemini_api_key', '');
$enabled = get_option('chatbot_enabled', '1');
$welcome_message = get_option('chatbot_welcome_message', 'Hello! How can I help you today?');
$debug_mode = get_option('chatbot_debug_mode', '0');
$gemini_model = get_option('chatbot_gemini_model', 'gemini-2.0-flash-exp');
$woocommerce_integration = get_option('chatbot_woocommerce_integration', '1');
$message_persistence = get_option('chatbot_message_persistence', '1');
$show_clear_history = get_option('chatbot_show_clear_history', '1');

// Dynamic term extraction settings
$max_terms = get_option('chatbot_max_terms', 50);
$min_term_length = get_option('chatbot_min_term_length', 3);
$cache_duration = get_option('chatbot_cache_duration', DAY_IN_SECONDS);
$exclude_terms = get_option('chatbot_exclude_terms', array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'size', 'color', 'item', 'product'));

// Convert exclude terms to comma-separated string for display
if (is_array($exclude_terms)) {
    $exclude_terms_string = implode(', ', $exclude_terms);
} else {
    $exclude_terms_string = $exclude_terms;
}

// Allow other plugins to modify default values
$api_key = apply_filters('baachal_setting_api_key', $api_key);
$enabled = apply_filters('baachal_setting_enabled', $enabled);
$welcome_message = apply_filters('baachal_setting_welcome_message', $welcome_message);
$debug_mode = apply_filters('baachal_setting_debug_mode', $debug_mode);
$gemini_model = apply_filters('baachal_setting_gemini_model', $gemini_model);
$woocommerce_integration = apply_filters('baachal_setting_woocommerce_integration', $woocommerce_integration);
$message_persistence = apply_filters('baachal_setting_message_persistence', $message_persistence);
$show_clear_history = apply_filters('baachal_setting_show_clear_history', $show_clear_history);
?>

<div class="wrap">
    <h1>Baachal AI bot Settings</h1>
    
    <?php
    // Allow other plugins to add content before settings form
    do_action('baachal_before_settings_form');
    ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('chatbot_settings'); ?>
        
        <table class="form-table">
            <?php
            // Allow other plugins to add settings before core settings
            do_action('baachal_settings_before_core');
            ?>
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
                    <label for="chatbot_show_clear_history">Show Clear Chat History</label>
                </th>
                <td>
                    <input type="checkbox" id="chatbot_show_clear_history" name="chatbot_show_clear_history" value="1" <?php checked($show_clear_history, '1'); ?> />
                    <p class="description">Show the clear chat history button in the chatbot header</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row" colspan="2">
                    <h3>Product Search Settings</h3>
                    <p>Configure how the chatbot analyzes your product catalog for better search results</p>
                </th>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_max_terms">Maximum Terms</label>
                </th>
                <td>
                    <input type="number" id="chatbot_max_terms" name="chatbot_max_terms" value="<?php echo esc_attr($max_terms); ?>" min="10" max="200" />
                    <p class="description">Maximum number of important terms to extract from your products (10-200)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_min_term_length">Minimum Term Length</label>
                </th>
                <td>
                    <input type="number" id="chatbot_min_term_length" name="chatbot_min_term_length" value="<?php echo esc_attr($min_term_length); ?>" min="2" max="10" />
                    <p class="description">Minimum length of terms to consider important (2-10 characters)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_cache_duration">Cache Duration (seconds)</label>
                </th>
                <td>
                    <input type="number" id="chatbot_cache_duration" name="chatbot_cache_duration" value="<?php echo esc_attr($cache_duration); ?>" min="3600" max="604800" />
                    <p class="description">How long to cache extracted terms (3600 = 1 hour, 86400 = 1 day, 604800 = 1 week)</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="chatbot_exclude_terms">Exclude Terms</label>
                </th>
                <td>
                    <textarea id="chatbot_exclude_terms" name="chatbot_exclude_terms" rows="3" cols="50"><?php echo esc_textarea($exclude_terms_string); ?></textarea>
                    <p class="description">Comma-separated list of words to ignore when extracting terms (e.g., "the,and,or,size,color")</p>
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
            
            <?php
            // Allow other plugins to add custom settings after core settings
            do_action('baachal_settings_after_core');
            ?>
        </table>
        
        <?php
        // Allow other plugins to add content before submit button
        do_action('baachal_before_settings_submit');
        ?>
        
        <?php submit_button(); ?>
        
        <?php
        // Allow other plugins to add content after submit button
        do_action('baachal_after_settings_submit');
        ?>
    </form>
    
    <?php
    // Allow other plugins to add content after settings form
    do_action('baachal_after_settings_form');
    ?>
    
    <div class="wrap" style="margin-top: 20px;">
        <h2>Cache Management</h2>
        <p>Clear cached product terms to force the system to re-analyze your products.</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('clear_dynamic_terms_cache'); ?>
            <input type="hidden" name="action" value="clear_cache" />
            
            <?php
            // Check if we should clear cache
            if (isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
                check_admin_referer('clear_dynamic_terms_cache');
                delete_transient('baachal_dynamic_terms');
                echo '<div class="notice notice-success"><p>Product terms cache cleared successfully!</p></div>';
            }
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Dynamic Product Terms</th>
                    <td>
                        <?php
                        $cached_terms = get_transient('baachal_dynamic_terms');
                        if ($cached_terms !== false) {
                            echo '<p style="color: green;">✅ Cache active (' . count($cached_terms) . ' terms stored)</p>';
                            echo '<details><summary>View cached terms</summary><pre>' . esc_html(implode(', ', array_slice($cached_terms, 0, 20))) . '...</pre></details>';
                        } else {
                            echo '<p style="color: orange;">⚠️ No cached terms (will be generated on next product search)</p>';
                        }
                        ?>
                        <p>
                            <input type="submit" class="button" value="Clear Product Terms Cache" />
                        </p>
                        <p class="description">This will force the system to re-analyze your products and extract new terms based on current settings.</p>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    
    <div class="card" style="max-width: 600px; margin-top: 20px;"><?php
        // Allow filtering of the help content
        $help_content = apply_filters('baachal_settings_help_content', array(
            'api_instructions' => true,
            'features_list' => true,
            'woocommerce_notice' => true,
            'ai_models_info' => true,
            'troubleshooting' => true
        ));
        
        if ($help_content['api_instructions']): ?>
        <h3>How to get your Gemini API Key:</h3>
        <ol>
            <li>Go to <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
            <li>Sign in with your Google account</li>
            <li>Click "Create API Key"</li>
            <li>Copy the API key and paste it in the field above</li>
        </ol>
        <?php endif;
        
        if ($help_content['features_list']): ?>
        <h3>Features:</h3>
        <ul><?php
            // Allow filtering of features list
            $features = apply_filters('baachal_features_list', array(
                'ai_responses' => '✅ AI-powered responses using Google Gemini',
                'context_aware' => '✅ Website context awareness',
                'clean_interface' => '✅ Clean, simple interface',
                'positioning' => '✅ Bottom-right positioning',
                'customizable' => '✅ Easy to customize',
                'multiple_models' => '✅ Selectable AI models including Gemini 2.0 Flash',
                'fast_responses' => '✅ Fast responses with single model requests',
                'message_persistence' => '✅ Message persistence across page reloads'
            ));
            
            foreach ($features as $key => $feature) {
                echo '<li>' . esc_html($feature) . '</li>';
            }
            
            if (class_exists('WooCommerce')): ?>
            <li>✅ WooCommerce integration - knows about your products</li>
            <?php else: ?>
            <li>❌ WooCommerce integration (WooCommerce not detected)</li>
            <?php endif; ?>
        </ul>
        <?php endif;
        
        if ($help_content['woocommerce_notice'] && class_exists('WooCommerce')): ?>
        <div class="notice notice-info inline" style="margin: 15px 0;">
            <p><strong>WooCommerce Detected!</strong> The chatbot can now help customers find products from your store. <?php 
            $product_count = wp_count_posts('product')->publish;
            echo "Found {$product_count} published products.";
            ?></p>
        </div>
        <?php endif;
        
        if ($help_content['ai_models_info']): ?>
        <h3>AI Models:</h3>
        <ul><?php
            // Allow filtering of AI models info
            $models_info = apply_filters('baachal_ai_models_info', array(
                'gemini_2_flash' => '<strong>Gemini 2.0 Flash (Experimental):</strong> Latest and fastest model - recommended',
                'gemini_1_5_flash' => '<strong>Gemini 1.5 Flash:</strong> Fast and reliable for general use',
                'gemini_1_5_pro' => '<strong>Gemini 1.5 Pro:</strong> Most capable for complex queries',
                'gemini_pro' => '<strong>Gemini Pro:</strong> Legacy model for compatibility'
            ));
            
            foreach ($models_info as $key => $info) {
                echo '<li>' . wp_kses_post($info) . '</li>';
            }
            ?>
        </ul>
        <?php endif;
        
        if ($help_content['troubleshooting']): ?>
        <h3>Troubleshooting:</h3>
        <ul><?php
            // Allow filtering of troubleshooting tips
            $troubleshooting_tips = apply_filters('baachal_troubleshooting_tips', array(
                'overloaded' => '<strong>"Model is overloaded" error:</strong> Try switching to a different model in settings.',
                'rate_limit' => '<strong>"Rate limit exceeded":</strong> You\'ve reached your API quota. Wait a few minutes or check your Google AI Studio usage.',
                'api_key' => '<strong>"API key not configured":</strong> Make sure you\'ve entered a valid API key above.',
                'no_response' => '<strong>No response:</strong> Enable debug mode to see detailed error messages.'
            ));
            
            foreach ($troubleshooting_tips as $key => $tip) {
                echo '<li>' . wp_kses_post($tip) . '</li>';
            }
            ?>
        </ul>
        <?php endif; ?>
    </div>
    
    <?php
    // Allow other plugins to add content at the end of settings page
    do_action('baachal_settings_page_end');
    ?>
</div>

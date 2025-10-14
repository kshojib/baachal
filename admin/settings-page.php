<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit'])) {
    check_admin_referer('baachal_settings');
    
    // Allow other plugins to modify settings before saving
    do_action('baachal_before_save_settings', $_POST);
    
    // General Settings
    update_option('baachal_ai_provider', isset($_POST['baachal_ai_provider']) ? sanitize_text_field($_POST['baachal_ai_provider']) : 'gemini');
    update_option('baachal_gemini_api_key', isset($_POST['baachal_gemini_api_key']) ? sanitize_text_field($_POST['baachal_gemini_api_key']) : '');
    update_option('baachal_openai_api_key', isset($_POST['baachal_openai_api_key']) ? sanitize_text_field($_POST['baachal_openai_api_key']) : '');
    update_option('baachal_claude_api_key', isset($_POST['baachal_claude_api_key']) ? sanitize_text_field($_POST['baachal_claude_api_key']) : '');
    update_option('baachal_grok_api_key', isset($_POST['baachal_grok_api_key']) ? sanitize_text_field($_POST['baachal_grok_api_key']) : '');
    update_option('baachal_enabled', isset($_POST['baachal_enabled']) ? '1' : '0');
    update_option('baachal_title', isset($_POST['baachal_title']) ? sanitize_text_field($_POST['baachal_title']) : 'AI Chat Bot');
    update_option('baachal_welcome_message', isset($_POST['baachal_welcome_message']) ? sanitize_textarea_field($_POST['baachal_welcome_message']) : 'Hello! How can I help you today?');
    update_option('baachal_ai_model', isset($_POST['baachal_ai_model']) ? sanitize_text_field($_POST['baachal_ai_model']) : 'gemini-2.0-flash-exp');
    update_option('baachal_debug_mode', isset($_POST['baachal_debug_mode']) ? '1' : '0');
    
    // WooCommerce Settings
    update_option('baachal_woocommerce_integration', isset($_POST['baachal_woocommerce_integration']) ? '1' : '0');
    update_option('baachal_message_persistence', isset($_POST['baachal_message_persistence']) ? '1' : '0');
    update_option('baachal_show_clear_history', isset($_POST['baachal_show_clear_history']) ? '1' : '0');
    
    // Product Search Settings
    if (isset($_POST['baachal_max_terms'])) {
        update_option('baachal_max_terms', intval($_POST['baachal_max_terms']));
    }
    if (isset($_POST['baachal_min_term_length'])) {
        update_option('baachal_min_term_length', intval($_POST['baachal_min_term_length']));
    }
    if (isset($_POST['baachal_cache_duration'])) {
        update_option('baachal_cache_duration', intval($_POST['baachal_cache_duration']));
    }
    
    // Handle exclude terms as comma-separated values
    if (isset($_POST['baachal_exclude_terms'])) {
        $exclude_terms = sanitize_textarea_field($_POST['baachal_exclude_terms']);
        $exclude_terms_array = array_map('trim', explode(',', $exclude_terms));
        update_option('baachal_exclude_terms', $exclude_terms_array);
    }
    
    // UI Styling Settings
    if (isset($_POST['baachal_primary_color'])) {
        update_option('baachal_primary_color', sanitize_hex_color($_POST['baachal_primary_color']));
    }
    if (isset($_POST['baachal_secondary_color'])) {
        update_option('baachal_secondary_color', sanitize_hex_color($_POST['baachal_secondary_color']));
    }
    if (isset($_POST['baachal_position'])) {
        update_option('baachal_position', sanitize_text_field($_POST['baachal_position']));
    }
    if (isset($_POST['baachal_size'])) {
        update_option('baachal_size', sanitize_text_field($_POST['baachal_size']));
    }
    if (isset($_POST['baachal_border_radius'])) {
        update_option('baachal_border_radius', intval($_POST['baachal_border_radius']));
    }
    if (isset($_POST['baachal_chat_height'])) {
        update_option('baachal_chat_height', intval($_POST['baachal_chat_height']));
    }
    if (isset($_POST['baachal_font_size'])) {
        update_option('baachal_font_size', intval($_POST['baachal_font_size']));
    }
    if (isset($_POST['baachal_animation_enabled'])) {
        update_option('baachal_animation_enabled', '1');
    } else {
        update_option('baachal_animation_enabled', '0');
    }
    
    // Content Indexing Settings
    update_option('baachal_content_indexing_enabled', isset($_POST['baachal_content_indexing_enabled']) ? '1' : '0');
    update_option('baachal_auto_index', isset($_POST['baachal_auto_index']) ? '1' : '0');
    update_option('baachal_preserve_data_on_uninstall', isset($_POST['baachal_preserve_data_on_uninstall']) ? '1' : '0');
    
    if (isset($_POST['baachal_indexable_post_types']) && is_array($_POST['baachal_indexable_post_types'])) {
        $indexable_types = array_map('sanitize_text_field', $_POST['baachal_indexable_post_types']);
        update_option('baachal_indexable_post_types', $indexable_types);
    } else {
        update_option('baachal_indexable_post_types', array());
    }
    
    if (isset($_POST['baachal_content_max_results'])) {
        $max_results = intval($_POST['baachal_content_max_results']);
        $max_results = max(1, min(20, $max_results)); // Ensure between 1-20
        update_option('baachal_content_max_results', $max_results);
    }
    
    // Clear dynamic terms cache when settings change
    delete_transient('baachal_dynamic_terms');
    
    // Allow other plugins to save additional settings
    do_action('baachal_save_additional_settings', $_POST);
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current values
$ai_provider = get_option('baachal_ai_provider', 'gemini');
$gemini_api_key = get_option('baachal_gemini_api_key', '');
$openai_api_key = get_option('baachal_openai_api_key', '');
$claude_api_key = get_option('baachal_claude_api_key', '');
$grok_api_key = get_option('baachal_grok_api_key', '');
$enabled = get_option('baachal_enabled', '1');
$chatbot_title = get_option('baachal_title', 'AI Chat Bot');
$welcome_message = get_option('baachal_welcome_message', 'Hello! How can I help you today?');
$debug_mode = get_option('baachal_debug_mode', '0');
$ai_model = get_option('baachal_ai_model', 'gemini-2.0-flash-exp');
$woocommerce_integration = get_option('baachal_woocommerce_integration', '1');
$message_persistence = get_option('baachal_message_persistence', '1');
$show_clear_history = get_option('baachal_show_clear_history', '1');

// Product search settings
$max_terms = get_option('baachal_max_terms', 50);
$min_term_length = get_option('baachal_min_term_length', 3);
$cache_duration = get_option('baachal_cache_duration', DAY_IN_SECONDS);
$exclude_terms = get_option('baachal_exclude_terms', array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'size', 'color', 'item', 'product'));

// Convert exclude terms to comma-separated string for display
if (is_array($exclude_terms)) {
    $exclude_terms_string = implode(', ', $exclude_terms);
} else {
    $exclude_terms_string = $exclude_terms;
}

// UI styling settings
$primary_color = get_option('baachal_primary_color', '#007cba');
$secondary_color = get_option('baachal_secondary_color', '#f1f1f1');
$position = get_option('baachal_position', 'bottom-right');
$size = get_option('baachal_size', 'medium');
$border_radius = get_option('baachal_border_radius', 15);
$chat_height = get_option('baachal_chat_height', 400);
$font_size = get_option('baachal_font_size', 14);
$animation_enabled = get_option('baachal_animation_enabled', '1');

// Allow other plugins to modify default values
$enabled = apply_filters('baachal_setting_enabled', $enabled);
$ai_provider = apply_filters('baachal_setting_ai_provider', $ai_provider);
$gemini_api_key = apply_filters('baachal_setting_gemini_api_key', $gemini_api_key);
$openai_api_key = apply_filters('baachal_setting_openai_api_key', $openai_api_key);
$claude_api_key = apply_filters('baachal_setting_claude_api_key', $claude_api_key);
$grok_api_key = apply_filters('baachal_setting_grok_api_key', $grok_api_key);

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

?>


<div class="wrap">
    <h1>Baachal AI bot Settings</h1>
    
    <?php
    // Define tabs
    $tabs = array(
        'general' => 'General',
        'woocommerce' => 'WooCommerce',
        'search' => 'Product Search',
        'content' => 'Content Indexing',
        'styling' => 'UI Styling',
        'advanced' => 'Advanced'
    );
    
    // Allow other plugins to add tabs
    $tabs = apply_filters('baachal_settings_tabs', $tabs);
    ?>
    
    <div class="baachal-tabs">
        <?php foreach ($tabs as $tab_id => $tab_name): ?>
            <a href="#" data-tab="<?php echo esc_attr($tab_id); ?>" 
               class="nav-tab">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('baachal_settings'); ?>
        
        <div class="baachal-tab-content">
            <?php
            // Allow other plugins to add content before tabs
            do_action('baachal_before_settings_tabs', $current_tab);
            ?>
            
            <div id="tab-general" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/general.php'; ?>
            </div>
            
            <div id="tab-woocommerce" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/woocommerce.php'; ?>
            </div>
            
            <div id="tab-search" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/search.php'; ?>
            </div>
            
            <div id="tab-content" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/content.php'; ?>
            </div>
            
            <div id="tab-styling" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/styling.php'; ?>
            </div>
            
            <div id="tab-advanced" class="baachal-tab-panel">
                <?php include BAACHAL_PLUGIN_PATH . 'admin/tabs/advanced.php'; ?>
            </div>
            
            <?php
            // Allow other plugins to add custom tabs
            do_action('baachal_settings_tab_content', $current_tab);
            
            // Allow other plugins to add content after tabs
            do_action('baachal_after_settings_tabs', $current_tab);
            ?>
        </div>
        
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
</div>

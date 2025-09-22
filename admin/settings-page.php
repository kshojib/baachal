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
    
    // General Settings
    update_option('chatbot_gemini_api_key', sanitize_text_field($_POST['chatbot_gemini_api_key']));
    update_option('chatbot_enabled', isset($_POST['chatbot_enabled']) ? '1' : '0');
    update_option('chatbot_title', sanitize_text_field($_POST['chatbot_title']));
    update_option('chatbot_welcome_message', sanitize_textarea_field($_POST['chatbot_welcome_message']));
    update_option('chatbot_gemini_model', sanitize_text_field($_POST['chatbot_gemini_model']));
    update_option('chatbot_debug_mode', isset($_POST['chatbot_debug_mode']) ? '1' : '0');
    
    // WooCommerce Settings
    update_option('chatbot_woocommerce_integration', isset($_POST['chatbot_woocommerce_integration']) ? '1' : '0');
    update_option('chatbot_message_persistence', isset($_POST['chatbot_message_persistence']) ? '1' : '0');
    update_option('chatbot_show_clear_history', isset($_POST['chatbot_show_clear_history']) ? '1' : '0');
    
    // Product Search Settings
    if (isset($_POST['chatbot_max_terms'])) {
        update_option('chatbot_max_terms', intval($_POST['chatbot_max_terms']));
    }
    if (isset($_POST['chatbot_min_term_length'])) {
        update_option('chatbot_min_term_length', intval($_POST['chatbot_min_term_length']));
    }
    if (isset($_POST['chatbot_cache_duration'])) {
        update_option('chatbot_cache_duration', intval($_POST['chatbot_cache_duration']));
    }
    
    // Handle exclude terms as comma-separated values
    if (isset($_POST['chatbot_exclude_terms'])) {
        $exclude_terms = sanitize_textarea_field($_POST['chatbot_exclude_terms']);
        $exclude_terms_array = array_map('trim', explode(',', $exclude_terms));
        update_option('chatbot_exclude_terms', $exclude_terms_array);
    }
    
    // UI Styling Settings
    if (isset($_POST['chatbot_primary_color'])) {
        update_option('chatbot_primary_color', sanitize_hex_color($_POST['chatbot_primary_color']));
    }
    if (isset($_POST['chatbot_secondary_color'])) {
        update_option('chatbot_secondary_color', sanitize_hex_color($_POST['chatbot_secondary_color']));
    }
    if (isset($_POST['chatbot_position'])) {
        update_option('chatbot_position', sanitize_text_field($_POST['chatbot_position']));
    }
    if (isset($_POST['chatbot_size'])) {
        update_option('chatbot_size', sanitize_text_field($_POST['chatbot_size']));
    }
    if (isset($_POST['chatbot_border_radius'])) {
        update_option('chatbot_border_radius', intval($_POST['chatbot_border_radius']));
    }
    if (isset($_POST['chatbot_chat_height'])) {
        update_option('chatbot_chat_height', intval($_POST['chatbot_chat_height']));
    }
    if (isset($_POST['chatbot_font_size'])) {
        update_option('chatbot_font_size', intval($_POST['chatbot_font_size']));
    }
    if (isset($_POST['chatbot_animation_enabled'])) {
        update_option('chatbot_animation_enabled', '1');
    } else {
        update_option('chatbot_animation_enabled', '0');
    }
    
    // Clear dynamic terms cache when settings change
    delete_transient('baachal_dynamic_terms');
    
    // Allow other plugins to save additional settings
    do_action('baachal_save_additional_settings', $_POST);
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current values
$api_key = get_option('chatbot_gemini_api_key', '');
$enabled = get_option('chatbot_enabled', '1');
$chatbot_title = get_option('chatbot_title', 'AI Chat Bot');
$welcome_message = get_option('chatbot_welcome_message', 'Hello! How can I help you today?');
$debug_mode = get_option('chatbot_debug_mode', '0');
$gemini_model = get_option('chatbot_gemini_model', 'gemini-2.0-flash-exp');
$woocommerce_integration = get_option('chatbot_woocommerce_integration', '1');
$message_persistence = get_option('chatbot_message_persistence', '1');
$show_clear_history = get_option('chatbot_show_clear_history', '1');

// Product search settings
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

// UI styling settings
$primary_color = get_option('chatbot_primary_color', '#007cba');
$secondary_color = get_option('chatbot_secondary_color', '#f1f1f1');
$position = get_option('chatbot_position', 'bottom-right');
$size = get_option('chatbot_size', 'medium');
$border_radius = get_option('chatbot_border_radius', 15);
$chat_height = get_option('chatbot_chat_height', 400);
$font_size = get_option('chatbot_font_size', 14);
$animation_enabled = get_option('chatbot_animation_enabled', '1');

// Allow other plugins to modify default values
$api_key = apply_filters('baachal_setting_api_key', $api_key);
$enabled = apply_filters('baachal_setting_enabled', $enabled);

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

?>

<style>
.baachal-tabs {
    border-bottom: 1px solid #ccd0d4;
    margin: 0 0 20px 0;
    padding: 0;
}

.baachal-tabs .nav-tab {
    border: 1px solid #ccd0d4;
    border-bottom: none;
    background: #f1f1f1;
    color: #555;
    text-decoration: none;
    padding: 10px 15px;
    margin: 0 5px 0 0;
    display: inline-block;
    position: relative;
    top: 1px;
}

.baachal-tabs .nav-tab:hover {
    background: #fafafa;
}

.baachal-tabs .nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
    color: #000;
}

.baachal-tab-content {
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.baachal-tab-panel {
    display: none;
}

.baachal-tab-panel.active {
    display: block;
}

.baachal-color-preview {
    width: 30px;
    height: 30px;
    border: 1px solid #ccd0d4;
    border-radius: 3px;
    display: inline-block;
    vertical-align: middle;
    margin-left: 10px;
}

.baachal-setting-row {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.baachal-setting-row:last-child {
    border-bottom: none;
}

.baachal-setting-label {
    font-weight: 600;
    margin-bottom: 5px;
    display: block;
}

.baachal-setting-description {
    color: #666;
    font-style: italic;
    margin-top: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    function switchTab(tabId) {
        // Hide all tab panels
        document.querySelectorAll('.baachal-tab-panel').forEach(function(panel) {
            panel.classList.remove('active');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.nav-tab').forEach(function(tab) {
            tab.classList.remove('nav-tab-active');
        });
        
        // Show the selected tab panel
        const targetPanel = document.getElementById('tab-' + tabId);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }
        
        // Add active class to clicked tab
        const targetTab = document.querySelector('.nav-tab[data-tab="' + tabId + '"]');
        if (targetTab) {
            targetTab.classList.add('nav-tab-active');
        }
        
        // Store active tab in localStorage
        localStorage.setItem('baachal_active_tab', tabId);
    }
    
    // Set up tab click handlers
    document.querySelectorAll('.nav-tab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const tabId = this.getAttribute('data-tab');
            switchTab(tabId);
        });
    });
    
    // Restore active tab from localStorage or use default
    const savedTab = localStorage.getItem('baachal_active_tab') || 'general';
    switchTab(savedTab);
    
    // Update color previews
    function updateColorPreview(input) {
        const preview = input.parentNode.querySelector('.baachal-color-preview');
        if (preview) {
            preview.style.backgroundColor = input.value;
        }
    }
    
    // Initialize color previews
    document.querySelectorAll('input[type="color"]').forEach(function(input) {
        updateColorPreview(input);
        input.addEventListener('change', function() {
            updateColorPreview(this);
        });
    });
});
</script>

<div class="wrap">
    <h1>Baachal AI bot Settings</h1>
    
    <?php
    // Define tabs
    $tabs = array(
        'general' => 'General',
        'woocommerce' => 'WooCommerce',
        'search' => 'Product Search',
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
        <?php wp_nonce_field('chatbot_settings'); ?>
        
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
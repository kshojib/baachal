<?php
/**
 * Plugin Name: Baachal AI Chatbot
 * Plugin URI: https://github.com/kshojib/baachal
 * Description: Intelligent AI chatbot powered by Google Gemini API. Provides customer support with deep knowledge of your website content, products, and services. Features automatic content indexing, customizable styling, and seamless WooCommerce integration.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: Shojib Khan
 * Author URI: https://www.shojibkhan.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: baachal
 * Domain Path: /languages
 * Network: false
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 * 
 * @package Baachal
 * @author Shojib Khan
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BAACHAL_VERSION', '1.0.0');
define('BAACHAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BAACHAL_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include required files
require_once BAACHAL_PLUGIN_PATH . 'includes/content-indexer.php';

// Declare WooCommerce HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Main plugin class
class Baachal {
    
    public function __construct() {
        // Allow other plugins to modify the hooks that get registered
        $hooks = apply_filters('baachal_register_hooks', array(
            'init' => array($this, 'init'),
            'wp_enqueue_scripts' => array($this, 'enqueue_scripts'),
            'wp_footer' => array($this, 'add_chatbot_html'),
            'wp_ajax_chatbot_message' => array($this, 'handle_chatbot_message'),
            'wp_ajax_nopriv_chatbot_message' => array($this, 'handle_chatbot_message'),
            'wp_ajax_get_chat_history' => array($this, 'get_chat_history'),
            'wp_ajax_nopriv_get_chat_history' => array($this, 'get_chat_history'),
            'wp_ajax_clear_chat_history' => array($this, 'clear_chat_history'),
            'wp_ajax_nopriv_clear_chat_history' => array($this, 'clear_chat_history'),
            'wp_ajax_clear_baachal_cache' => array($this, 'handle_clear_cache_ajax'),
            'wp_ajax_baachal_test_content_search' => array($this, 'handle_test_content_search'),
            'wp_ajax_baachal_reindex_content' => array($this, 'handle_reindex_content_ajax'),
            'admin_menu' => array($this, 'add_admin_menu'),
            'admin_init' => array($this, 'register_settings'),
            'add_meta_boxes' => array($this, 'add_chat_meta_boxes')
        ));
        
        // Register all hooks
        foreach ($hooks as $hook => $callback) {
            add_action($hook, $callback);
        }
        
        // Add settings link to plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        register_uninstall_hook(__FILE__, array('Baachal', 'uninstall_plugin'));
        
        // Clear product cache when products are updated
        add_action('save_post', array($this, 'clear_product_cache_on_update'));
        add_action('delete_post', array($this, 'clear_product_cache_on_update'));
        add_action('wp_trash_post', array($this, 'clear_product_cache_on_update'));
        add_action('untrash_post', array($this, 'clear_product_cache_on_update'));
        
        // Clear cache when taxonomies are updated
        add_action('created_term', array($this, 'clear_dynamic_terms_cache'));
        add_action('edited_term', array($this, 'clear_dynamic_terms_cache'));
        add_action('delete_term', array($this, 'clear_dynamic_terms_cache'));
        
        // Clear cache when product attributes are updated
        add_action('woocommerce_attribute_added', array($this, 'clear_dynamic_terms_cache'));
        add_action('woocommerce_attribute_updated', array($this, 'clear_dynamic_terms_cache'));
        add_action('woocommerce_attribute_deleted', array($this, 'clear_dynamic_terms_cache'));
        
        // Allow other plugins to perform actions after plugin initialization
        do_action('baachal_init', $this);
    }
    
    public function clear_dynamic_terms_cache() {
        delete_transient('baachal_dynamic_terms');
    }
    
    public function clear_product_cache_on_update($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'product') {
            // Clear all product search cache
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_baachal_products_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_baachal_products_%'");
            
            // Clear dynamic terms cache as product catalog has changed
            delete_transient('baachal_dynamic_terms');
        }
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('baachal', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Allow other plugins to perform actions before initialization
        do_action('baachal_before_init');
        
        // Register custom post type for chat conversations
        $this->register_chat_post_type();
        
        // Allow other plugins to perform actions after initialization
        do_action('baachal_after_init');
    }
    
    public function activate_plugin() {
        // Register post type
        $this->register_chat_post_type();
        
        // Create content index table
        $indexer = new Baachal_Content_Indexer();
        $indexer->create_index_table();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Allow other plugins to perform activation tasks
        do_action('baachal_plugin_activated');
    }
    
    public function deactivate_plugin() {
        // Clear all transients
        $this->clear_all_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Allow other plugins to perform deactivation tasks
        do_action('baachal_plugin_deactivated');
    }
    
    public static function uninstall_plugin() {
        // Only proceed if user has proper permissions
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Check if we should preserve data
        $preserve_data = get_option('baachal_preserve_data_on_uninstall', '1');
        
        if ($preserve_data !== '1') {
            global $wpdb;
            
            // Delete all plugin options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'baachal_%' OR option_name LIKE 'chatbot_%'");
            
            // Delete content index table
            $index_table = $wpdb->prefix . 'baachal_content_index';
            $wpdb->query("DROP TABLE IF EXISTS {$index_table}");
            
            // Delete all conversations
            $conversations = get_posts(array(
                'post_type' => 'chatbot_conversation',
                'numberposts' => -1,
                'post_status' => 'any'
            ));
            
            foreach ($conversations as $conversation) {
                wp_delete_post($conversation->ID, true);
            }
            
            // Clear all transients
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_baachal_%' OR option_name LIKE '_transient_timeout_baachal_%'");
            
            // Allow other plugins to perform cleanup
            do_action('baachal_plugin_uninstalled');
        }
    }
    
    private function set_default_options() {
        // Set default options if they don't exist
        $defaults = array(
            'chatbot_enabled' => '1',
            'chatbot_welcome_message' => 'Hello! How can I help you today?',
            'chatbot_title' => 'Baachal AI Bot',
            'chatbot_gemini_model' => 'gemini-2.0-flash-exp',
            'chatbot_position' => 'bottom-right',
            'chatbot_size' => 'medium',
            'chatbot_primary_color' => '#007cba',
            'chatbot_secondary_color' => '#f0f0f1',
            'chatbot_border_radius' => '12',
            'chatbot_chat_height' => '400',
            'chatbot_font_size' => '14',
            'chatbot_animation_enabled' => '1',
            'chatbot_woocommerce_integration' => '1',
            'chatbot_message_persistence' => '1',
            'chatbot_show_clear_history' => '1',
            'chatbot_max_terms' => '50',
            'chatbot_min_term_length' => '3',
            'chatbot_cache_duration' => DAY_IN_SECONDS,
            'baachal_content_indexing_enabled' => '1',
            'baachal_auto_index' => '1',
            'baachal_content_max_results' => '5',
            'baachal_indexable_post_types' => array('post', 'page'),
            'baachal_preserve_data_on_uninstall' => '1'
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                update_option($option, $value);
            }
        }
    }
    
    private function clear_all_transients() {
        global $wpdb;
        
        // Clear all Baachal-related transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_baachal_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_baachal_%'");
        
        // Clear dynamic terms cache
        delete_transient('baachal_dynamic_terms');
    }
    
    public function register_chat_post_type() {
        $args = array(
            'label' => 'Baachal Conversations',
            'labels' => array(
                'name' => 'Baachal Conversations',
                'singular_name' => 'Baachal Conversation',
                'menu_name' => 'Baachal Conversations',
                'all_items' => 'All Conversations',
                'view_item' => 'View Conversation',
                'search_items' => 'Search Conversations',
                'not_found' => 'No conversations found',
                'not_found_in_trash' => 'No conversations found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => array('title', 'custom-fields'),
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
            ),
            'map_meta_cap' => true,
        );
        
        register_post_type('chatbot_conversation', $args);
    }
    
    public function enqueue_scripts() {
        // Allow other plugins to modify script dependencies
        $js_dependencies = apply_filters('baachal_js_dependencies', array('jquery'));
        $css_dependencies = apply_filters('baachal_css_dependencies', array());

        wp_enqueue_script('baachal-js', BAACHAL_PLUGIN_URL . 'assets/chatbot.js', $js_dependencies, BAACHAL_VERSION, true);
        wp_enqueue_style('baachal-css', BAACHAL_PLUGIN_URL . 'assets/chatbot.css', $css_dependencies, BAACHAL_VERSION);

        // Add custom styling based on settings
        $this->add_custom_styles();

        // Allow other plugins to modify localized data
        $localized_data = apply_filters('baachal_localized_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_nonce'),
            'debug_mode' => get_option('chatbot_debug_mode', '0'),
            'message_persistence' => get_option('chatbot_message_persistence', '1'),
            'session_id' => $this->get_chat_session_id(),
            'plugin_url' => BAACHAL_PLUGIN_URL
        ));

        // Localize script for AJAX
        wp_localize_script('baachal-js', 'chatbot_ajax', $localized_data);

        // Allow other plugins to enqueue additional scripts/styles
        do_action('baachal_after_enqueue_scripts');
    }
    
    private function add_custom_styles() {
        // Get styling options
        $primary_color = get_option('chatbot_primary_color', '#007cba');
        $secondary_color = get_option('chatbot_secondary_color', '#f1f1f1');
        $border_radius = get_option('chatbot_border_radius', 15);
        $chat_height = get_option('chatbot_chat_height', 400);
        $font_size = get_option('chatbot_font_size', 14);
        
        // Custom CSS using CSS custom properties
        $custom_css = "
            :root {
                --chatbot-primary-color: {$primary_color};
                --chatbot-secondary-color: {$secondary_color};
                --chatbot-border-radius: {$border_radius}px;
                --chatbot-font-size: {$font_size}px;
                --chatbot-chat-height: {$chat_height}px;
            }";

        // Allow other plugins to modify the custom CSS
        $custom_css = apply_filters('baachal_custom_css', $custom_css, array(
            'primary_color' => $primary_color,
            'secondary_color' => $secondary_color,
            'border_radius' => $border_radius,
            'chat_height' => $chat_height,
            'font_size' => $font_size
        ));

        // Add the custom CSS as inline styles to the main chatbot stylesheet
        wp_add_inline_style('baachal-css', $custom_css);
    }
    
    private function darken_color($hex, $percent) {
        // Remove the hash if present
        $hex = str_replace('#', '', $hex);
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Darken
        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    private function lighten_color($hex, $percent) {
        // Remove the hash if present
        $hex = str_replace('#', '', $hex);
        
        // Convert to RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Lighten
        $r = min(255, $r + ((255 - $r) * $percent / 100));
        $g = min(255, $g + ((255 - $g) * $percent / 100));
        $b = min(255, $b + ((255 - $b) * $percent / 100));
        
        // Convert back to hex
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    public function add_chatbot_html() {
        $is_enabled = get_option('chatbot_enabled', '1');
        
        // Allow other plugins to override the enabled state
        $is_enabled = apply_filters('baachal_is_enabled', $is_enabled);
        
        if ($is_enabled !== '1') {
            return;
        }
        
        // Allow other plugins to add content before widget
        do_action('baachal_before_widget_render');
        
        include BAACHAL_PLUGIN_PATH . 'templates/chatbot-widget.php';
        
        // Allow other plugins to add content after widget
        do_action('baachal_after_widget_render');
    }
    
    public function handle_chatbot_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_die('Security check failed');
        }
        
        $message = sanitize_text_field($_POST['message']);
        
        // Allow other plugins to modify the user message before processing
        $message = apply_filters('baachal_before_process_message', $message, $_POST);
        
        // Allow other plugins to handle the message entirely
        $custom_response = apply_filters('baachal_custom_message_handler', null, $message, $_POST);
        
        if ($custom_response !== null) {
            // Custom handler provided a response
            if (is_array($custom_response) && isset($custom_response['success'])) {
                if ($custom_response['success']) {
                    wp_send_json_success($custom_response['data']);
                } else {
                    wp_send_json_error($custom_response['error']);
                }
            } else {
                wp_send_json_success($custom_response);
            }
            return;
        }
        
        $api_key = get_option('chatbot_gemini_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }
        
        // Allow other plugins to modify API parameters
        $api_params = apply_filters('baachal_api_params', array(
            'message' => $message,
            'api_key' => $api_key
        ), $_POST);
        
        $result = $this->call_gemini_api($api_params['message'], $api_params['api_key']);
        
        if ($result['success']) {
            // Allow other plugins to modify the response before saving/sending
            $bot_response = apply_filters('baachal_bot_response', $result['data'], $message, $result);
            
            // Save both user message and bot response
            $this->save_chat_message($message, 'user');
            $this->save_chat_message($bot_response, 'bot');
            
            // Allow other plugins to perform actions after successful response
            do_action('baachal_after_successful_response', $bot_response, $message, $result);
            
            wp_send_json_success($bot_response);
        } else {
            // Allow other plugins to modify error response
            $error_response = apply_filters('baachal_error_response', $result['error'], $message, $result);
            
            // Allow other plugins to perform actions after error
            do_action('baachal_after_error_response', $error_response, $message, $result);
            
            wp_send_json_error($error_response);
        }
    }
    
    private function get_chat_session_id() {
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
        
        // Create or get session ID for chat
        if (!isset($_SESSION['chatbot_session_id'])) {
            $_SESSION['chatbot_session_id'] = 'chat_' . wp_generate_uuid4();
        }
        
        return $_SESSION['chatbot_session_id'];
    }
    
    private function save_chat_message($message, $type) {
        // Allow other plugins to prevent message saving
        $should_save = apply_filters('baachal_should_save_message', true, $message, $type);
        
        if (!$should_save) {
            return;
        }
        
        if (get_option('chatbot_message_persistence', '1') !== '1') {
            return; // Don't save if persistence is disabled
        }
        
        $session_id = $this->get_chat_session_id();
        $user_id = get_current_user_id();
        
        // Allow other plugins to modify message data before saving
        $message_data = apply_filters('baachal_before_save_message', array(
            'message' => $message,
            'type' => $type,
            'session_id' => $session_id,
            'user_id' => $user_id,
            'timestamp' => current_time('mysql')
        ));
        
        // Find or create conversation post
        $conversation_id = $this->get_or_create_conversation($message_data['session_id'], $message_data['user_id']);
        
        if ($conversation_id) {
            // Get existing messages
            $messages = get_post_meta($conversation_id, '_chat_messages', true);
            if (!is_array($messages)) {
                $messages = array();
            }
            
            // Add new message
            $messages[] = $message_data;
            
            // Allow other plugins to modify the message limit
            $message_limit = apply_filters('baachal_message_limit', 100);
            
            // Keep only last X messages per conversation
            if (count($messages) > $message_limit) {
                $messages = array_slice($messages, -$message_limit);
            }
            
            // Update messages
            update_post_meta($conversation_id, '_chat_messages', $messages);
            
            // Update conversation title if it's just the default
            $current_title = get_the_title($conversation_id);
            if (strpos($current_title, 'Chat Session') !== false && $message_data['type'] === 'user') {
                $new_title = apply_filters('baachal_conversation_title', 'Chat: ' . wp_trim_words($message_data['message'], 5, '...'), $message_data['message'], $conversation_id);
                wp_update_post(array(
                    'ID' => $conversation_id,
                    'post_title' => $new_title
                ));
            }
            
            // Allow other plugins to perform actions after saving message
            do_action('baachal_after_save_message', $message_data, $conversation_id, $messages);
        }
    }
    
    private function get_or_create_conversation($session_id, $user_id = 0) {
        // Look for existing conversation with this session ID
        $existing = get_posts(array(
            'post_type' => 'chatbot_conversation',
            'meta_query' => array(
                array(
                    'key' => '_session_id',
                    'value' => $session_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($existing)) {
            return $existing[0]->ID;
        }
        
        // Create new conversation
        $conversation_id = wp_insert_post(array(
            'post_type' => 'chatbot_conversation',
            'post_title' => 'Chat Session ' . date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'post_author' => $user_id ?: 1 // Use user ID or default to admin
        ));
        
        if ($conversation_id && !is_wp_error($conversation_id)) {
            // Store session metadata
            update_post_meta($conversation_id, '_session_id', $session_id);
            update_post_meta($conversation_id, '_user_id', $user_id);
            update_post_meta($conversation_id, '_user_ip', $_SERVER['REMOTE_ADDR']);
            update_post_meta($conversation_id, '_user_agent', $_SERVER['HTTP_USER_AGENT']);
            update_post_meta($conversation_id, '_chat_messages', array());
            
            return $conversation_id;
        }
        
        return false;
    }
    
    public function get_chat_history() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_die('Security check failed');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Get conversation for this session
        $conversation = get_posts(array(
            'post_type' => 'chatbot_conversation',
            'meta_query' => array(
                array(
                    'key' => '_session_id',
                    'value' => $session_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($conversation)) {
            $messages = get_post_meta($conversation[0]->ID, '_chat_messages', true);
            if (is_array($messages)) {
                wp_send_json_success($messages);
                return;
            }
        }
        
        wp_send_json_success(array());
    }
    
    public function handle_clear_cache_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'clear_baachal_cache')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Clear the cache
        delete_transient('baachal_dynamic_terms');
        
        wp_send_json_success('Cache cleared successfully');
    }
    
    public function handle_test_content_search() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'baachal_test_search')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $query = sanitize_text_field($_POST['query']);
        if (empty($query)) {
            wp_send_json_error('Query is required');
            return;
        }
        
        // Get content indexer instance and search
        $indexer = new Baachal_Content_Indexer();
        $results = $indexer->search_content($query, 10);
        
        wp_send_json_success(array('results' => $results));
    }
    
    public function handle_reindex_content_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'baachal_reindex_content')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get content indexer instance and reindex
        $indexer = new Baachal_Content_Indexer();
        $indexed_count = $indexer->index_all_content();
        
        wp_send_json_success(array('count' => $indexed_count));
    }
    
    public function clear_chat_history() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_die('Security check failed');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        // Get conversation for this session
        $conversation = get_posts(array(
            'post_type' => 'chatbot_conversation',
            'meta_query' => array(
                array(
                    'key' => '_session_id',
                    'value' => $session_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($conversation)) {
            // Clear messages but keep the conversation post
            update_post_meta($conversation[0]->ID, '_chat_messages', array());
            wp_send_json_success('Chat history cleared');
        } else {
            wp_send_json_success('No chat history found');
        }
    }
    
    private function call_gemini_api($message, $api_key) {
        $website_context = $this->get_website_context($message);
        $full_prompt = $website_context . "\n\nUser question: " . $message;
        
        // Get selected model from settings
        $selected_model = get_option('chatbot_gemini_model', 'gemini-2.0-flash-exp');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $selected_model . ':generateContent?key=' . $api_key;
        
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $full_prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            )
        );
        
        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . ' Baachal/1.0.0'
        );
        
        $response = wp_remote_post($url, $args);
        
        // Handle network errors
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Network error: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Handle success
        if ($status_code === 200) {
            $decoded = json_decode($body, true);
            
            if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                return array(
                    'success' => true,
                    'data' => $decoded['candidates'][0]['content']['parts'][0]['text']
                );
            }
            
            // Check for safety filters
            if (isset($decoded['candidates'][0]['finishReason'])) {
                $finish_reason = $decoded['candidates'][0]['finishReason'];
                if ($finish_reason === 'SAFETY') {
                    return array(
                        'success' => false,
                        'error' => 'Response was filtered for safety reasons. Please try rephrasing your question.'
                    );
                }
            }
            
            return array(
                'success' => false,
                'error' => 'Unexpected response format from AI service'
            );
        }
        
        // Handle API errors
        $error_data = json_decode($body, true);
        $error_message = 'API error (HTTP ' . $status_code . ')';
        
        if (isset($error_data['error']['message'])) {
            $error_message .= ': ' . $error_data['error']['message'];
            
            // Provide helpful suggestions for common errors
            if (strpos($error_data['error']['message'], 'overloaded') !== false) {
                $error_message .= '. Please try again in a moment.';
            } elseif (strpos($error_data['error']['message'], 'quota') !== false || $status_code === 429) {
                $error_message = 'API quota exceeded. Please check your Google AI Studio usage or try again later.';
            } elseif ($status_code === 400) {
                $error_message .= '. Please check your API key configuration.';
            }
        }
        
        return array(
            'success' => false,
            'error' => $error_message
        );
    }
    
    private function get_website_context($user_message = '') {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $site_url = get_home_url();
        
        $context = "You are Baachal, a helpful assistant for the website '{$site_name}'. ";
        $context .= "Website description: {$site_description}. ";
        $context .= "Website URL: {$site_url}. ";
        
        // Add WooCommerce product information if available and enabled
        if (class_exists('WooCommerce') && get_option('chatbot_woocommerce_integration', '1') === '1') {
            $woocommerce_context = $this->get_woocommerce_context($user_message);
            if (!empty($woocommerce_context)) {
                $context .= $woocommerce_context;
            }
        }
        
        // Add website content context if enabled
        if (get_option('baachal_content_indexing_enabled', '1') === '1') {
            $content_context = $this->get_content_context($user_message);
            if (!empty($content_context)) {
                $context .= $content_context;
            }
        }
        
        $context .= "Please answer questions about this website helpfully and accurately. ";
        $context .= "When mentioning specific products, always include clickable links in this format: [Product Name](URL). ";
        $context .= "When referencing website pages or content, include clickable links in this format: [Page Title](URL). ";
        $context .= "If you don't know something specific about the website, be honest about it. ";
        $context .= "Keep your responses friendly and concise.";
        
        return $context;
    }
    
    private function get_woocommerce_context($user_message = '') {
        if (!class_exists('WooCommerce')) {
            return '';
        }
        
        // Debug logging if enabled
        $debug_mode = get_option('chatbot_debug_mode', '0') === '1';
        
        // If we have a user message, get relevant products using embeddings
        if (!empty($user_message)) {
            if ($debug_mode) {
                error_log('Baachal: Searching for products with message: ' . $user_message);
            }
            
            $relevant_products = $this->get_relevant_products($user_message);
            if (!empty($relevant_products)) {
                if ($debug_mode) {
                    $product_titles = array_map(function($p) { return $p->post_title; }, $relevant_products);
                    error_log('Baachal: Found products: ' . implode(', ', $product_titles));
                }
                return $this->format_products_context($relevant_products);
            } else {
                if ($debug_mode) {
                    error_log('Baachal: No relevant products found for: ' . $user_message);
                }
            }
        }
        
        // Fallback: Get a small sample of recent products
        return $this->get_sample_products_context();
    }
    
    private function get_relevant_products($user_message, $limit = 5) {
        // Create a cache key based on the user message
        $cache_key = 'baachal_products_' . md5(strtolower($user_message)) . '_' . $limit;
        
        // Try to get from cache first (valid for 1 hour)
        $cached_products = get_transient($cache_key);
        if ($cached_products !== false) {
            return $cached_products;
        }
        
        // First, try to find products using WordPress search and keywords
        $products = $this->search_products_by_keywords($user_message, $limit);
        
        if (count($products) < $limit) {
            // If we don't have enough products, add some popular/recent ones
            $additional = $this->get_popular_products($limit - count($products));
            $products = array_merge($products, $additional);
        }
        
        $final_products = array_slice($products, 0, $limit);
        
        // Cache the results for 1 hour
        set_transient($cache_key, $final_products, HOUR_IN_SECONDS);
        
        return $final_products;
    }
    
    private function search_products_by_keywords($user_message, $limit = 5) {
        // Extract keywords from user message - improved version
        $keywords = $this->extract_keywords_improved($user_message);
        
        if (empty($keywords)) {
            return array();
        }
        
        // Multi-stage search for better accuracy
        $products = array();
        
        // Stage 1: Exact phrase search in titles
        $exact_products = $this->search_by_exact_phrase($user_message, $limit);
        $products = array_merge($products, $exact_products);
        
        // Stage 2: Search by individual keywords with weighted scoring
        if (count($products) < $limit) {
            $keyword_products = $this->search_by_weighted_keywords($keywords, $limit - count($products));
            $products = array_merge($products, $keyword_products);
        }
        
        // Stage 3: Search product attributes and variations
        if (count($products) < $limit) {
            $attribute_products = $this->search_by_attributes($keywords, $limit - count($products));
            $products = array_merge($products, $attribute_products);
        }
        
        // Stage 4: Search categories and tags
        if (count($products) < $limit) {
            $taxonomy_products = $this->search_by_taxonomies($keywords, $limit - count($products));
            $products = array_merge($products, $taxonomy_products);
        }
        
        // Remove duplicates and return
        $unique_products = array();
        $seen_ids = array();
        
        foreach ($products as $product) {
            if (!in_array($product->ID, $seen_ids)) {
                $seen_ids[] = $product->ID;
                $unique_products[] = $product;
            }
        }
        
        return array_slice($unique_products, 0, $limit);
    }
    
    private function extract_keywords_improved($message) {
        // Convert to lowercase but preserve important product terms
        $message = strtolower($message);
        
        // Get dynamic important terms from the store's products
        $important_terms = $this->get_dynamic_product_terms();
        
        // Extract multi-word important terms first
        $extracted_terms = array();
        foreach ($important_terms as $term) {
            if (strpos($message, $term) !== false) {
                $extracted_terms[] = $term;
                // Replace with placeholder to avoid double processing
                $message = str_replace($term, ' __TERM__ ', $message);
            }
        }
        
        // Clean up remaining text and split into words
        $message = preg_replace('/[^a-z0-9\s]/', ' ', $message);
        $words = explode(' ', $message);
        
        // Minimal stop words - keep product-related terms
        $stop_words = array(
            'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'him', 'his', 'she', 'her',
            'it', 'its', 'they', 'them', 'their', 'this', 'that', 'these', 'those',
            'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had',
            'do', 'does', 'did', 'a', 'an', 'the', 'and', 'but', 'if', 'or', 'as',
            'of', 'at', 'by', 'for', 'with', 'in', 'on', 'to', 'from',
            'here', 'there', 'when', 'where', 'how', 'all', 'any', 'some', 'no', 'not',
            'can', 'will', 'just', 'should', 'now', '__term__'
        );
        
        // Filter words
        $keywords = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stop_words)) {
                $keywords[] = $word;
            }
        }
        
        // Combine extracted terms with individual keywords
        return array_unique(array_merge($extracted_terms, $keywords));
    }
    
    private function get_dynamic_product_terms() {
        // Cache key for product terms
        $cache_key = 'baachal_dynamic_terms';
        
        // Try to get from cache first
        $cached_terms = get_transient($cache_key);
        if ($cached_terms !== false) {
            return $cached_terms;
        }
        
        $important_terms = array();
        
        // Get configurable settings with defaults
        $max_terms = get_option('chatbot_max_terms', 50);
        $min_term_length = get_option('chatbot_min_term_length', 3);
        $cache_duration = get_option('chatbot_cache_duration', DAY_IN_SECONDS);
        $exclude_terms = get_option('chatbot_exclude_terms', array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'size', 'color', 'item', 'product'));
        
        // Ensure exclude_terms is an array
        if (!is_array($exclude_terms)) {
            $exclude_terms = explode(',', $exclude_terms);
            $exclude_terms = array_map('trim', $exclude_terms);
        }
        
        // Get common terms from product categories
        $category_terms = $this->extract_category_terms($exclude_terms, $min_term_length);
        $important_terms = array_merge($important_terms, $category_terms);
        
        // Get common terms from product attributes
        $attribute_terms = $this->extract_attribute_terms($exclude_terms, $min_term_length);
        $important_terms = array_merge($important_terms, $attribute_terms);
        
        // Get common terms from product tags
        $tag_terms = $this->extract_tag_terms($exclude_terms, $min_term_length);
        $important_terms = array_merge($important_terms, $tag_terms);
        
        // Get common multi-word phrases from product titles
        $title_phrases = $this->extract_title_phrases($exclude_terms, $min_term_length);
        $important_terms = array_merge($important_terms, $title_phrases);
        
        // Remove duplicates and sort by length (longer terms first)
        $important_terms = array_unique($important_terms);
        usort($important_terms, function($a, $b) {
            return strlen($b) - strlen($a);
        });
        
        // Limit to max terms
        if (count($important_terms) > $max_terms) {
            $important_terms = array_slice($important_terms, 0, $max_terms);
        }
        
        // Allow store owners to add custom terms via filter
        $important_terms = apply_filters('baachal_important_product_terms', $important_terms);
        
        // Cache for configured duration
        set_transient($cache_key, $important_terms, $cache_duration);
        
        return $important_terms;
    }
    
    private function extract_category_terms($exclude_terms = array(), $min_term_length = 3) {
        $terms = array();
        
        // Get all product categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 50 // Limit to prevent too many terms
        ));
        
        foreach ($categories as $category) {
            $name = strtolower($category->name);
            if (strlen($name) >= $min_term_length && !in_array($name, $exclude_terms)) {
                $terms[] = $name;
                
                // Also add individual words from multi-word categories
                $words = explode(' ', $name);
                foreach ($words as $word) {
                    $word = trim($word);
                    if (strlen($word) >= $min_term_length && !in_array($word, $exclude_terms)) {
                        $terms[] = $word;
                    }
                }
            }
        }
        
        return $terms;
    }
    
    private function extract_attribute_terms($exclude_terms = array(), $min_term_length = 3) {
        global $wpdb;
        
        $terms = array();
        
        // Get all product attributes
        $attributes = $wpdb->get_results("
            SELECT DISTINCT meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE 'attribute_%' 
            AND meta_value != ''
            LIMIT 200
        ");
        
        foreach ($attributes as $attr) {
            // Clean attribute name
            $attr_name = str_replace('attribute_pa_', '', $attr->meta_key);
            $attr_name = str_replace('attribute_', '', $attr_name);
            $attr_name = str_replace('-', ' ', $attr_name);
            $attr_name = str_replace('_', ' ', $attr_name);
            
            if (strlen($attr_name) >= $min_term_length && !in_array(strtolower($attr_name), $exclude_terms)) {
                $terms[] = strtolower($attr_name);
            }
            
            // Clean attribute value
            $attr_value = str_replace('-', ' ', $attr->meta_value);
            $attr_value = str_replace('_', ' ', $attr_value);
            
            if (strlen($attr_value) >= $min_term_length && !in_array(strtolower($attr_value), $exclude_terms)) {
                $terms[] = strtolower($attr_value);
            }
        }
        
        return $terms;
    }
    
    private function extract_tag_terms($exclude_terms = array(), $min_term_length = 3) {
        $terms = array();
        
        // Get all product tags
        $tags = get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
            'number' => 50
        ));
        
        foreach ($tags as $tag) {
            $name = strtolower($tag->name);
            if (strlen($name) >= $min_term_length && !in_array($name, $exclude_terms)) {
                $terms[] = $name;
            }
        }
        
        return $terms;
    }
    
    private function extract_title_phrases($exclude_terms = array(), $min_term_length = 3) {
        global $wpdb;
        
        $terms = array();
        
        // Get common phrases from product titles
        $titles = $wpdb->get_col("
            SELECT post_title 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' 
            AND post_status = 'publish'
            ORDER BY post_date DESC
            LIMIT 100
        ");
        
        $phrase_counts = array();
        
        foreach ($titles as $title) {
            $title = strtolower($title);
            // Extract 2-3 word phrases
            $words = explode(' ', $title);
            
            for ($i = 0; $i < count($words) - 1; $i++) {
                // 2-word phrases
                if (isset($words[$i + 1])) {
                    $phrase = trim($words[$i] . ' ' . $words[$i + 1]);
                    if (strlen($phrase) >= $min_term_length * 2 && !$this->is_common_phrase($phrase, $exclude_terms)) {
                        $phrase_counts[$phrase] = isset($phrase_counts[$phrase]) ? $phrase_counts[$phrase] + 1 : 1;
                    }
                }
                
                // 3-word phrases
                if (isset($words[$i + 2])) {
                    $phrase = trim($words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2]);
                    if (strlen($phrase) >= $min_term_length * 3 && !$this->is_common_phrase($phrase, $exclude_terms)) {
                        $phrase_counts[$phrase] = isset($phrase_counts[$phrase]) ? $phrase_counts[$phrase] + 1 : 1;
                    }
                }
            }
        }
        
        // Keep phrases that appear at least twice
        foreach ($phrase_counts as $phrase => $count) {
            if ($count >= 2) {
                $terms[] = $phrase;
            }
        }
        
        return $terms;
    }
    
    private function is_common_phrase($phrase, $exclude_terms = array()) {
        $common_phrases = array(
            'on sale', 'for sale', 'in stock', 'out of', 'the best', 'and more',
            'with free', 'free shipping', 'buy now', 'add to', 'view all'
        );
        
        // Merge with custom exclude terms
        $all_excludes = array_merge($common_phrases, $exclude_terms);
        
        return in_array($phrase, $all_excludes);
    }
    
    private function search_by_exact_phrase($phrase, $limit) {
        // Clean the phrase but keep it as a whole
        $clean_phrase = preg_replace('/[^a-zA-Z0-9\s-]/', '', $phrase);
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            's' => '"' . $clean_phrase . '"', // Exact phrase search
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            )
        );
        
        return get_posts($args);
    }
    
    private function search_by_weighted_keywords($keywords, $limit) {
        global $wpdb;
        
        if (empty($keywords)) {
            return array();
        }
        
        // Create a weighted search query
        $title_conditions = array();
        $content_conditions = array();
        
        foreach ($keywords as $keyword) {
            $keyword = $wpdb->esc_like($keyword);
            $title_conditions[] = "post_title LIKE '%{$keyword}%'";
            $content_conditions[] = "post_content LIKE '%{$keyword}%'";
        }
        
        $title_sql = implode(' OR ', $title_conditions);
        $content_sql = implode(' OR ', $content_conditions);
        
        // Weighted scoring: title matches worth more than content matches
        $sql = "
            SELECT p.ID, p.post_title,
                   (CASE WHEN ({$title_sql}) THEN 2 ELSE 0 END) +
                   (CASE WHEN ({$content_sql}) THEN 1 ELSE 0 END) as relevance_score
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'product'
            AND p.post_status = 'publish'
            AND (({$title_sql}) OR ({$content_sql}))
            ORDER BY relevance_score DESC, p.post_date DESC
            LIMIT {$limit}
        ";
        
        $results = $wpdb->get_results($sql);
        $products = array();
        
        foreach ($results as $result) {
            $product = get_post($result->ID);
            if ($product) {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    private function search_by_attributes($keywords, $limit) {
        global $wpdb;
        
        if (empty($keywords)) {
            return array();
        }
        
        // Search in product attributes (like color, material, etc.)
        $attribute_conditions = array();
        foreach ($keywords as $keyword) {
            $keyword = $wpdb->esc_like($keyword);
            $attribute_conditions[] = "meta_value LIKE '%{$keyword}%'";
        }
        
        if (empty($attribute_conditions)) {
            return array();
        }
        
        $conditions_sql = implode(' OR ', $attribute_conditions);
        
        $sql = "
            SELECT DISTINCT p.ID, p.post_title
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('product', 'product_variation')
            AND p.post_status = 'publish'
            AND pm.meta_key LIKE 'attribute_%'
            AND ({$conditions_sql})
            ORDER BY p.post_date DESC
            LIMIT {$limit}
        ";
        
        $results = $wpdb->get_results($sql);
        $products = array();
        
        foreach ($results as $result) {
            // If it's a variation, get the parent product
            $product_id = $result->ID;
            $product = get_post($product_id);
            
            if ($product && $product->post_type === 'product_variation') {
                $parent_id = wp_get_post_parent_id($product_id);
                if ($parent_id) {
                    $product = get_post($parent_id);
                }
            }
            
            if ($product && $product->post_type === 'product') {
                $products[] = $product;
            }
        }
        
        return $products;
    }
    
    private function search_by_taxonomies($keywords, $limit) {
        if (empty($keywords)) {
            return array();
        }
        
        // Search in product categories and tags
        $tax_query = array('relation' => 'OR');
        
        foreach ($keywords as $keyword) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field' => 'name',
                'terms' => $keyword,
                'operator' => 'LIKE'
            );
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field' => 'name', 
                'terms' => $keyword,
                'operator' => 'LIKE'
            );
        }
        
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'tax_query' => $tax_query,
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            )
        );
        
        return get_posts($args);
    }
    
    private function get_popular_products($limit = 5) {
        // Get best-selling or most viewed products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            ),
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        );
        
        $products = get_posts($args);
        
        // If no sales data, get recent products
        if (empty($products)) {
            $args['meta_key'] = '';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            $products = get_posts($args);
        }
        
        return $products;
    }
    
    private function get_sample_products_context() {
        // Get just a small sample of products for general context
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 8, // Much smaller sample
            'post_status' => 'publish',
            'orderby' => 'rand', // Random sample
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            )
        );
        
        $products = get_posts($args);
        return $this->format_products_context($products);
    }
    
    private function format_products_context($products) {
        if (empty($products)) {
            return '';
        }
        
        $context = "\n\nThis is a WooCommerce online store. Here are the most relevant products matching your query:\n";
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product && $wc_product->is_visible()) {
                $price = $wc_product->get_price_html();
                $categories = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'names'));
                $category_names = !empty($categories) ? implode(', ', $categories) : 'Uncategorized';
                
                $product_info = "- **{$product->post_title}**";
                $product_info .= " | Price: {$price}";
                $product_info .= " | Category: {$category_names}";
                
                // Add product attributes if available
                $attributes = $this->get_product_attributes($wc_product);
                if (!empty($attributes)) {
                    $product_info .= " | " . implode(', ', $attributes);
                }
                
                $product_info .= " | URL: " . get_permalink($product->ID);
                
                // Add short description if available
                if ($wc_product->get_short_description()) {
                    $short_desc = wp_strip_all_tags($wc_product->get_short_description());
                    $short_desc = substr($short_desc, 0, 100) . (strlen($short_desc) > 100 ? '...' : '');
                    $product_info .= " | Description: {$short_desc}";
                }
                
                // Add stock status
                $stock_status = $wc_product->get_stock_status();
                if ($stock_status === 'outofstock') {
                    $product_info .= " | **OUT OF STOCK**";
                } elseif ($stock_status === 'onbackorder') {
                    $product_info .= " | Available on backorder";
                }
                
                $context .= $product_info . "\n";
            }
        }
        
        $context .= "\nInstructions for product recommendations:\n";
        $context .= "- These are the most relevant products based on the user's query\n";
        $context .= "- ALWAYS include product URLs as clickable links: [Product Name](URL)\n";
        $context .= "- If the user asks for something not in this list, suggest the closest alternatives\n";
        $context .= "- Mention that there may be more products available and suggest browsing categories\n";
        $context .= "- Focus on helping the customer find what they need from these relevant options\n";
        $context .= "- Pay attention to product attributes (material, color, size, etc.) when making recommendations\n";
        
        return $context;
    }
    
    private function get_content_context($user_message = '') {
        // Debug logging if enabled
        $debug_mode = get_option('chatbot_debug_mode', '0') === '1';
        
        if (empty($user_message)) {
            return '';
        }
        
        // Get content indexer instance
        $indexer = new Baachal_Content_Indexer();
        
        // Get max results from settings
        $max_results = get_option('baachal_content_max_results', 5);
        
        // Search for relevant content
        $relevant_content = $indexer->search_content($user_message, $max_results);
        
        if (empty($relevant_content)) {
            if ($debug_mode) {
                error_log('Baachal: No relevant content found for: ' . $user_message);
            }
            return '';
        }
        
        if ($debug_mode) {
            $content_titles = array_map(function($c) { return $c['title']; }, $relevant_content);
            error_log('Baachal: Found content: ' . implode(', ', $content_titles));
        }
        
        return $this->format_content_context($relevant_content);
    }
    
    private function format_content_context($content_items) {
        if (empty($content_items)) {
            return '';
        }
        
        $context = "\n\nHere is relevant content from this website that may help answer your question:\n";
        
        foreach ($content_items as $item) {
            $context .= "- **{$item['title']}** ({$item['post_type']})";
            
            if (!empty($item['excerpt'])) {
                $excerpt = wp_strip_all_tags($item['excerpt']);
                $excerpt = substr($excerpt, 0, 150) . (strlen($excerpt) > 150 ? '...' : '');
                $context .= " | {$excerpt}";
            }
            
            $context .= " | URL: {$item['url']}\n";
        }
        
        $context .= "\nInstructions for using website content:\n";
        $context .= "- Use this content to provide accurate answers about the website\n";
        $context .= "- ALWAYS include page URLs as clickable links: [Page Title](URL)\n";
        $context .= "- If the content doesn't fully answer the question, be honest about limitations\n";
        $context .= "- Encourage users to visit the linked pages for more detailed information\n";
        $context .= "- Synthesize information from multiple sources when relevant\n";
        
        return $context;
    }
    
    private function get_product_attributes($wc_product) {
        $attributes = array();
        
        if (!$wc_product) {
            return $attributes;
        }
        
        // Get product attributes
        $product_attributes = $wc_product->get_attributes();
        
        foreach ($product_attributes as $attribute) {
            if ($attribute->get_visible()) {
                $attribute_name = $attribute->get_name();
                $attribute_label = wc_attribute_label($attribute_name);
                
                if ($attribute->is_taxonomy()) {
                    $terms = wp_get_post_terms($wc_product->get_id(), $attribute_name, array('fields' => 'names'));
                    if (!empty($terms)) {
                        $attributes[] = $attribute_label . ': ' . implode(', ', $terms);
                    }
                } else {
                    $values = $attribute->get_options();
                    if (!empty($values)) {
                        $attributes[] = $attribute_label . ': ' . implode(', ', $values);
                    }
                }
            }
        }
        
        return $attributes;
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Baachal AI bot Settings',
            'Baachal AI bot',
            'manage_options',
            'baachal',
            array($this, 'admin_page')
        );
    }
    
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=baachal') . '">' . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    public function add_chat_meta_boxes() {
        add_meta_box(
            'chatbot-conversation-details',
            'Conversation Details',
            array($this, 'render_conversation_meta_box'),
            'chatbot_conversation',
            'normal',
            'high'
        );
        
        add_meta_box(
            'chatbot-conversation-messages',
            'Chat Messages',
            array($this, 'render_messages_meta_box'),
            'chatbot_conversation',
            'normal',
            'high'
        );
        
        // Add content indexing meta box to indexable post types
        $indexable_types = get_option('baachal_indexable_post_types', array('post', 'page'));
        foreach ($indexable_types as $post_type) {
            add_meta_box(
                'baachal-content-indexing',
                'Baachal AI Bot - Content Indexing',
                array($this, 'render_content_indexing_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function render_conversation_meta_box($post) {
        $session_id = get_post_meta($post->ID, '_session_id', true);
        $user_id = get_post_meta($post->ID, '_user_id', true);
        $user_ip = get_post_meta($post->ID, '_user_ip', true);
        $user_agent = get_post_meta($post->ID, '_user_agent', true);
        
        echo '<table class="form-table">';
        echo '<tr><th>Session ID:</th><td>' . esc_html($session_id) . '</td></tr>';
        echo '<tr><th>User ID:</th><td>' . ($user_id ? esc_html($user_id) : 'Anonymous') . '</td></tr>';
        echo '<tr><th>IP Address:</th><td>' . esc_html($user_ip) . '</td></tr>';
        echo '<tr><th>User Agent:</th><td>' . esc_html($user_agent) . '</td></tr>';
        echo '<tr><th>Created:</th><td>' . get_the_date('Y-m-d H:i:s', $post->ID) . '</td></tr>';
        echo '</table>';
    }
    
    public function render_messages_meta_box($post) {
        $messages = get_post_meta($post->ID, '_chat_messages', true);
        
        if (empty($messages) || !is_array($messages)) {
            echo '<p>No messages in this conversation yet.</p>';
            return;
        }
        
        echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">';
        
        foreach ($messages as $index => $message) {
            $type = $message['type'];
            $content = $message['message'];
            $timestamp = $message['timestamp'];
            $user_id = isset($message['user_id']) ? $message['user_id'] : '';
            
            $bg_color = ($type === 'user') ? '#e3f2fd' : '#f3e5f5';
            $label = ($type === 'user') ? 'User' : 'Baachal AI bot';
            
            echo '<div style="margin-bottom: 15px; padding: 10px; background: ' . $bg_color . '; border-radius: 5px;">';
            echo '<div style="font-weight: bold; margin-bottom: 5px; color: #333;">';
            echo esc_html($label) . ' <span style="font-weight: normal; color: #666; font-size: 12px;">(' . esc_html($timestamp) . ')</span>';
            echo '</div>';
            echo '<div style="line-height: 1.4;">' . nl2br(esc_html($content)) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<p style="margin-top: 10px;"><strong>Total Messages:</strong> ' . count($messages) . '</p>';
    }
    
    public function render_content_indexing_meta_box($post) {
        wp_nonce_field('baachal_content_indexing', 'baachal_content_indexing_nonce');
        
        $exclude_from_index = get_post_meta($post->ID, '_baachal_exclude_from_index', true);
        
        echo '<p>';
        echo '<label>';
        echo '<input type="checkbox" name="baachal_exclude_from_index" value="1" ' . checked($exclude_from_index, '1', false) . ' />';
        echo ' Exclude from chatbot indexing';
        echo '</label>';
        echo '</p>';
        echo '<p class="description">Check this box to prevent this content from being indexed for chatbot searches.</p>';
        
        // Add save hook
        add_action('save_post', array($this, 'save_content_indexing_meta'));
    }
    
    public function save_content_indexing_meta($post_id) {
        // Check if our nonce is set and verify it
        if (!isset($_POST['baachal_content_indexing_nonce']) || !wp_verify_nonce($_POST['baachal_content_indexing_nonce'], 'baachal_content_indexing')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save the meta value
        if (isset($_POST['baachal_exclude_from_index'])) {
            update_post_meta($post_id, '_baachal_exclude_from_index', '1');
        } else {
            delete_post_meta($post_id, '_baachal_exclude_from_index');
        }
    }
    
    public function register_settings() {
        register_setting('chatbot_settings', 'chatbot_gemini_api_key');
        register_setting('chatbot_settings', 'chatbot_enabled');
        register_setting('chatbot_settings', 'chatbot_welcome_message');
        register_setting('chatbot_settings', 'chatbot_debug_mode');
        register_setting('chatbot_settings', 'chatbot_gemini_model');
        register_setting('chatbot_settings', 'chatbot_woocommerce_integration');
        register_setting('chatbot_settings', 'chatbot_message_persistence');
        register_setting('chatbot_settings', 'chatbot_show_clear_history');
        
        // Product search settings
        register_setting('chatbot_settings', 'chatbot_max_terms');
        register_setting('chatbot_settings', 'chatbot_min_term_length');
        register_setting('chatbot_settings', 'chatbot_cache_duration');
        register_setting('chatbot_settings', 'chatbot_exclude_terms');
        
        // UI styling settings
        register_setting('chatbot_settings', 'chatbot_primary_color');
        register_setting('chatbot_settings', 'chatbot_secondary_color');
        register_setting('chatbot_settings', 'chatbot_position');
        register_setting('chatbot_settings', 'chatbot_size');
        register_setting('chatbot_settings', 'chatbot_border_radius');
        register_setting('chatbot_settings', 'chatbot_chat_height');
        register_setting('chatbot_settings', 'chatbot_font_size');
        register_setting('chatbot_settings', 'chatbot_animation_enabled');
    }
    
    public function admin_page() {
        include BAACHAL_PLUGIN_PATH . 'admin/settings-page.php';
    }
}

// Initialize the plugin
new Baachal();

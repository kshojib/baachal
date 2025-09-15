<?php
/**
 * Plugin Name: Baachal AI bot
 * Description: A simple AI chatbot using Gemini API with website knowledge
 * Version: 1.0.0
 * Author: Shojib Khan
 * Author URI: https://www.shojibkhan.com
 * Text Domain: baachal
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BAACHAL_VERSION', '1.0.0');
define('BAACHAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BAACHAL_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Main plugin class
class Baachal {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_chatbot_html'));
        add_action('wp_ajax_chatbot_message', array($this, 'handle_chatbot_message'));
        add_action('wp_ajax_nopriv_chatbot_message', array($this, 'handle_chatbot_message'));
        add_action('wp_ajax_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_nopriv_get_chat_history', array($this, 'get_chat_history'));
        add_action('wp_ajax_clear_chat_history', array($this, 'clear_chat_history'));
        add_action('wp_ajax_nopriv_clear_chat_history', array($this, 'clear_chat_history'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'add_chat_meta_boxes'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }
    
    public function init() {
        // Register custom post type for chat conversations
        $this->register_chat_post_type();
    }
    
    public function activate_plugin() {
        // Register post type
        $this->register_chat_post_type();
        
        // Flush rewrite rules
        flush_rewrite_rules();
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
        wp_enqueue_script('baachal-js', BAACHAL_PLUGIN_URL . 'assets/chatbot.js', array('jquery'), BAACHAL_VERSION, true);
        wp_enqueue_style('baachal-css', BAACHAL_PLUGIN_URL . 'assets/chatbot.css', array(), BAACHAL_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('baachal-js', 'chatbot_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_nonce'),
            'debug_mode' => get_option('chatbot_debug_mode', '0'),
            'message_persistence' => get_option('chatbot_message_persistence', '1'),
            'session_id' => $this->get_chat_session_id()
        ));
    }
    
    public function add_chatbot_html() {
        $is_enabled = get_option('chatbot_enabled', '1');
        if ($is_enabled !== '1') {
            return;
        }
        
        include BAACHAL_PLUGIN_PATH . 'templates/chatbot-widget.php';
    }
    
    public function handle_chatbot_message() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chatbot_nonce')) {
            wp_die('Security check failed');
        }
        
        $message = sanitize_text_field($_POST['message']);
        $api_key = get_option('chatbot_gemini_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            return;
        }
        
        $result = $this->call_gemini_api($message, $api_key);
        
        if ($result['success']) {
            // Save both user message and bot response
            $this->save_chat_message($message, 'user');
            $this->save_chat_message($result['data'], 'bot');
            
            wp_send_json_success($result['data']);
        } else {
            wp_send_json_error($result['error']);
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
        if (get_option('chatbot_message_persistence', '1') !== '1') {
            return; // Don't save if persistence is disabled
        }
        
        $session_id = $this->get_chat_session_id();
        $user_id = get_current_user_id();
        
        // Find or create conversation post
        $conversation_id = $this->get_or_create_conversation($session_id, $user_id);
        
        if ($conversation_id) {
            // Get existing messages
            $messages = get_post_meta($conversation_id, '_chat_messages', true);
            if (!is_array($messages)) {
                $messages = array();
            }
            
            // Add new message
            $messages[] = array(
                'message' => $message,
                'type' => $type,
                'timestamp' => current_time('mysql'),
                'user_id' => $user_id
            );
            
            // Keep only last 100 messages per conversation
            if (count($messages) > 100) {
                $messages = array_slice($messages, -100);
            }
            
            // Update messages
            update_post_meta($conversation_id, '_chat_messages', $messages);
            
            // Update conversation title if it's just the default
            $current_title = get_the_title($conversation_id);
            if (strpos($current_title, 'Chat Session') !== false && $type === 'user') {
                $new_title = 'Chat: ' . wp_trim_words($message, 5, '...');
                wp_update_post(array(
                    'ID' => $conversation_id,
                    'post_title' => $new_title
                ));
            }
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
        
        $context = "You are a helpful assistant for the website '{$site_name}'. ";
        $context .= "Website description: {$site_description}. ";
        $context .= "Website URL: {$site_url}. ";
        
        // Add WooCommerce product information if available and enabled
        if (class_exists('WooCommerce') && get_option('chatbot_woocommerce_integration', '1') === '1') {
            $woocommerce_context = $this->get_woocommerce_context($user_message);
            if (!empty($woocommerce_context)) {
                $context .= $woocommerce_context;
            }
        }
        
        $context .= "Please answer questions about this website helpfully and accurately. ";
        $context .= "When mentioning specific products, always include clickable links in this format: [Product Name](URL). ";
        $context .= "If you don't know something specific about the website, be honest about it. ";
        $context .= "Keep your responses friendly and concise.";
        
        return $context;
    }
    
    private function get_woocommerce_context($user_message = '') {
        if (!class_exists('WooCommerce')) {
            return '';
        }
        
        // If we have a user message, get relevant products using embeddings
        if (!empty($user_message)) {
            $relevant_products = $this->get_relevant_products($user_message);
            if (!empty($relevant_products)) {
                return $this->format_products_context($relevant_products);
            }
        }
        
        // Fallback: Get a small sample of recent products
        return $this->get_sample_products_context();
    }
    
    private function get_relevant_products($user_message, $limit = 5) {
        // First, try to find products using WordPress search and keywords
        $products = $this->search_products_by_keywords($user_message, $limit);
        
        if (count($products) < $limit) {
            // If we don't have enough products, add some popular/recent ones
            $additional = $this->get_popular_products($limit - count($products));
            $products = array_merge($products, $additional);
        }
        
        return array_slice($products, 0, $limit);
    }
    
    private function search_products_by_keywords($user_message, $limit = 5) {
        // Extract keywords from user message
        $keywords = $this->extract_keywords($user_message);
        
        if (empty($keywords)) {
            return array();
        }
        
        // Search products by title, content, and meta
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit * 2, // Get more to filter
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            ),
            's' => implode(' ', $keywords) // WordPress search
        );
        
        $products = get_posts($args);
        $valid_products = array();
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product && $wc_product->is_visible() && $wc_product->is_purchasable()) {
                $valid_products[] = $product;
                if (count($valid_products) >= $limit) {
                    break;
                }
            }
        }
        
        return $valid_products;
    }
    
    private function extract_keywords($message) {
        // Convert to lowercase and remove special characters
        $message = strtolower($message);
        $message = preg_replace('/[^a-z0-9\s]/', ' ', $message);
        
        // Split into words
        $words = explode(' ', $message);
        
        // Remove common stop words
        $stop_words = array(
            'i', 'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours',
            'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 'she', 'her', 'hers',
            'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 'theirs', 'themselves',
            'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'am', 'is', 'are',
            'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'having', 'do', 'does',
            'did', 'doing', 'a', 'an', 'the', 'and', 'but', 'if', 'or', 'because', 'as', 'until',
            'while', 'of', 'at', 'by', 'for', 'with', 'through', 'during', 'before', 'after',
            'above', 'below', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again',
            'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all',
            'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor',
            'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'can', 'will', 'just',
            'should', 'now', 'want', 'need', 'looking', 'find', 'show', 'get', 'buy', 'purchase'
        );
        
        // Filter words
        $keywords = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stop_words) && !is_numeric($word)) {
                $keywords[] = $word;
            }
        }
        
        return array_unique($keywords);
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
        
        $context = "\n\nThis is a WooCommerce online store. Here are some relevant products:\n";
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if ($wc_product && $wc_product->is_visible()) {
                $price = $wc_product->get_price_html();
                $categories = wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'names'));
                $category_names = !empty($categories) ? implode(', ', $categories) : 'Uncategorized';
                
                $product_info = "- {$product->post_title}";
                $product_info .= " (Price: {$price})";
                $product_info .= " (Category: {$category_names})";
                $product_info .= " (URL: " . get_permalink($product->ID) . ")";
                
                // Add short description if available
                if ($wc_product->get_short_description()) {
                    $short_desc = wp_strip_all_tags($wc_product->get_short_description());
                    $short_desc = substr($short_desc, 0, 80) . (strlen($short_desc) > 80 ? '...' : '');
                    $product_info .= " - {$short_desc}";
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
        
        return $context;
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
    
    public function register_settings() {
        register_setting('chatbot_settings', 'chatbot_gemini_api_key');
        register_setting('chatbot_settings', 'chatbot_enabled');
        register_setting('chatbot_settings', 'chatbot_welcome_message');
        register_setting('chatbot_settings', 'chatbot_debug_mode');
        register_setting('chatbot_settings', 'chatbot_gemini_model');
        register_setting('chatbot_settings', 'chatbot_woocommerce_integration');
        register_setting('chatbot_settings', 'chatbot_message_persistence');
    }
    
    public function admin_page() {
        include BAACHAL_PLUGIN_PATH . 'admin/settings-page.php';
    }
}

// Initialize the plugin
new Baachal();

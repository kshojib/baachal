<?php
/**
 * Uninstall Baachal AI Chatbot
 *
 * @package Baachal
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Call the main plugin uninstall method
if (class_exists('Baachal')) {
    Baachal::uninstall_plugin();
} else {
    // Fallback cleanup if class is not available
    
    // Check if we should preserve data
    $preserve_data = get_option('baachal_preserve_data_on_uninstall', false);
    
    if (!$preserve_data) {
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
    }
}
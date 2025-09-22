<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get content indexer instance
global $wpdb;
$indexer = new Baachal_Content_Indexer();
$stats = $indexer->get_index_stats();

// Handle form submissions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'reindex_content' && wp_verify_nonce($_POST['baachal_content_nonce'], 'baachal_content_action')) {
        $indexed_count = $indexer->index_all_content();
        echo '<div class="notice notice-success"><p>' . sprintf(__('Successfully indexed %d items.', 'baachal'), $indexed_count) . '</p></div>';
        $stats = $indexer->get_index_stats(); // Refresh stats
    }
}

// Get all available post types
$post_types = get_post_types(array('public' => true), 'objects');
$indexable_types = get_option('baachal_indexable_post_types', array('post', 'page'));
?>

<div class="baachal-tab-content">
    <h3><?php _e('Content Indexing', 'baachal'); ?></h3>
    <p><?php _e('Configure which content should be indexed for the chatbot to search through. This allows your chatbot to answer questions about your website content, not just products.', 'baachal'); ?></p>
    
    <!-- Index Statistics -->
    <div class="baachal-stats-box" style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px;">
        <h4><?php _e('Index Statistics', 'baachal'); ?></h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
            <div>
                <strong><?php _e('Total Indexed Items:', 'baachal'); ?></strong><br>
                <span style="font-size: 24px; color: #0073aa;"><?php echo number_format($stats['total']); ?></span>
            </div>
            
            <?php if (!empty($stats['by_type'])): ?>
            <div>
                <strong><?php _e('By Content Type:', 'baachal'); ?></strong><br>
                <?php foreach ($stats['by_type'] as $type_stat): ?>
                    <div><?php echo esc_html(ucfirst($type_stat->post_type)); ?>: <?php echo number_format($type_stat->count); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($stats['last_updated']): ?>
            <div>
                <strong><?php _e('Last Updated:', 'baachal'); ?></strong><br>
                <?php echo wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['last_updated'])); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('baachal_content_action', 'baachal_content_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="baachal_content_indexing_enabled"><?php _e('Enable Content Indexing', 'baachal'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="baachal_content_indexing_enabled" name="baachal_content_indexing_enabled" value="1" <?php checked(get_option('baachal_content_indexing_enabled', '1'), '1'); ?> />
                        <?php _e('Allow chatbot to search website content (pages, posts, etc.)', 'baachal'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the chatbot can answer questions about your website content, not just products.', 'baachal'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label><?php _e('Content Types to Index', 'baachal'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php _e('Select content types to index', 'baachal'); ?></legend>
                        <?php foreach ($post_types as $post_type): ?>
                            <?php 
                            // Skip attachment and some system post types
                            if (in_array($post_type->name, array('attachment', 'revision', 'nav_menu_item'))) continue;
                            ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="baachal_indexable_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $indexable_types)); ?> />
                                <?php echo esc_html($post_type->label); ?> (<?php echo esc_html($post_type->name); ?>)
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php _e('Select which types of content should be indexed for chatbot searches.', 'baachal'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="baachal_content_max_results"><?php _e('Max Search Results', 'baachal'); ?></label>
                </th>
                <td>
                    <input type="number" id="baachal_content_max_results" name="baachal_content_max_results" value="<?php echo esc_attr(get_option('baachal_content_max_results', '5')); ?>" min="1" max="20" class="small-text" />
                    <p class="description"><?php _e('Maximum number of content results to consider when answering questions (1-20).', 'baachal'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="baachal_auto_index"><?php _e('Auto Index', 'baachal'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="baachal_auto_index" name="baachal_auto_index" value="1" <?php checked(get_option('baachal_auto_index', '1'), '1'); ?> />
                        <?php _e('Automatically index content when it\'s created or updated', 'baachal'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, new and updated content will be automatically indexed for chatbot searches.', 'baachal'); ?></p>
                </td>
            </tr>
        </table>
        
        <h4><?php _e('Manual Actions', 'baachal'); ?></h4>
        <p style="margin-bottom: 15px;">
            <button type="submit" name="action" value="reindex_content" class="button button-secondary" onclick="return confirm('<?php esc_attr_e('This will reindex all content. Continue?', 'baachal'); ?>')">
                <?php _e('Reindex All Content', 'baachal'); ?>
            </button>
            <span class="description" style="margin-left: 10px;"><?php _e('This will clear the current index and rebuild it from scratch.', 'baachal'); ?></span>
        </p>
        
        <?php submit_button(__('Save Content Settings', 'baachal')); ?>
    </form>
    
    <!-- Content Search Test -->
    <div class="baachal-test-section" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h4><?php _e('Test Content Search', 'baachal'); ?></h4>
        <p><?php _e('Test the content search functionality to see what results would be returned for a query.', 'baachal'); ?></p>
        
        <div id="content-search-test">
            <input type="text" id="test-query" placeholder="<?php esc_attr_e('Enter a search query...', 'baachal'); ?>" style="width: 300px; margin-right: 10px;" />
            <button type="button" id="test-search-btn" class="button"><?php _e('Test Search', 'baachal'); ?></button>
            <div id="search-results" style="margin-top: 15px;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-search-btn').click(function() {
                var query = $('#test-query').val();
                if (!query) return;
                
                $('#search-results').html('<p>Searching...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'baachal_test_content_search',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('baachal_test_search'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<h5>Search Results:</h5>';
                            if (response.data.results.length > 0) {
                                html += '<ul>';
                                response.data.results.forEach(function(result) {
                                    html += '<li><strong>' + result.title + '</strong> (' + result.post_type + ')<br>';
                                    html += '<small>' + result.excerpt + '</small><br>';
                                    html += '<a href="' + result.url + '" target="_blank">' + result.url + '</a></li>';
                                });
                                html += '</ul>';
                            } else {
                                html += '<p>No results found.</p>';
                            }
                            $('#search-results').html(html);
                        } else {
                            $('#search-results').html('<p style="color: red;">Error: ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#search-results').html('<p style="color: red;">Ajax error occurred.</p>');
                    }
                });
            });
            
            $('#test-query').keypress(function(e) {
                if (e.which == 13) {
                    $('#test-search-btn').click();
                }
            });
        });
        </script>
    </div>
</div>
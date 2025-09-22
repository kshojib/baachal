<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<h2>Advanced Settings</h2>
<p>Advanced options for developers and troubleshooting.</p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="chatbot_debug_mode">Debug Mode</label>
        </th>
        <td>
            <input type="checkbox" id="chatbot_debug_mode" name="chatbot_debug_mode" value="1" <?php checked($debug_mode, '1'); ?> />
            <p class="description">Enable to see detailed error messages in browser console (for troubleshooting)</p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="baachal_preserve_data_on_uninstall">Preserve Data on Uninstall</label>
        </th>
        <td>
            <input type="checkbox" id="baachal_preserve_data_on_uninstall" name="baachal_preserve_data_on_uninstall" value="1" <?php checked(get_option('baachal_preserve_data_on_uninstall', false), '1'); ?> />
            <p class="description">Keep all plugin data (settings, conversations, indexed content) when uninstalling the plugin</p>
        </td>
    </tr>
</table>

<h3>Plugin Information</h3>
<table class="form-table">
    <tr>
        <th scope="row">Plugin Version</th>
        <td><?php echo BAACHAL_VERSION; ?></td>
    </tr>
    <tr>
        <th scope="row">WordPress Version</th>
        <td><?php echo get_bloginfo('version'); ?></td>
    </tr>
    <tr>
        <th scope="row">PHP Version</th>
        <td><?php echo PHP_VERSION; ?></td>
    </tr>
    <tr>
        <th scope="row">WooCommerce Status</th>
        <td><?php echo class_exists('WooCommerce') ? '✅ Active (Version: ' . WC()->version . ')' : '❌ Not installed'; ?></td>
    </tr>
</table>

<h3>Features</h3>
<ul>
    <li>✅ AI-powered responses using Google Gemini</li>
    <li>✅ <?php echo class_exists('WooCommerce') ? 'WooCommerce integration - knows about your products' : 'WooCommerce integration (WooCommerce not detected)'; ?></li>
    <li>✅ Responsive design works on all devices</li>
    <li>✅ Customizable positioning and styling</li>
    <li>✅ Multiple AI models including Gemini 2.0 Flash</li>
    <li>✅ Message persistence across page reloads</li>
    <li>✅ Dynamic product search system</li>
    <li>✅ Comprehensive hook system for developers</li>
</ul>

<h3>Developer Hooks</h3>
<p>This plugin provides numerous hooks for developers to extend functionality:</p>

<h4>Action Hooks</h4>
<ul>
    <li><code>baachal_before_save_settings</code> - Before settings are saved</li>
    <li><code>baachal_save_additional_settings</code> - Save custom settings</li>
    <li><code>baachal_before_widget_render</code> - Before widget is displayed</li>
    <li><code>baachal_after_widget_render</code> - After widget is displayed</li>
    <li><code>baachal_before_get_dynamic_terms</code> - Before term extraction</li>
    <li><code>baachal_after_get_dynamic_terms</code> - After term extraction</li>
</ul>

<h4>Filter Hooks</h4>
<ul>
    <li><code>baachal_important_product_terms</code> - Modify extracted product terms</li>
    <li><code>baachal_bot_response</code> - Modify AI response before sending</li>
    <li><code>baachal_settings_tabs</code> - Add custom settings tabs</li>
    <li><code>baachal_is_enabled</code> - Override chatbot enabled state</li>
</ul>

<h3>Need Help?</h3>
<p>If you need assistance or have questions about this plugin, please feel free to reach out for support.</p>

<h3>System Requirements</h3>
<ul>
    <li>WordPress 5.0 or higher</li>
    <li>PHP 7.4 or higher</li>
    <li>Valid Google Gemini API key</li>
    <li>WooCommerce 3.0+ (optional, for product integration)</li>
</ul>
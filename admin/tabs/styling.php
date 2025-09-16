<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<h2>UI Styling</h2>
<p>Customize the appearance and behavior of your chatbot widget.</p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="chatbot_primary_color">Primary Color</label>
        </th>
        <td>
            <input type="color" id="chatbot_primary_color" name="chatbot_primary_color" value="<?php echo esc_attr($primary_color); ?>" />
            <span class="baachal-color-preview" style="background-color: <?php echo esc_attr($primary_color); ?>"></span>
            <p class="description">Main color for the chatbot (header, buttons, links)</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_secondary_color">Secondary Color</label>
        </th>
        <td>
            <input type="color" id="chatbot_secondary_color" name="chatbot_secondary_color" value="<?php echo esc_attr($secondary_color); ?>" />
            <span class="baachal-color-preview" style="background-color: <?php echo esc_attr($secondary_color); ?>"></span>
            <p class="description">Background color for chat messages and secondary elements</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_position">Position</label>
        </th>
        <td>
            <select id="chatbot_position" name="chatbot_position">
                <option value="bottom-right" <?php selected($position, 'bottom-right'); ?>>Bottom Right</option>
                <option value="bottom-left" <?php selected($position, 'bottom-left'); ?>>Bottom Left</option>
                <option value="top-right" <?php selected($position, 'top-right'); ?>>Top Right</option>
                <option value="top-left" <?php selected($position, 'top-left'); ?>>Top Left</option>
            </select>
            <p class="description">Where to position the chatbot on your website</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_size">Widget Size</label>
        </th>
        <td>
            <select id="chatbot_size" name="chatbot_size">
                <option value="small" <?php selected($size, 'small'); ?>>Small (300px width)</option>
                <option value="medium" <?php selected($size, 'medium'); ?>>Medium (350px width)</option>
                <option value="large" <?php selected($size, 'large'); ?>>Large (400px width)</option>
                <option value="extra-large" <?php selected($size, 'extra-large'); ?>>Extra Large (450px width)</option>
            </select>
            <p class="description">Overall size of the chatbot widget</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_chat_height">Chat Area Height</label>
        </th>
        <td>
            <input type="number" id="chatbot_chat_height" name="chatbot_chat_height" value="<?php echo esc_attr($chat_height); ?>" min="200" max="600" step="10" />
            <span>pixels</span>
            <p class="description">Height of the chat messages area (200-600 pixels)</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_border_radius">Border Radius</label>
        </th>
        <td>
            <input type="number" id="chatbot_border_radius" name="chatbot_border_radius" value="<?php echo esc_attr($border_radius); ?>" min="0" max="50" />
            <span>pixels</span>
            <p class="description">Roundness of the chatbot corners (0 = square, 50 = very round)</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_font_size">Font Size</label>
        </th>
        <td>
            <input type="number" id="chatbot_font_size" name="chatbot_font_size" value="<?php echo esc_attr($font_size); ?>" min="10" max="20" />
            <span>pixels</span>
            <p class="description">Text size in the chat (10-20 pixels)</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_animation_enabled">Enable Animations</label>
        </th>
        <td>
            <input type="checkbox" id="chatbot_animation_enabled" name="chatbot_animation_enabled" value="1" <?php checked($animation_enabled, '1'); ?> />
            <p class="description">Enable smooth animations when opening/closing the chatbot</p>
        </td>
    </tr>
</table>
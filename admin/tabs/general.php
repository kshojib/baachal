<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<h2>General Settings</h2>
<p>Configure the basic settings for your chatbot.</p>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <label for="chatbot_gemini_api_key">Gemini API Key</label>
        </th>
        <td>
            <input type="password" id="chatbot_gemini_api_key" name="chatbot_gemini_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
            <p class="description">Get your API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_enabled">Enable Chatbot</label>
        </th>
        <td>
            <input type="checkbox" id="chatbot_enabled" name="chatbot_enabled" value="1" <?php checked($enabled, '1'); ?> />
            <p class="description">Show the chatbot on your website</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_gemini_model">AI Model</label>
        </th>
        <td>
            <select id="chatbot_gemini_model" name="chatbot_gemini_model">
                <option value="gemini-2.0-flash-exp" <?php selected($gemini_model, 'gemini-2.0-flash-exp'); ?>>Gemini 2.0 Flash (Experimental) - Recommended</option>
                <option value="gemini-1.5-flash" <?php selected($gemini_model, 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash - Fast & Reliable</option>
                <option value="gemini-1.5-pro" <?php selected($gemini_model, 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro - Most Capable</option>
                <option value="gemini-pro" <?php selected($gemini_model, 'gemini-pro'); ?>>Gemini Pro - Legacy</option>
            </select>
            <p class="description">Choose the AI model. Gemini 2.0 Flash is the latest and fastest model.</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_title">Chatbot Title</label>
        </th>
        <td>
            <input type="text" id="chatbot_title" name="chatbot_title" value="<?php echo esc_attr($chatbot_title); ?>" class="regular-text" />
            <p class="description">The title displayed in the chatbot header</p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="chatbot_welcome_message">Welcome Message</label>
        </th>
        <td>
            <textarea id="chatbot_welcome_message" name="chatbot_welcome_message" rows="3" cols="50"><?php echo esc_textarea($welcome_message); ?></textarea>
            <p class="description">The first message users see when they open the chatbot</p>
        </td>
    </tr>
</table>

<h3>Getting Started</h3>
<ol>
    <li>Get your free API key from <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></li>
    <li>Paste the API key in the field above</li>
    <li>Make sure "Enable Chatbot" is checked</li>
    <li>Customize the welcome message</li>
    <li>Save settings</li>
    <li>Visit your website to see the chatbot in action!</li>
</ol>

<h3>AI Models</h3>
<ul>
    <li><strong>Gemini 2.0 Flash (Experimental):</strong> Latest and fastest model - recommended</li>
    <li><strong>Gemini 1.5 Flash:</strong> Fast and reliable for general use</li>
    <li><strong>Gemini 1.5 Pro:</strong> Most capable for complex queries</li>
    <li><strong>Gemini Pro:</strong> Legacy model for compatibility</li>
</ul>
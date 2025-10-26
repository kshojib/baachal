# Baachal AI Bot - Developer Hooks Documentation

This document lists all available action hooks and filter hooks that developers can use to extend the Baachal AI Bot plugin functionality.

## Recent Updates (v1.0.4)

### New Conversational Memory Hooks

The conversational memory feature introduces new ways to modify conversation context and behavior:

- **`baachal_conversation_history`** - Filter conversation history before sending to AI
- **`baachal_memory_limit`** - Modify memory limit dynamically per conversation
- **Enhanced `baachal_api_params`** - Now includes conversation_history parameter

## Action Hooks

### Plugin Initialization

#### `baachal_init`

**Description:** Fired after the main plugin class is instantiated.
**Parameters:** `$plugin_instance` - The main Baachal plugin instance
**Example:**

```php
add_action('baachal_init', function($plugin) {
    // Your custom initialization code
});
```

#### `baachal_before_init`

**Description:** Fired before the plugin initializes (before custom post type registration).
**Parameters:** None
**Example:**

```php
add_action('baachal_before_init', function() {
    // Perform actions before plugin initialization
});
```

#### `baachal_after_init`

**Description:** Fired after the plugin initializes (after custom post type registration).
**Parameters:** None
**Example:**

```php
add_action('baachal_after_init', function() {
    // Perform actions after plugin initialization
});
```

### Scripts and Styles

#### `baachal_after_enqueue_scripts`

**Description:** Fired after the plugin enqueues its scripts and styles.
**Parameters:** None
**Example:**

```php
add_action('baachal_after_enqueue_scripts', function() {
    wp_enqueue_script('my-chatbot-extension', 'path/to/script.js', array('baachal-js'));
});
```

### Widget Rendering

#### `baachal_before_widget_render`

**Description:** Fired before the chatbot widget template is included.
**Parameters:** None
**Example:**

```php
add_action('baachal_before_widget_render', function() {
    echo '<div class="custom-wrapper">';
});
```

#### `baachal_after_widget_render`

**Description:** Fired after the chatbot widget template is included.
**Parameters:** None
**Example:**

```php
add_action('baachal_after_widget_render', function() {
    echo '</div>';
});
```

### Widget Template Hooks

#### `baachal_before_chatbot_widget`

**Description:** Fired before the chatbot widget HTML starts.
**Parameters:** None

#### `baachal_after_chatbot_widget`

**Description:** Fired after the chatbot widget HTML ends.
**Parameters:** None

#### `baachal_before_toggle_button`

**Description:** Fired before the toggle button in the widget.
**Parameters:** None

#### `baachal_after_toggle_button`

**Description:** Fired after the toggle button in the widget.
**Parameters:** None

#### `baachal_before_chatbot_header`

**Description:** Fired before the chatbot header.
**Parameters:** None

#### `baachal_after_chatbot_header`

**Description:** Fired after the chatbot header.
**Parameters:** None

#### `baachal_chatbot_header_buttons_before`

**Description:** Fired before the header buttons (before clear button).
**Parameters:** None

#### `baachal_chatbot_header_buttons_after`

**Description:** Fired after the header buttons (after clear button, before close button).
**Parameters:** None

#### `baachal_before_welcome_message`

**Description:** Fired before the welcome message in the chat area.
**Parameters:** None

#### `baachal_after_welcome_message`

**Description:** Fired after the welcome message in the chat area.
**Parameters:** None

#### `baachal_before_input_area`

**Description:** Fired before the input area.
**Parameters:** None

#### `baachal_after_input_area`

**Description:** Fired after the input area.
**Parameters:** None

#### `baachal_before_container_end`

**Description:** Fired before the chatbot container closes.
**Parameters:** None

#### `baachal_after_chatbot_container`

**Description:** Fired after the chatbot container.
**Parameters:** None

### Settings Page Hooks

#### `baachal_before_settings_form`

**Description:** Fired before the settings form.
**Parameters:** None

#### `baachal_after_settings_form`

**Description:** Fired after the settings form.
**Parameters:** None

#### `baachal_before_save_settings`

**Description:** Fired before settings are saved.
**Parameters:** `$post_data` - The $\_POST data from the form

#### `baachal_save_additional_settings`

**Description:** Fired when saving settings, allows saving custom settings.
**Parameters:** `$post_data` - The $\_POST data from the form

#### `baachal_settings_before_core`

**Description:** Fired before core settings fields in the settings table.
**Parameters:** None

#### `baachal_settings_after_core`

**Description:** Fired after core settings fields in the settings table.
**Parameters:** None

#### `baachal_before_settings_submit`

**Description:** Fired before the submit button.
**Parameters:** None

#### `baachal_after_settings_submit`

**Description:** Fired after the submit button.
**Parameters:** None

#### `baachal_settings_page_end`

**Description:** Fired at the end of the settings page.
**Parameters:** None

### Message Processing

#### `baachal_after_successful_response`

**Description:** Fired after a successful API response.
**Parameters:** `$bot_response`, `$user_message`, `$api_result`

#### `baachal_after_error_response`

**Description:** Fired after an error response.
**Parameters:** `$error_response`, `$user_message`, `$api_result`

#### `baachal_before_save_message`

**Description:** Fired before saving a message to database.
**Parameters:** `$message_data` - Array with message, type, session_id, user_id, timestamp

#### `baachal_after_save_message`

**Description:** Fired after saving a message to database.
**Parameters:** `$message_data`, `$conversation_id`, `$all_messages`

## Filter Hooks

### Plugin Configuration

#### `baachal_register_hooks`

**Description:** Filter the hooks that the plugin registers.
**Parameters:** `$hooks` - Array of hook_name => callback pairs
**Return:** Array of hooks
**Example:**

```php
add_filter('baachal_register_hooks', function($hooks) {
    $hooks['wp_head'] = array($my_instance, 'custom_head_content');
    return $hooks;
});
```

#### `baachal_is_enabled`

**Description:** Filter whether the chatbot is enabled.
**Parameters:** `$is_enabled` - Boolean indicating if enabled
**Return:** Boolean
**Example:**

```php
add_filter('baachal_is_enabled', function($enabled) {
    // Disable on specific pages
    if (is_page('contact')) {
        return false;
    }
    return $enabled;
});
```

### Scripts and Styles

#### `baachal_js_dependencies`

**Description:** Filter JavaScript dependencies.
**Parameters:** `$dependencies` - Array of script dependencies
**Return:** Array of dependencies

#### `baachal_css_dependencies`

**Description:** Filter CSS dependencies.
**Parameters:** `$dependencies` - Array of style dependencies
**Return:** Array of dependencies

#### `baachal_localized_data`

**Description:** Filter the data passed to JavaScript.
**Parameters:** `$data` - Array of localized data
**Return:** Array of data
**Example:**

```php
add_filter('baachal_localized_data', function($data) {
    $data['custom_setting'] = get_option('my_custom_setting');
    return $data;
});
```

### Settings

#### `baachal_setting_*`

**Description:** Filter individual setting values. Replace \* with setting name.
**Available filters:**

- `baachal_setting_api_key`
- `baachal_setting_enabled`
- `baachal_setting_welcome_message`
- `baachal_setting_debug_mode`
- `baachal_setting_gemini_model`
- `baachal_setting_woocommerce_integration`
- `baachal_setting_message_persistence`
- `baachal_setting_show_clear_history`

**Example:**

```php
add_filter('baachal_setting_welcome_message', function($message) {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return "Hello " . $user->display_name . "! How can I help you today?";
    }
    return $message;
});
```

#### `baachal_settings_help_content`

**Description:** Filter what help content sections to show.
**Parameters:** `$sections` - Array of section_name => true/false
**Return:** Array of sections

#### `baachal_features_list`

**Description:** Filter the features list in settings.
**Parameters:** `$features` - Array of feature_key => feature_text
**Return:** Array of features

#### `baachal_ai_models_info`

**Description:** Filter the AI models information.
**Parameters:** `$models` - Array of model_key => model_description
**Return:** Array of models

#### `baachal_troubleshooting_tips`

**Description:** Filter the troubleshooting tips.
**Parameters:** `$tips` - Array of tip_key => tip_text
**Return:** Array of tips

### Widget Appearance

#### `baachal_welcome_message`

**Description:** Filter the welcome message.
**Parameters:** `$message` - The welcome message
**Return:** String

#### `baachal_show_clear_history`

**Description:** Filter whether to show clear history button.
**Parameters:** `$show` - Boolean indicating if should show
**Return:** Boolean

#### `baachal_chatbot_title`

**Description:** Filter the chatbot title in header.
**Parameters:** `$title` - The title text
**Return:** String

#### `baachal_toggle_button_content`

**Description:** Filter the toggle button HTML content.
**Parameters:** `$content` - The HTML content
**Return:** String

#### `baachal_send_button_content`

**Description:** Filter the send button HTML content.
**Parameters:** `$content` - The HTML content
**Return:** String

#### `baachal_loading_content`

**Description:** Filter the loading indicator HTML content.
**Parameters:** `$content` - The HTML content
**Return:** String

#### `baachal_input_placeholder`

**Description:** Filter the input field placeholder text.
**Parameters:** `$placeholder` - The placeholder text
**Return:** String

### Message Processing

#### `baachal_before_process_message`

**Description:** Filter user message before processing.
**Parameters:** `$message` - The user message, `$post_data` - $\_POST data
**Return:** String (filtered message)

#### `baachal_custom_message_handler`

**Description:** Provide custom message handling (bypasses API).
**Parameters:** `$response` - null by default, `$message` - user message, `$post_data` - $\_POST data
**Return:** String or Array (custom response) or null (continue normal processing)
**Example:**

```php
add_filter('baachal_custom_message_handler', function($response, $message, $post_data) {
    if (strpos(strtolower($message), 'hours') !== false) {
        return 'Our hours are 9 AM to 5 PM, Monday through Friday.';
    }
    return $response; // Continue normal processing
}, 10, 3);
```

#### `baachal_api_params`

**Description:** Filter API parameters before calling AI provider. Enhanced in v1.0.4 to include conversation history.
**Parameters:** `$params` - Array with message, conversation_history, and other params, `$api_context` - Context data
**Return:** Array of parameters

**Example:**

```php
add_filter('baachal_api_params', function($params, $context) {
    // Access conversation history
    $history = $params['conversation_history'];

    // Modify or filter conversation history
    $filtered_history = array_filter($history, function($msg) {
        // Only include messages from the last 5 minutes
        return (time() - strtotime($msg['timestamp'])) < 300;
    });

    $params['conversation_history'] = $filtered_history;
    return $params;
}, 10, 2);
```

#### `baachal_conversation_history`

**Description:** Filter conversation history before sending to AI provider (new in v1.0.4).
**Parameters:** `$history` - Array of conversation messages, `$session_id` - Session ID
**Return:** Array of conversation messages

**Example:**

```php
add_filter('baachal_conversation_history', function($history, $session_id) {
    // Add custom context or filter messages
    foreach ($history as &$message) {
        if ($message['role'] === 'user') {
            // Enhance user messages with additional context
            $message['content'] = '[Customer] ' . $message['content'];
        }
    }
    return $history;
}, 10, 2);
```

#### `baachal_memory_limit`

**Description:** Dynamically modify the conversation memory limit (new in v1.0.4).
**Parameters:** `$limit` - Number of messages to remember, `$session_id` - Session ID
**Return:** Number

**Example:**

```php
add_filter('baachal_memory_limit', function($limit, $session_id) {
    // Premium users get more memory
    if (user_has_premium_plan()) {
        return 25; // Increased memory for premium users
    }
    return $limit; // Default limit for regular users
}, 10, 2);
```

#### `baachal_bot_response`

**Description:** Filter bot response before saving/sending.
**Parameters:** `$response` - Bot response, `$user_message` - User message, `$api_result` - Full API result
**Return:** String (filtered response)

#### `baachal_error_response`

**Description:** Filter error response.
**Parameters:** `$error` - Error message, `$user_message` - User message, `$api_result` - API result
**Return:** String (filtered error message)

### Message Storage

#### `baachal_should_save_message`

**Description:** Filter whether a message should be saved.
**Parameters:** `$should_save` - Boolean, `$message` - Message text, `$type` - Message type
**Return:** Boolean

#### `baachal_message_limit`

**Description:** Filter the maximum number of messages per conversation.
**Parameters:** `$limit` - Number (default 100)
**Return:** Number

#### `baachal_conversation_title`

**Description:** Filter the conversation title.
**Parameters:** `$title` - Generated title, `$message` - First user message, `$conversation_id` - Post ID
**Return:** String

## JavaScript Events

The plugin also triggers custom JavaScript events that can be listened to:

### `baachal_before_open`

**Description:** Triggered before chatbot opens.
**Data:** `{widget: jQuery_element}`

### `baachal_after_open`

**Description:** Triggered after chatbot opens.
**Data:** `{widget: jQuery_element}`

### `baachal_before_close`

**Description:** Triggered before chatbot closes.
**Data:** `{widget: jQuery_element}`

### `baachal_after_close`

**Description:** Triggered after chatbot closes.
**Data:** `{widget: jQuery_element}`

### `baachal_before_send_message`

**Description:** Triggered before sending a message (can be cancelled).
**Data:** `{message: string, cancelled: boolean}`
**Note:** Set `event.cancelled = true` to prevent sending.

### `baachal_sending_started`

**Description:** Triggered when message sending starts.
**Data:** `{message: string}`

### `baachal_response_received`

**Description:** Triggered when API response is received.
**Data:** `{response: object, originalMessage: string}`

### `baachal_response_success`

**Description:** Triggered after successful response is displayed.
**Data:** `{botMessage: string, userMessage: string}`

### `baachal_response_error`

**Description:** Triggered after error response.
**Data:** `{error: string, response: object, userMessage: string}`

### `baachal_connection_error`

**Description:** Triggered on connection errors.
**Data:** `{error: string, xhr: object, status: string, originalError: string, userMessage: string}`

### `baachal_before_add_message`

**Description:** Triggered before adding message to chat (can be cancelled).
**Data:** `{content: string, type: string, save: boolean, cancelled: boolean}`

### `baachal_after_add_message`

**Description:** Triggered after adding message to chat.
**Data:** `{content: string, processedContent: string, type: string, messageElement: jQuery_element}`

### JavaScript Event Filters

Some events can return filtered values:

#### `baachal_filter_bot_response`

**Description:** Filter bot response before display.
**Usage:** `$(document).on('baachal_filter_bot_response', function(event, response) { return filtered_response; });`

#### `baachal_filter_error_message`

**Description:** Filter error message before display.
**Usage:** `$(document).on('baachal_filter_error_message', function(event, error, response) { return filtered_error; });`

#### `baachal_filter_connection_error`

**Description:** Filter connection error message before display.
**Usage:** `$(document).on('baachal_filter_connection_error', function(event, error, xhr, status, originalError) { return filtered_error; });`

## Example: Creating an Addon

Here's an example of how to create a simple addon that adds a greeting based on time of day:

```php
<?php
/**
 * Plugin Name: Baachal Time Greeting Addon
 */

// Wait for Baachal to initialize
add_action('baachal_init', function() {

    // Modify welcome message based on time
    add_filter('baachal_welcome_message', function($message) {
        $hour = date('H');
        if ($hour < 12) {
            $greeting = 'Good morning!';
        } elseif ($hour < 18) {
            $greeting = 'Good afternoon!';
        } else {
            $greeting = 'Good evening!';
        }
        return $greeting . ' How can I help you today?';
    });

    // Add custom responses for time-related questions
    add_filter('baachal_custom_message_handler', function($response, $message) {
        if (preg_match('/what time|current time|time now/i', $message)) {
            return 'The current time is ' . date('g:i A') . '.';
        }
        return $response;
    }, 10, 2);

    // Log all conversations for analytics
    add_action('baachal_after_save_message', function($message_data, $conversation_id) {
        error_log('Baachal message: ' . $message_data['type'] . ' - ' . $message_data['message']);
    }, 10, 2);

});
```

This documentation provides comprehensive information for developers to extend the Baachal AI Bot plugin functionality.

# Baachal AI bot

A simple and clean WordPress AI chatbot plugin that uses Google's Gemini API to provide intelligent responses with website context awareness and WooCommerce integration.

## Features

- ✅ **Clean, Simple Interface** - Positioned in the bottom-right corner
- ✅ **Google Gemini AI Integration** - Powered by Google's advanced AI models
- ✅ **Website Context Awareness** - Knows about your website content
- ✅ **WooCommerce Integration** - Product-aware responses with clickable links
- ✅ **Message Persistence** - Chat history saved in database
- ✅ **Admin Conversation Viewer** - View all chat conversations in WordPress admin
- ✅ **Model Selection** - Choose from multiple Gemini AI models
- ✅ **Easy Configuration** - Simple admin settings page
- ✅ **Responsive Design** - Works on all devices
- ✅ **Secure** - Includes proper nonce verification and data sanitization

## Installation

1. Upload the `custom-ai-chatbot` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Settings > Baachal AI bot** to configure the plugin

## Configuration

### Getting Your Gemini API Key

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Sign in with your Google account
3. Click "Create API Key"
4. Copy the API key

### Plugin Settings

1. Go to **Settings > Baachal AI bot** in your WordPress admin
2. Paste your Gemini API key
3. Choose your preferred AI model (default: gemini-2.0-flash-exp)
4. Enable the chatbot
5. Customize the welcome message
6. Configure WooCommerce integration (if using WooCommerce)
7. Enable/disable message persistence
8. Save settings

## Usage

Once configured, Baachal AI bot will appear as a floating icon in the bottom-right corner of your website. Visitors can:

- Click the icon to open the chat interface
- Type questions about your website or products
- Get AI-powered responses with website and product context
- Click on product links provided by the bot
- View conversation history (if persistence is enabled)
- Clear chat history using the trash icon
- Close the chat at any time

## Advanced Features

### WooCommerce Integration

When WooCommerce is active and integration is enabled:

- Baachal knows about your products, prices, and categories
- Provides clickable product links in responses
- Helps customers find products based on their needs
- Includes product attributes and descriptions in context

### Message Persistence

- Chat messages are saved in WordPress database
- Conversations persist across page reloads and sessions
- Admin can view all conversations in **Tools > Baachal Conversations**
- Each conversation includes user details, timestamps, and full message history

### Admin Conversation Management

- View all customer conversations in WordPress admin
- See conversation details including IP address, user agent, timestamps
- Read full chat history with visual distinction between user and bot messages
- Monitor bot performance and customer interactions

## File Structure

```
custom-ai-chatbot/
├── baachal-ai-bot.php             # Main plugin file (Baachal class)
├── admin/
│   └── settings-page.php          # Admin settings interface
├── assets/
│   ├── chatbot.css                # Baachal styling
│   └── chatbot.js                 # Baachal functionality
├── templates/
│   └── chatbot-widget.php         # Baachal HTML template
└── README.md                      # This file
```

## AI Models Supported

Baachal AI bot supports multiple Google Gemini models:

- **gemini-2.0-flash-exp** (Default) - Latest experimental model
- **gemini-1.5-pro** - Production-ready, high-quality responses
- **gemini-1.5-flash** - Faster responses, good for high-traffic sites
- **gemini-pro** - Balanced performance and quality

## Customization

### Styling

Edit `assets/chatbot.css` to customize the appearance:

- Colors and gradients
- Positioning and sizing
- Animation effects
- Responsive behavior

### Functionality

Edit `assets/chatbot.js` to modify:

- Chat behavior
- Message handling
- User interactions
- AJAX communication

### AI Context

The plugin automatically provides comprehensive context to the AI:

- Website name, description, and URL
- WooCommerce product catalog (when enabled)
- Product prices, categories, and attributes
- Instructions for helpful and accurate responses
- Proper link formatting for clickable product URLs

## Technical Details

- **WordPress Integration**: Uses proper WordPress hooks, filters, and standards
- **Security**: Includes nonce verification, data sanitization, and capability checks
- **AJAX**: WordPress AJAX for real-time communication
- **Database**: Custom post type for conversation storage
- **Session Management**: UUID-based session tracking for anonymous users
- **API Integration**: Google Gemini API with error handling and retry logic
- **Responsive Design**: Mobile-friendly interface

## Troubleshooting

### Baachal not appearing

- Check if the plugin is activated
- Ensure "Enable Baachal AI bot" is checked in settings
- Verify there are no JavaScript errors in browser console
- Check that your theme loads `wp_footer()` properly

### AI not responding

- Verify your Gemini API key is correct and active
- Check that your API key has quota remaining
- Ensure your website can make external HTTP requests
- Try switching to a different AI model in settings
- Enable debug mode to see detailed error messages

### WooCommerce integration issues

- Ensure WooCommerce is active and products are published
- Check that "WooCommerce Integration" is enabled in settings
- Verify products have proper categories and pricing

### Conversation persistence issues

- Check that "Message Persistence" is enabled in settings
- Verify database write permissions
- Look for PHP errors in WordPress error logs

### Styling issues

- Check for CSS conflicts with your theme
- Use browser developer tools to debug styling
- Ensure the plugin CSS is loading properly
- Clear any caching plugins

## Support

This plugin is developed by **Shojib Khan**. For support:

- Check the WordPress error logs for PHP errors
- Use browser developer tools for JavaScript debugging
- Verify API key and network connectivity
- Review conversation logs in **Tools > Baachal Conversations**

## Author

**Shojib Khan**  
Website: [https://www.shojibkhan.com](https://www.shojibkhan.com)

## License

This plugin is provided as-is for educational and commercial purposes.

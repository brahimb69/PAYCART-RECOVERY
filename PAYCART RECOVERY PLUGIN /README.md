# WooCommerce Canceled Order Email

A WordPress plugin that automatically sends custom emails to customers when their WooCommerce orders are canceled, using custom SMTP settings.

## Features

- **Automatic Email Sending**: Automatically sends emails when orders are canceled
- **Custom SMTP Configuration**: Use your own SMTP server settings
- **Customizable Email Templates**: Customize subject, heading, and content
- **Dynamic Variables**: Use variables like {order_number}, {customer_name}, etc.
- **Test Email Functionality**: Test your SMTP settings before going live
- **Enable/Disable Option**: Turn the feature on or off as needed

## Installation

1. Upload the `wc-canceled-order-email` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Canceled Order Email to configure the plugin

## Configuration

### SMTP Settings

Configure your SMTP server settings:

- **SMTP Host**: Your SMTP server address (e.g., smtp.gmail.com)
- **SMTP Port**: Usually 587 for TLS or 465 for SSL
- **Encryption**: Choose TLS, SSL, or None
- **SMTP Username**: Your email account username
- **SMTP Password**: Your email account password
- **From Email**: The email address that will appear as sender
- **From Name**: The name that will appear as sender

### Email Content

Customize your email content:

- **Email Subject**: Subject line of the email
- **Email Heading**: Main heading displayed in the email
- **Email Content**: The body of your email message

### Available Variables

You can use these variables in your subject and content:

- `{order_number}` - The order number
- `{customer_name}` - Customer's full name
- `{customer_email}` - Customer's email address
- `{order_date}` - Order creation date
- `{order_total}` - Formatted order total
- `{site_name}` - Your website name
- `{order_items}` - List of ordered items

## Common SMTP Settings

### Gmail

- SMTP Host: smtp.gmail.com
- SMTP Port: 587
- Encryption: TLS
- Username: your-email@gmail.com
- Password: Your app-specific password (not your regular Gmail password)

**Note**: You need to enable "2-Step Verification" and create an "App Password" in your Google Account settings.

### Outlook/Office 365

- SMTP Host: smtp.office365.com
- SMTP Port: 587
- Encryption: TLS
- Username: your-email@outlook.com
- Password: Your Outlook password

### Yahoo

- SMTP Host: smtp.mail.yahoo.com
- SMTP Port: 465 or 587
- Encryption: SSL (465) or TLS (587)
- Username: your-email@yahoo.com
- Password: Your app-specific password

### Custom SMTP Servers

Contact your hosting provider or email service provider for their specific SMTP settings.

## Testing

1. Go to WooCommerce > Canceled Order Email
2. Scroll down to the "Test Email" section
3. Enter your email address
4. Click "Send Test Email"
5. Check your inbox to verify the email was received

## Usage

Once configured and enabled:

1. The plugin will automatically detect when an order status changes to "Canceled"
2. It will send a custom email to the customer's billing email address
3. The email will include the order details and your custom message

## Troubleshooting

### Email Not Sending

- Verify your SMTP settings are correct
- Check that "Enable Email" is checked
- Send a test email to verify SMTP connection
- Check your WordPress error logs for detailed error messages

### Gmail Not Working

- Make sure you're using an App Password, not your regular password
- Enable 2-Step Verification in your Google Account
- Generate an App Password specifically for this plugin

### SSL Certificate Errors

The plugin includes basic SSL verification bypass for development environments. For production, ensure your server has proper SSL certificates installed.

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Support

For issues, questions, or feature requests, please contact your plugin administrator.

## Changelog

### 1.0.0
- Initial release
- SMTP configuration
- Custom email templates
- Test email functionality
- Order cancellation detection

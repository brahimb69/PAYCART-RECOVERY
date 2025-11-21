<?php
/**
 * Plugin Name: WooCommerce Canceled Order Email
 * Plugin URI: https://yourwebsite.com
 * Description: Sends custom emails to customers with canceled orders using custom SMTP settings
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: wc-canceled-order-email
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_COE_VERSION', '1.0.0');
define('WC_COE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_COE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class WC_Canceled_Order_Email {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));

        // Admin settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Hook into order status change
        add_action('woocommerce_order_status_cancelled', array($this, 'send_canceled_order_email'), 10, 2);

        // Add settings link on plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(plugin_basename(__FILE__));
        }
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WooCommerce Canceled Order Email requires WooCommerce to be installed and active.', 'wc-canceled-order-email'); ?></p>
        </div>
        <?php
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Canceled Order Email Settings', 'wc-canceled-order-email'),
            __('Canceled Order Email', 'wc-canceled-order-email'),
            'manage_options',
            'wc-canceled-order-email',
            array($this, 'settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // SMTP Settings
        register_setting('wc_coe_settings', 'wc_coe_smtp_host');
        register_setting('wc_coe_settings', 'wc_coe_smtp_port');
        register_setting('wc_coe_settings', 'wc_coe_smtp_username');
        register_setting('wc_coe_settings', 'wc_coe_smtp_password');
        register_setting('wc_coe_settings', 'wc_coe_smtp_encryption');
        register_setting('wc_coe_settings', 'wc_coe_from_email');
        register_setting('wc_coe_settings', 'wc_coe_from_name');

        // Email Content Settings
        register_setting('wc_coe_settings', 'wc_coe_email_subject');
        register_setting('wc_coe_settings', 'wc_coe_email_heading');
        register_setting('wc_coe_settings', 'wc_coe_email_content');
        register_setting('wc_coe_settings', 'wc_coe_enable_email');
    }

    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Canceled Order Email Settings', 'wc-canceled-order-email'); ?></h1>

            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php settings_fields('wc_coe_settings'); ?>

                <!-- Enable/Disable -->
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Email', 'wc-canceled-order-email'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="wc_coe_enable_email" value="1" <?php checked(get_option('wc_coe_enable_email'), '1'); ?>>
                                <?php _e('Enable sending emails for canceled orders', 'wc-canceled-order-email'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php _e('SMTP Settings', 'wc-canceled-order-email'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_coe_smtp_host"><?php _e('SMTP Host', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wc_coe_smtp_host" name="wc_coe_smtp_host" value="<?php echo esc_attr(get_option('wc_coe_smtp_host')); ?>" class="regular-text">
                            <p class="description"><?php _e('e.g., smtp.gmail.com', 'wc-canceled-order-email'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_smtp_port"><?php _e('SMTP Port', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="wc_coe_smtp_port" name="wc_coe_smtp_port" value="<?php echo esc_attr(get_option('wc_coe_smtp_port', '587')); ?>" class="small-text">
                            <p class="description"><?php _e('Usually 587 for TLS or 465 for SSL', 'wc-canceled-order-email'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_smtp_encryption"><?php _e('Encryption', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <select id="wc_coe_smtp_encryption" name="wc_coe_smtp_encryption">
                                <option value="tls" <?php selected(get_option('wc_coe_smtp_encryption', 'tls'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected(get_option('wc_coe_smtp_encryption'), 'ssl'); ?>>SSL</option>
                                <option value="" <?php selected(get_option('wc_coe_smtp_encryption'), ''); ?>><?php _e('None', 'wc-canceled-order-email'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_smtp_username"><?php _e('SMTP Username', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wc_coe_smtp_username" name="wc_coe_smtp_username" value="<?php echo esc_attr(get_option('wc_coe_smtp_username')); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_smtp_password"><?php _e('SMTP Password', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="wc_coe_smtp_password" name="wc_coe_smtp_password" value="<?php echo esc_attr(get_option('wc_coe_smtp_password')); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_from_email"><?php _e('From Email', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="wc_coe_from_email" name="wc_coe_from_email" value="<?php echo esc_attr(get_option('wc_coe_from_email')); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_from_name"><?php _e('From Name', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wc_coe_from_name" name="wc_coe_from_name" value="<?php echo esc_attr(get_option('wc_coe_from_name')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <h2><?php _e('Email Content', 'wc-canceled-order-email'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_coe_email_subject"><?php _e('Email Subject', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wc_coe_email_subject" name="wc_coe_email_subject" value="<?php echo esc_attr(get_option('wc_coe_email_subject', 'Your order has been canceled')); ?>" class="large-text">
                            <p class="description"><?php _e('Available variables: {order_number}, {customer_name}, {site_name}', 'wc-canceled-order-email'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_email_heading"><?php _e('Email Heading', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="wc_coe_email_heading" name="wc_coe_email_heading" value="<?php echo esc_attr(get_option('wc_coe_email_heading', 'Order Canceled')); ?>" class="large-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="wc_coe_email_content"><?php _e('Email Content', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <?php
                            $default_content = "Hello {customer_name},\n\nWe're sorry to inform you that your order #{order_number} has been canceled.\n\nIf you have any questions, please don't hesitate to contact us.\n\nThank you,\n{site_name}";
                            wp_editor(
                                get_option('wc_coe_email_content', $default_content),
                                'wc_coe_email_content',
                                array(
                                    'textarea_name' => 'wc_coe_email_content',
                                    'textarea_rows' => 10,
                                    'media_buttons' => false
                                )
                            );
                            ?>
                            <p class="description"><?php _e('Available variables: {order_number}, {customer_name}, {customer_email}, {order_date}, {order_total}, {site_name}, {order_items}', 'wc-canceled-order-email'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php _e('Test Email', 'wc-canceled-order-email'); ?></h2>
            <p><?php _e('Send a test email to verify your SMTP settings:', 'wc-canceled-order-email'); ?></p>
            <form method="post" action="">
                <?php wp_nonce_field('wc_coe_test_email', 'wc_coe_test_email_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wc_coe_test_email_address"><?php _e('Test Email Address', 'wc-canceled-order-email'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="wc_coe_test_email_address" name="wc_coe_test_email_address" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="wc_coe_send_test" class="button button-secondary" value="<?php _e('Send Test Email', 'wc-canceled-order-email'); ?>">
                </p>
            </form>
        </div>
        <?php

        // Handle test email
        if (isset($_POST['wc_coe_send_test']) && check_admin_referer('wc_coe_test_email', 'wc_coe_test_email_nonce')) {
            $test_email = sanitize_email($_POST['wc_coe_test_email_address']);
            if ($this->send_test_email($test_email)) {
                echo '<div class="notice notice-success"><p>' . __('Test email sent successfully!', 'wc-canceled-order-email') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Failed to send test email. Please check your SMTP settings.', 'wc-canceled-order-email') . '</p></div>';
            }
        }
    }

    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=wc-canceled-order-email">' . __('Settings', 'wc-canceled-order-email') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Send canceled order email
     */
    public function send_canceled_order_email($order_id, $order = null) {
        // Check if email is enabled
        if (get_option('wc_coe_enable_email') != '1') {
            return;
        }

        if (!$order) {
            $order = wc_get_order($order_id);
        }

        if (!$order) {
            return;
        }

        // Get customer email
        $customer_email = $order->get_billing_email();

        if (empty($customer_email)) {
            return;
        }

        // Prepare email content
        $subject = $this->replace_variables(get_option('wc_coe_email_subject', 'Your order has been canceled'), $order);
        $content = $this->replace_variables(get_option('wc_coe_email_content'), $order);

        // Send email
        $this->send_email($customer_email, $subject, $content, $order);
    }

    /**
     * Replace email variables
     */
    private function replace_variables($text, $order) {
        $replacements = array(
            '{order_number}' => $order->get_order_number(),
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{customer_email}' => $order->get_billing_email(),
            '{order_date}' => $order->get_date_created()->date('Y-m-d H:i:s'),
            '{order_total}' => $order->get_formatted_order_total(),
            '{site_name}' => get_bloginfo('name'),
            '{order_items}' => $this->get_order_items_html($order)
        );

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }

    /**
     * Get order items HTML
     */
    private function get_order_items_html($order) {
        $items_html = '';
        foreach ($order->get_items() as $item) {
            $items_html .= '- ' . $item->get_name() . ' x ' . $item->get_quantity() . "\n";
        }
        return $items_html;
    }

    /**
     * Send email using SMTP
     */
    private function send_email($to, $subject, $message, $order = null) {
        require_once WC_COE_PLUGIN_DIR . 'includes/class-smtp-mailer.php';

        $mailer = new WC_COE_SMTP_Mailer();
        return $mailer->send($to, $subject, $message);
    }

    /**
     * Send test email
     */
    private function send_test_email($to) {
        // Create a mock order object for testing
        $mock_order = new stdClass();
        $mock_order->order_number = '12345';
        $mock_order->customer_first_name = 'John';
        $mock_order->customer_last_name = 'Doe';
        $mock_order->customer_email = $to;
        $mock_order->order_date = date('Y-m-d H:i:s');
        $mock_order->order_total = '$99.99';
        $mock_order->order_items = "- Sample Product x 2\n- Another Product x 1";

        // Get email template from settings
        $subject = get_option('wc_coe_email_subject', 'Your order has been canceled');
        $content = get_option('wc_coe_email_content', "Hello {customer_name},\n\nWe're sorry to inform you that your order #{order_number} has been canceled.\n\nIf you have any questions, please don't hesitate to contact us.\n\nThank you,\n{site_name}");

        // Replace variables with mock data
        $subject = $this->replace_test_variables($subject, $mock_order);
        $content = $this->replace_test_variables($content, $mock_order);

        require_once WC_COE_PLUGIN_DIR . 'includes/class-smtp-mailer.php';

        $mailer = new WC_COE_SMTP_Mailer();
        return $mailer->send($to, $subject, $content);
    }

    /**
     * Replace variables for test email
     */
    private function replace_test_variables($text, $mock_order) {
        $replacements = array(
            '{order_number}' => $mock_order->order_number,
            '{customer_name}' => $mock_order->customer_first_name . ' ' . $mock_order->customer_last_name,
            '{customer_email}' => $mock_order->customer_email,
            '{order_date}' => $mock_order->order_date,
            '{order_total}' => $mock_order->order_total,
            '{site_name}' => get_bloginfo('name'),
            '{order_items}' => $mock_order->order_items
        );

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('WC_Canceled_Order_Email', 'get_instance'));

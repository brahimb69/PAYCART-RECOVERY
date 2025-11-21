<?php
/**
 * SMTP Mailer Class
 *
 * Handles sending emails using SMTP configuration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class WC_COE_SMTP_Mailer {

    /**
     * Send email using SMTP
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool True on success, false on failure
     */
    public function send($to, $subject, $message) {
        // Load PHPMailer
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        try {
            $mail = new PHPMailer(true);

            // Get SMTP settings
            $smtp_host = get_option('wc_coe_smtp_host');
            $smtp_port = get_option('wc_coe_smtp_port', 587);
            $smtp_username = get_option('wc_coe_smtp_username');
            $smtp_password = get_option('wc_coe_smtp_password');
            $smtp_encryption = get_option('wc_coe_smtp_encryption', 'tls');
            $from_email = get_option('wc_coe_from_email');
            $from_name = get_option('wc_coe_from_name', get_bloginfo('name'));

            // Validate required settings
            if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password) || empty($from_email)) {
                error_log('WC Canceled Order Email: Missing required SMTP settings');
                return false;
            }

            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->Port = $smtp_port;

            // Set encryption
            if ($smtp_encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtp_encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            // Disable SSL verification for development (not recommended for production)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to);
            $mail->addReplyTo($from_email, $from_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->format_email_html($message, $subject);
            $mail->AltBody = strip_tags($message);

            // Send email
            $result = $mail->send();

            if ($result) {
                error_log('WC Canceled Order Email: Email sent successfully to ' . $to);
            }

            return $result;

        } catch (Exception $e) {
            error_log('WC Canceled Order Email: Failed to send email. Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format email with HTML template
     *
     * @param string $content Email content
     * @param string $heading Email heading
     * @return string Formatted HTML email
     */
    private function format_email_html($content, $heading = '') {
        // Check if content is already a complete HTML document
        if (stripos($content, '<!DOCTYPE') !== false || stripos($content, '<html') !== false) {
            // Content is already a complete HTML email, return as-is
            return $content;
        }

        // Convert line breaks to <br> tags for plain text content
        $content = nl2br($content);

        // Basic HTML email template
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html($heading) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #0073aa;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        .email-body {
            padding: 30px;
        }
        .email-footer {
            background-color: #f8f8f8;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>' . esc_html($heading) . '</h1>
        </div>
        <div class="email-body">
            ' . $content . '
        </div>
        <div class="email-footer">
            <p>&copy; ' . date('Y') . ' ' . esc_html(get_bloginfo('name')) . '. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }
}

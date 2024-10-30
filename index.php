<?php
/*
Plugin Name: Custom Forgot Password Mail
Plugin URI: 
Version: 1.2
Author: Galaxyweblinks
Author URI: https://profiles.wordpress.org/galaxyweblinks/
Description: A plugin to create custom forgot mail
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  custom-forgot-mail
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('cfpm_write_forgot_mail')) {
    /**
     * Add the menu in the admin dashboard
     */
    function cfpm_write_forgot_mail() {
        add_menu_page(
            __('Custom Forgot Password Mail','custom-forgot-mail'),
            __('Custom Forgot Password Mail','custom-forgot-mail'),
            'manage_options',
            'custom_forgot_mail',
            'cfpm_overwrite',
            plugins_url('/images/Forgot_password.png', __FILE__),
            82
        );
    }
    add_action('admin_menu', 'cfpm_write_forgot_mail');
}

if (!function_exists('cfpm_overwrite')) {
    /**
     * Function to handle custom email message settings for forgot password.
     */
    function cfpm_overwrite() {
        // Check if the user is allowed to update options and verify nonce
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'custom-forgot-mail'));
        }

        // Save the custom email message if the form is submitted
        if (isset($_POST['message'])) {
            check_admin_referer('cfpm_update_forgot_mail');
            $message = stripslashes(wp_kses_post($_POST['message']));
            if (get_option('forgot_mail_cwd') !== false) {
                update_option('forgot_mail_cwd', $message);
            } else {
                add_option('forgot_mail_cwd', $message);
            }
        }

        // Set default email content if it doesn't exist
        if (!get_option('forgot_mail_cwd')) {
            $text = '<p>Someone requested that the password be reset for the following account: <a href="'.get_bloginfo("url").'">'.get_bloginfo("url").'</a></p>
                    Username: %username%<br/>
                    <p>If this was a mistake, just ignore this email and nothing will happen.</p>
                    To reset your password, visit the following address:<br/>
                    <a href="%reseturl%">%reseturl%</a>';
            add_option('forgot_mail_cwd', $text);
        }

        // Display the form
        ?>
        <form action="" method="post">
            <?php wp_nonce_field('cfpm_update_forgot_mail'); ?>
            <h2><?php esc_html__('Forgot Password Custom Email:', 'custom-forgot-mail'); ?></h2><br><br>
            <textarea rows=10 cols=40 name="message" id="message"><?php echo esc_textarea(get_option('forgot_mail_cwd')); ?></textarea><br><br>
            <b>(Note:)&nbsp;</b>Use placeholders <b>%username%</b> for username and <b>%reseturl%</b> for reset URL<br><br>
            <input type="submit" name="setmesssage" value="Save" class="button-primary">
        </form>
        <?php
    }
}

if (!function_exists('cfpm_retrieve_password_subject_filter')) {
    /**
     * Filter the subject line of the password reset email.
     *
     * @param string $old_subject The old email subject.
     * @return string The modified email subject.
     */
    function cfpm_retrieve_password_subject_filter($old_subject){
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        /* translators: %s: Site name */
        $subject = sprintf(__('[%s] Password Reset'), $blogname);
        return $subject;
    }
    add_filter('retrieve_password_title', 'cfpm_retrieve_password_subject_filter', 10, 1);
}

if (!function_exists('cfpm_retrieve_password_message_filter')) {
    /**
     * Filter to customize the password reset email message.
     *
     * @param string $old_message  The default password reset email message.
     * @param string $key          The password reset key.
     * @param string $user_login   The user login name.
     * @return string              The customized password reset email message.
     */
    function cfpm_retrieve_password_message_filter($old_message, $key, $user_login) {
    
        $custom_message = get_option('forgot_mail_cwd');
        $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
        $message = str_replace(["%reseturl%", "%username%"], [$reset_url, $user_login], $custom_message);
        return $message;
    }
    add_filter('retrieve_password_message', 'cfpm_retrieve_password_message_filter', 10, 3);
}

if (!function_exists('cfpm_set_content_type')) {
    /**
     * Set the email content type to HTML.
     *
     * @param string $content_type The current email content type.
     * @return string The modified email content type.
     */
    function cfpm_set_content_type($content_type) {
        return 'text/html';
    }
    add_filter('wp_mail_content_type', 'cfpm_set_content_type');
}
?>

<?php

/**
 * Load the base class
 */
class Leaky_Paywall_Content_Auto_Archiver_Settings
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_wp_enqueue_scripts'));
        add_filter('leaky_paywall_settings_tab_sections', array($this, 'add_setting_section'));
        add_action('leaky_paywall_output_settings_fields', array($this, 'display_settings_fields'), 10, 2);
        add_action('leaky_paywall_update_settings', array($this, 'save_settings_fields'), 20, 3);
    }


    /**
     * Enqueues backend styles
     *
     * @since 1.0.0
     */
    public function admin_wp_enqueue_scripts($hook_suffix)
    {
        if ('toplevel_page_issuem-leaky-paywall' === $hook_suffix)
        wp_enqueue_script('leaky_paywall_caa_settings_js', LP_CAA_URL . 'js/issuem-leaky-paywall-settings.js', array('jquery'), LP_CAA_VERSION);
    }


    /**
     * Add Leaky Paywall - Content Auto Archiver section to general settings
     *
     * @since 1.0.0
     */
    public function add_setting_section($sections)
    {
        $sections['general'][] = 'content_auto_archiver';
        return $sections;
    }

    /**
     * Create and Display settings page
     *
     * @since 1.0.0
     */
    public function display_settings_fields($current_tab, $current_section)
    {

        if ($current_tab != 'general') {
            return;
        }

        if ($current_section != 'content_auto_archiver') {
            return;
        }

        $settings = $this->get_settings();
?>

        <h3 class="hndle"><span><?php _e('Content Auto Archiver Settings', 'lp-mailchimp'); ?></span></h3>

        <div class="inside">



                <table id="leaky_paywall_content_auto_archiver_settings_wrapper" class="form-table">
                    <tr>
                        <th><?php _e('Subscribe or Login Message', 'issuem-lp-caa'); ?></th>
                        <td>
                            <textarea id="subscribe_archive_login_message" class="large-text" name="subscribe_archive_login_message" cols="50" rows="3"><?php echo stripslashes($settings['subscribe_archive_login_message']); ?></textarea>
                            <p class="description">
                                <?php _e("Available replacement variables: {{SUBSCRIBE_LOGIN_URL}}", 'issuem-lp-caa'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Upgrade Message', 'issuem-lp-caa'); ?></th>
                        <td>
                            <textarea id="subscribe_archive_upgrade_message" class="large-text" name="subscribe_archive_upgrade_message" cols="50" rows="3"><?php echo stripslashes($settings['subscribe_archive_upgrade_message']); ?></textarea>
                            <p class="description">
                                <?php _e("Available replacement variables: {{SUBSCRIBE_LOGIN_URL}}", 'issuem-lp-caa'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p><strong><?php _e('Timewall Settings', 'issuem-lp-caa'); ?></strong></p>

                <table id="leaky_paywall_content_auto_archiver_wrapper" class="leaky-paywall-table">

                    <?php
                    $count = 0;
                    if (!empty($settings['expirations'])) {
                        foreach ($settings['expirations'] as $exp_post_type => $expiration) {
                            echo build_leaky_paywall_content_auto_archive_restriction_row($exp_post_type, $expiration, $count);
                            $count++;
                        }
                    }
                    ?>

                </table>

                <script type="text/javascript" charset="utf-8">
                    var content_auto_archiver_key_count = <?php echo $count; ?>;
                </script>

                <p>
                    <input class="button-secondary" id="add-expiration-row" class="add-new-issuem-leaky-paywall-expiration-row" type="submit" name="add_leaky_paywall_expiration_row" value="<?php _e('Add New Expiration', 'issuem-lp-caa'); ?>" />
                </p>

        </div>

<?php
    }

    /**
     * Get Leaky Paywall Content Auto Archiver options
     *
     * @since 1.0.0
     */
    public function get_settings()
    {

        $defaults = array(
            'expirations' => array(
                'article' => array(
                    'exp_value' => '1',
                    'exp_type'  => 'month',
                )
            ),
            'subscribe_archive_login_message'        => __('This content has been archived. <a href="{{SUBSCRIBE_LOGIN_URL}}">Log in or Subscribe</a> to a level that has access to archived content.', 'issuem-lp-caa'),
            'subscribe_archive_upgrade_message'        => __('This content has been archived. You must <a href="{{SUBSCRIBE_LOGIN_URL}}">upgrade your account</a> to a level that has access to archived content.', 'issuem-lp-caa'),
        );

        $defaults = apply_filters('leaky_paywall_content_auto_archiver_default_settings', $defaults);

        $settings = get_option('issuem-leaky-paywall-content-auto-archiver');

        return wp_parse_args($settings, $defaults);
    }

    /**
     * Update Leaky Paywall Content Auto Archiver options
     *
     * @since 1.0.0
     */
    public function update_settings($settings)
    {
        update_option('issuem-leaky-paywall-content-auto-archiver', $settings);
    }


    /**
     * Save Leaky Paywall - Content Auto Archiver options
     *
     * @since 1.0.0
     */
    public function save_settings_fields($settings, $current_tab, $current_section)
    {

        if ($current_tab != 'general') {
            return;
        }

        if ($current_section != 'content_auto_archiver') {
            return;
        }

        // Get the user options
        $settings = $this->get_settings();

        if (!empty($_POST['expirations'])) {

            $expirations = array();

            foreach ($_POST['expirations'] as $expiration) {

                $expirations[$expiration['post_type']] = array(
                    'exp_value' => $expiration['exp_value'],
                    'exp_type'  => $expiration['exp_type'],
                );
            }

            $settings['expirations'] = $expirations;
        } else {

            $settings['expirations'] = array();
        }

        if (!empty($_POST['subscribe_archive_login_message'])) {
            $settings['subscribe_archive_login_message'] = wp_kses_post( $_POST['subscribe_archive_login_message']);
        }

        if (!empty($_POST['subscribe_archive_upgrade_message'])) {
            $settings['subscribe_archive_upgrade_message'] = wp_kses_post( $_POST['subscribe_archive_upgrade_message']);
        }

        $this->update_settings($settings);
    }
}

new Leaky_Paywall_Content_Auto_Archiver_Settings();

<?php

/**
 * Registers zeen101's Leaky Paywall class
 *
 * @package zeen101's Leaky Paywall
 * @since 1.0.0
 */

/**
 * This class registers the main functionality
 *
 * @since 1.0.0
 */
if (!class_exists('Leaky_Paywall_Content_Auto_Archiver')) {

	class Leaky_Paywall_Content_Auto_Archiver
	{

		/**
		 * Class constructor, puts things in motion
		 *
		 * @since 1.0.0
		 */
		function __construct()
		{

			$settings = $this->get_settings();

			add_action('admin_init', array($this, 'upgrade'));

			add_action('admin_enqueue_scripts', array($this, 'admin_wp_enqueue_scripts'));

			add_action('leaky_paywall_filter_is_restricted', array($this, 'leaky_paywall_filter_is_restricted'), 10, 3);

			add_action('leaky_paywall_settings_form', array($this, 'settings_div'));
			add_action('leaky_paywall_update_settings', array($this, 'update_settings_div'));
		}

		/**
		 * Enqueues backend styles
		 *
		 * @since 1.0.0
		 */
		function admin_wp_enqueue_scripts($hook_suffix)
		{

			if ('toplevel_page_issuem-leaky-paywall' === $hook_suffix)
				wp_enqueue_script('leaky_paywall_caa_settings_js', LP_CAA_URL . 'js/issuem-leaky-paywall-settings.js', array('jquery'), LP_CAA_VERSION);
		}

		function leaky_paywall_filter_is_restricted($is_restricted, $restrictions, $post_id)
		{

			global $leaky_paywall_data, $blog_id;

			if (version_compare($leaky_paywall_data['Version'], '3.0.0', '>=')) {
				$settings = get_leaky_paywall_settings();
				$level_ids = leaky_paywall_subscriber_current_level_ids();
				if (!empty($level_ids)) {
					foreach ($level_ids as $level_id) {
						if (empty($settings['levels'][$level_id]['site']) || $blog_id == $settings['levels'][$level_id]['site'] || 'all' == $settings['levels'][$level_id]['site']) {
							$restrictions = $settings['levels'][$level_id];
						}
					}
				}
			} else {
				$restrictions = $restrictions;
			}

			if (empty($restrictions['access-archived-content']) || 'off' === $restrictions['access-archived-content']) {

				$settings = $this->get_settings();
				$lp_settings = get_leaky_paywall_settings();

				$keys = array_keys($settings['expirations']);

				if (is_singular($keys)) {

					if (!current_user_can('manage_options')) { //Admins can see it all

						// We don't ever want to block the login, subscription
						if (!is_page(array($lp_settings['page_for_login'], $lp_settings['page_for_subscription'], $lp_settings['page_for_profile'], $lp_settings['page_for_register']))) {

							global $post, $leaky_paywall;

							if (!empty($settings['expirations'][$post->post_type])) {

								$exp_value = $settings['expirations'][$post->post_type]['exp_value'];
								$exp_type = $settings['expirations'][$post->post_type]['exp_type'];

								$exp_time = strtotime('-' . $exp_value . ' ' . $exp_type . ' midnight');
								$post_time = strtotime($post->post_date);

								if ($post_time <= $exp_time) {

									add_filter('the_content', array($this, 'the_content_paywall'), 999);
									$is_restricted = false; //This is an expired post, so we want to use the expired message

								}
							}
						}
					}
				}
			}

			return $is_restricted;
		}

		function the_content_paywall($content)
		{

			global $leaky_paywall;
			$settings = $this->get_settings();

			add_filter('excerpt_more', '__return_false');

			//Remove the_content filter for get_the_excerpt calls
			remove_filter('the_content', array($this, 'the_content_paywall'), 999);
			$content = get_the_excerpt();
			add_filter('the_content', array($this, 'the_content_paywall'), 999);
			//Add the_content filter back for futhre the_content calls

			$message  = '<div id="leaky_paywall_message">';
			if (!is_user_logged_in()) {
				$message .= $this->replace_variables(stripslashes($settings['subscribe_archive_login_message']));
			} else {
				$message .= $this->replace_variables(stripslashes($settings['subscribe_archive_upgrade_message']));
			}
			$message .= '</div>';

			$new_content = $content . $message;

			return apply_filters('leaky_paywall_content_archived_subscribe_or_login_message', $new_content, $message, $content);
		}

		public function replace_variables($message)
		{

			$settings = get_leaky_paywall_settings();

			if (0 === $settings['page_for_subscription'])
				$subscription_url = get_bloginfo('wpurl') . '/?subscription'; //CHANGEME -- I don't really know what this is suppose to do...
			else
				$subscription_url = get_page_link($settings['page_for_subscription']);

			if (0 === $settings['page_for_profile'])
				$my_account_url = get_bloginfo('wpurl') . '/?my-account'; //CHANGEME -- I don't really know what this is suppose to do...
			else
				$my_account_url = get_page_link($settings['page_for_profile']);

			$message = str_ireplace('{{SUBSCRIBE_LOGIN_URL}}', $subscription_url, $message);
			$message = str_ireplace('{{SUBSCRIBE_URL}}', $subscription_url, $message);
			$message = str_ireplace('{{MY_ACCOUNT_URL}}', $my_account_url, $message);

			if (0 === $settings['page_for_login'])
				$login_url = get_bloginfo('wpurl') . '/?login'; //CHANGEME -- I don't really know what this is suppose to do...
			else
				$login_url = get_page_link($settings['page_for_login']);

			$message = str_ireplace('{{LOGIN_URL}}', $login_url, $message);

			//Deprecated
			if (!empty($settings['price'])) {
				$message = str_ireplace('{{PRICE}}', $settings['price'], $message);
			}
			if (!empty($settings['interval_count']) && !empty($settings['interval'])) {
				$message = str_ireplace('{{LENGTH}}', leaky_paywall_human_readable_interval($settings['interval_count'], $settings['interval']), $message);
			}

			return $message;
		}

		/**
		 * Get zeen101's Leaky Paywall - Subscriber Meta options
		 *
		 * @since 1.0.0
		 */
		function get_settings()
		{

			$defaults = array(
				'expirations' => array(
					'article' => array(
						'exp_value' => '1',
						'exp_type'  => 'month',
					)
				),
				'subscribe_archive_login_message'		=> __('This content has been archived. <a href="{{SUBSCRIBE_LOGIN_URL}}">Log in or Subscribe</a> to a level that has access to archived content.', 'issuem-lp-caa'),
				'subscribe_archive_upgrade_message'		=> __('This content has been archived. You must <a href="{{SUBSCRIBE_LOGIN_URL}}">upgrade your account</a> to a level that has access to archived content.', 'issuem-lp-caa'),
			);

			$defaults = apply_filters('leaky_paywall_content_auto_archiver_default_settings', $defaults);

			$settings = get_option('issuem-leaky-paywall-content-auto-archiver');

			return wp_parse_args($settings, $defaults);
		}

		/**
		 * Update zeen101's Leaky Paywall options
		 *
		 * @since 1.0.0
		 */
		function update_settings($settings)
		{

			update_option('issuem-leaky-paywall-content-auto-archiver', $settings);
		}

		/**
		 * Create and Display settings page
		 *
		 * @since 1.0.0
		 */
		function settings_div()
		{

			// Get the user options
			$settings = $this->get_settings();

			// Display HTML form for the options below
?>
			<div id="modules" class="postbox">

				<div class="handlediv" title="Click to toggle"><br /></div>

				<h3 class="hndle"><span><?php _e('Content Auto Archiver', 'issuem-lp-caa'); ?></span></h3>

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

			</div>
<?php

		}

		function update_settings_div()
		{

			if (isset($_GET['tab'])) {
				$tab = $_GET['tab'];
			} else if ($_GET['page'] == 'issuem-leaky-paywall') {
				$tab = 'general';
			} else {
				$tab = '';
			}

			if ($tab != 'general') {
				return;
			}

			$settings = $this->get_settings();

			if (!empty($_REQUEST['expirations'])) {

				$expirations = array();

				foreach ($_REQUEST['expirations'] as $expiration) {

					$expirations[$expiration['post_type']] = array(
						'exp_value' => $expiration['exp_value'],
						'exp_type'  => $expiration['exp_type'],
					);
				}

				$settings['expirations'] = $expirations;
			} else {

				$settings['expirations'] = array();
			}

			if (!empty($_REQUEST['subscribe_archive_login_message']))
				$settings['subscribe_archive_login_message'] = trim($_REQUEST['subscribe_archive_login_message']);

			if (!empty($_REQUEST['subscribe_archive_upgrade_message']))
				$settings['subscribe_archive_upgrade_message'] = trim($_REQUEST['subscribe_archive_upgrade_message']);

			$this->update_settings($settings);
		}

		/**
		 * Upgrade function, tests for upgrade version changes and performs necessary actions
		 *
		 * @since 1.0.0
		 */
		function upgrade()
		{

			$settings = $this->get_settings();

			if (isset($settings['version']))
				$old_version = $settings['version'];
			else
				$old_version = 0;

			/* Table Version Changes */
			if (isset($settings['db_version']))
				$old_db_version = $settings['db_version'];
			else
				$old_db_version = 0;

			$settings['version'] = LP_CAA_VERSION;
			$settings['db_version'] = LP_CAA_DB_VERSION;

			$this->update_settings($settings);
		}
	}
}

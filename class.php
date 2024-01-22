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

class Leaky_Paywall_Content_Auto_Archiver
{

	/**
	 * Class constructor, puts things in motion
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		add_action('leaky_paywall_filter_is_restricted', array($this, 'leaky_paywall_filter_is_restricted'), 10, 3);
	}

	public function leaky_paywall_filter_is_restricted($is_restricted, $restrictions, $post_id)
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

			$settings = get_leaky_paywall_content_auto_archive_settings();
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

	public function the_content_paywall($content)
	{

		global $leaky_paywall;
		$settings = get_leaky_paywall_content_auto_archive_settings();

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

}

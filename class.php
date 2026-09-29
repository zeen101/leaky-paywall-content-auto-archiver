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
		add_filter('leaky_paywall_current_user_can_access', array( $this, 'current_user_can_access' ), 10, 2 );
		add_filter('leaky_paywall_filter_is_restricted', array($this, 'leaky_paywall_filter_is_restricted'), 10, 3);
		add_filter('leaky_paywall_nag_message_text', array($this, 'the_content_paywall'), 20, 2);

		// After Subscribe Messages (10) and the Offer Engine (15): archive access is the real gate.
		add_filter('leaky_paywall_nag_type', array($this, 'nag_type'), 20, 2);
	}

	/**
	 * Give archived content its own nag type.
	 *
	 * List Builder takes over "subscribe" and "upgrade" nags with its modal,
	 * and LP core then skips the in-content nag, so the archive message never
	 * showed. List Builder ignores other types, so "archived" lets core
	 * render the archive message in the content.
	 *
	 * @param string $nag_type Current nag type.
	 * @param int    $post_id  Post being viewed.
	 * @return string
	 */
	public function nag_type($nag_type, $post_id)
	{
		$post_data = get_post($post_id);

		if ($post_data && $this->is_archived_content($post_data)) {
			return 'archived';
		}

		return $nag_type;
	}

	public function current_user_can_access( $can_access, $post_id ) {

		$post_data = get_post($post_id);

		if ( !$post_data ) {
			return $can_access;
		}

		if (!$this->is_archived_content($post_data)) {
			return $can_access;
		}

		// is archived content
		// check if the current user can access content
		$site = leaky_paywall_get_current_site();
		$lp_settings = get_leaky_paywall_settings();
		$level_ids = leaky_paywall_subscriber_current_level_ids();

		if ( empty( $level_ids ) ) {
			$can_access = false;
		} else {
			foreach ($level_ids as $level_id) {
				if (empty($lp_settings['levels'][$level_id]['site']) || $site == $lp_settings['levels'][$level_id]['site'] || 'all' == $lp_settings['levels'][$level_id]['site']) {
					$level = $lp_settings['levels'][$level_id];
				}
			}

			if ( isset( $level['access-archived-content'] ) && 'on' === $level['access-archived-content'] ) {
				$can_access = true;
			} else {
				$can_access = false;
			}
		}

		return $can_access;
	}

	public function leaky_paywall_filter_is_restricted($is_restricted, $restrictions, $post_id)
	{

		$post_data = get_post($post_id);

		if ($post_data) {
			if ($this->is_archived_content($post_data)) {
				$is_restricted = true;
			}
		}

		return $is_restricted;

	}

	public function is_archived_content( $post_data )
	{

		if ( $this->matches_restriction_exceptions(( $post_data->ID ))) {
			return false;
		}

		if ($this->visibility_allows_access( $post_data->ID )) {
			return false;
		}

		$user = wp_get_current_user();

		if ( $user ) {
			if (leaky_paywall_user_can_bypass_paywall_by_role($user)) {
				return false;
			}
		}

		$settings = get_leaky_paywall_content_auto_archive_settings();

		if (!empty($settings['expirations'][$post_data->post_type])) {

			$exp_value = $settings['expirations'][$post_data->post_type]['exp_value'];
			$exp_type = $settings['expirations'][$post_data->post_type]['exp_type'];

			$exp_time = strtotime('-' . $exp_value . ' ' . $exp_type . ' midnight');
			$post_time = strtotime($post_data->post_date);

			if ($post_time <= $exp_time) {
				return true;
			}
		}

		return false;
	}

	public function visibility_allows_access( $post_id ) {

		$visibility = get_post_meta($post_id, '_issuem_leaky_paywall_visibility', true);
		$level_ids  = leaky_paywall_subscriber_current_level_ids();

		if (false !== $visibility && ! empty($visibility['visibility_type']) && 'default' !== $visibility['visibility_type']) {

			switch ($visibility['visibility_type']) {

				case 'only':
					$only = array_intersect($level_ids, $visibility['only_visible']);
					if (empty($only)) {
						return false;
					}
					break;

				case 'always':
					$always = array_intersect($level_ids, $visibility['always_visible']);

					if (in_array(-1, $visibility['always_visible'])) { // -1 = Everyone.
						return true; // always visible, don't need process anymore.
					}

					// level id of the user matches those selected in the settings, and the user currently has access to that level.
					if (! empty($always) && leaky_paywall_user_has_access()) {
						return true;
					}
					break;

				case 'onlyalways':
					$onlyalways = array_intersect($level_ids, $visibility['only_always_visible']);
					if (empty($onlyalways)) {
						return false;
					} elseif (! empty($onlyalways) && leaky_paywall_user_has_access()) {
						return true; // always visible, don't need process anymore.
					}
					break;
			}
		}

		return false;
	}


	public function matches_restriction_exceptions( $post_id ) {

		$match    = false;
		$settings = get_leaky_paywall_settings();

		$category_exceptions    = $settings['post_category_exceptions'];
		$category_exception_ids = explode(',', $category_exceptions);

		if (! empty($category_exception_ids)) {
			$post_categories = get_the_category($post_id);

			foreach ($post_categories as $cat) {
				if (in_array($cat->term_id, $category_exception_ids)) {
					$match = true;
				}
			}
		}

		$tag_exceptions    = $settings['post_tag_exceptions'];
		$tag_exception_ids = explode(',', $tag_exceptions);

		if (! empty($tag_exception_ids)) {
			$post_tag = get_the_tags($post_id);

			if (is_array($post_tag)) {
				foreach ($post_tag as $tag) {
					if (in_array($tag->term_id, $tag_exception_ids)) {
						$match = true;
					}
				}
			}
		}

		return $match;

	}

	public function the_content_paywall($text, $post_id)
	{

		$post_data = get_post( $post_id );

		if ( !$this->is_archived_content( $post_data ) ) {
			return $text;
		}

		$settings = get_leaky_paywall_content_auto_archive_settings();

		if (!is_user_logged_in()) {
			$new_content = $this->replace_variables(stripslashes($settings['subscribe_archive_login_message']));
		} else {
			$new_content = $this->replace_variables(stripslashes($settings['subscribe_archive_upgrade_message']));
		}

		return apply_filters('leaky_paywall_content_archived_subscribe_or_login_message', $new_content, $text, $post_id);
	}

	public function replace_variables($message)
	{

		$settings = get_leaky_paywall_settings();

		$subscription_url = get_page_link($settings['page_for_subscription']);
		$my_account_url = get_page_link($settings['page_for_profile']);
		$login_url = get_page_link($settings['page_for_login']);

		$message = str_ireplace('{{SUBSCRIBE_LOGIN_URL}}', $subscription_url, $message);
		$message = str_ireplace('{{SUBSCRIBE_URL}}', $subscription_url, $message);
		$message = str_ireplace('{{MY_ACCOUNT_URL}}', $my_account_url, $message);
		$message = str_ireplace('{{LOGIN_URL}}', $login_url, $message);

		return $message;
	}

}

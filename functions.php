<?php

/**
 * @package zeen101's Leaky Paywall - Content Auto-Archiver
 * @since 1.0.0
 */

if (!function_exists('build_leaky_paywall_content_auto_archiver_subscription_levels_row_addon_filter')) {

	/*
	 * This will actually automatically save because of how we're saving the HTTP POST $level array
	 */
	function build_leaky_paywall_content_auto_archiver_subscription_levels_row_addon_filter($level, $row_key)
	{

		if (empty($level['access-archived-content'])) {
			$level['access-archived-content'] = 'off';
		}

		echo '<tr>';
		echo '<th>' . __('Archived Content', 'issuem-lp-caa') . '</th>';
		echo '<td><input id="level-access-archived-content-' . $row_key . '" class="access-archived-content" type="checkbox" name="levels[' . $row_key . '][access-archived-content]" value="on" ' . checked('on', $level['access-archived-content'], false) . ' /> Allow subscribers of this level to access archived content.</td>';
		echo '</tr>';
	}

	add_action('leaky_paywall_after_subscription_levels_row', 'build_leaky_paywall_content_auto_archiver_subscription_levels_row_addon_filter', 10, 2);
}

if (!function_exists('build_leaky_paywall_content_auto_archive_restriction_row')) {

	function build_leaky_paywall_content_auto_archive_restriction_row($exp_post_type, $expiration, $count)
	{

		$return  = '<tr>';
		$return .= '<td>';
		$return .= '<div class="issuem-leaky-paywall-row-expiration">';

		$return .= '<select name="expirations[' . $count . '][post_type]">';

		$hidden_post_types = array('attachment', 'revision', 'nav_menu_item');
		$post_types = get_post_types(array(), 'objects');

		foreach ($post_types as $post_type) {

			if (in_array($post_type->name, $hidden_post_types))
				continue;

			$return .= '<option value="' . $post_type->name . '" ' . selected($post_type->name, $exp_post_type, false) . '>' . $post_type->labels->name . '</option>';
		}
		$return .= '</select>';

		$return .= ' <span>' . __('place behind the paywall after', 'issuem-lp-caa') . '</span> ';

		$return .= '<input type="text" class="exp_value small-text" name="expirations[' . $count . '][exp_value]" value="' . $expiration['exp_value'] . '" placeholder="' . __('#', 'issuem-lp-caa') . '" />';

		$return .= '<select id="exp_type" name="expirations[' . $count . '][exp_type]">';
		$return .= '  <option value="day" ' . selected('day' === $expiration['exp_type'], true, false) . '>' . __('Day(s)', 'issuem-lp-caa') . '</option>';
		$return .= '  <option value="week" ' . selected('week' === $expiration['exp_type'], true, false) . '>' . __('Week(s)', 'issuem-lp-caa') . '</option>';
		$return .= '  <option value="month" ' . selected('month' === $expiration['exp_type'], true, false) . '>' . __('Month(s)', 'issuem-lp-caa') . '</option>';
		$return .= '  <option value="year" ' . selected('year' === $expiration['exp_type'], true, false) . '>' . __('Year(s)', 'issuem-lp-caa') . '</option>';
		$return .= '</select>';

		$return .= '<span class="delete-x delete-expiration-row">&times;</span>';

		$return .= '</div>';

		$return .= '</td>';
		$return .= '</tr>';

		return $return;
	}
}

if (!function_exists('leaky_paywall_content_auto_archive_subscription_options_allowed_content')) {

	function leaky_paywall_content_auto_archive_subscription_options_allowed_content($allowed_content, $level)
	{

		if (!empty($level['access-archived-content']) && 'on' === $level['access-archived-content'])
			$allowed_content .= '<p>' . __('Archive Access', 'issuem-lp-caa') . '</p>';

		return $allowed_content;
	}
	add_filter('leaky_paywall_subscription_options_allowed_content', 'leaky_paywall_content_auto_archive_subscription_options_allowed_content', 10, 2);
}

if (!function_exists('build_leaky_paywall_content_auto_archive_add_new_restriction_row_ajax')) {

	/**
	 * AJAX Wrapper
	 *
	 * @since 1.0.0
	 */
	function build_leaky_paywall_content_auto_archive_add_new_restriction_row_ajax()
	{
		if (isset($_REQUEST['row-key']))
			die(build_leaky_paywall_content_auto_archive_restriction_row('article', array('exp_value' => 1, 'exp_type' => 'month'), $_REQUEST['row-key']));
		else
			die();
	}
	add_action('wp_ajax_leaky-paywall-content-auto-archive-add-new-expiration-row', 'build_leaky_paywall_content_auto_archive_add_new_restriction_row_ajax');
}
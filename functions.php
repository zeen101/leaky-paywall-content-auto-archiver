<?php

/**
 * @package zeen101's Leaky Paywall - Content Auto-Archiver
 * @since 1.0.0
 */


/*
* This will actually automatically save because of how we're saving the HTTP POST $level array
*/
function build_leaky_paywall_content_auto_archiver_subscription_levels_row_addon_filter($level, $row_key)
{

	if (empty($level['access-archived-content'])) {
		$level['access-archived-content'] = 'off';
	}

	$row_key_attr = esc_attr( $row_key );

	echo '<tr>';
	echo '<th>' . esc_html__('Archived Content', 'issuem-lp-caa') . '</th>';
	echo '<td><input id="level-access-archived-content-' . $row_key_attr . '" class="access-archived-content" type="checkbox" name="levels[' . $row_key_attr . '][access-archived-content]" value="on" ' . checked('on', $level['access-archived-content'], false) . ' /> ' . esc_html__('Allow subscribers of this level to access archived content.', 'issuem-lp-caa') . '</td>';
	echo '</tr>';
}

add_action('leaky_paywall_after_subscription_levels_row', 'build_leaky_paywall_content_auto_archiver_subscription_levels_row_addon_filter', 10, 2);



function build_leaky_paywall_content_auto_archive_restriction_row($exp_post_type, $expiration, $count)
{

	// Force to int — this value is echoed into name attributes and every
	// caller passes a numeric row key. Reflected input via the AJAX handler
	// would XSS here without this coercion.
	$count      = absint( $count );
	$exp_value  = isset( $expiration['exp_value'] ) ? $expiration['exp_value'] : '';
	$exp_type   = isset( $expiration['exp_type'] ) ? $expiration['exp_type'] : 'month';

	$return  = '<tr>';
	$return .= '<td>';
	$return .= '<div class="issuem-leaky-paywall-row-expiration">';

	$return .= '<select name="expirations[' . $count . '][post_type]">';

	$hidden_post_types = array('attachment', 'revision', 'nav_menu_item');
	$post_types = get_post_types(array(), 'objects');

	foreach ($post_types as $post_type) {

		if (in_array($post_type->name, $hidden_post_types))
			continue;

		$return .= '<option value="' . esc_attr( $post_type->name ) . '" ' . selected($post_type->name, $exp_post_type, false) . '>' . esc_html( $post_type->labels->name ) . '</option>';
	}
	$return .= '</select>';

	$return .= ' <span>' . esc_html__('place behind the paywall after', 'issuem-lp-caa') . '</span> ';

	$return .= '<input type="text" class="exp_value small-text" name="expirations[' . $count . '][exp_value]" value="' . esc_attr( $exp_value ) . '" placeholder="' . esc_attr__('#', 'issuem-lp-caa') . '" />';

	$return .= '<select id="exp_type" name="expirations[' . $count . '][exp_type]">';
	$return .= '  <option value="day" ' . selected('day' === $exp_type, true, false) . '>' . esc_html__('Day(s)', 'issuem-lp-caa') . '</option>';
	$return .= '  <option value="week" ' . selected('week' === $exp_type, true, false) . '>' . esc_html__('Week(s)', 'issuem-lp-caa') . '</option>';
	$return .= '  <option value="month" ' . selected('month' === $exp_type, true, false) . '>' . esc_html__('Month(s)', 'issuem-lp-caa') . '</option>';
	$return .= '  <option value="year" ' . selected('year' === $exp_type, true, false) . '>' . esc_html__('Year(s)', 'issuem-lp-caa') . '</option>';
	$return .= '</select>';

	$return .= '<span class="delete-x delete-expiration-row">&times;</span>';

	$return .= '</div>';

	$return .= '</td>';
	$return .= '</tr>';

	return $return;
}


function leaky_paywall_content_auto_archive_subscription_options_allowed_content($allowed_content, $level)
{

	if (!empty($level['access-archived-content']) && 'on' === $level['access-archived-content'])
		$allowed_content .= '<p>' . __('Archive Access', 'issuem-lp-caa') . '</p>';

	return $allowed_content;
}
add_filter('leaky_paywall_subscription_options_allowed_content', 'leaky_paywall_content_auto_archive_subscription_options_allowed_content', 10, 2);



/**
 * AJAX Wrapper — appends a fresh expiration row to the settings table.
 *
 * Requires manage_options and a valid nonce. Previously, the endpoint had
 * no capability or CSRF check, and reflected $_REQUEST['row-key'] straight
 * into the returned HTML's name attributes — an authenticated subscriber
 * could echo arbitrary HTML back via a crafted row-key.
 *
 * @since 1.0.0
 */
function build_leaky_paywall_content_auto_archive_add_new_restriction_row_ajax()
{
	check_ajax_referer( 'lp_caa_add_expiration', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', '', 403 );
	}

	$row_key = isset( $_REQUEST['row-key'] ) ? absint( $_REQUEST['row-key'] ) : 0;

	die( build_leaky_paywall_content_auto_archive_restriction_row( 'article', array( 'exp_value' => 1, 'exp_type' => 'month' ), $row_key ) );
}
add_action('wp_ajax_leaky-paywall-content-auto-archive-add-new-expiration-row', 'build_leaky_paywall_content_auto_archive_add_new_restriction_row_ajax');


function get_leaky_paywall_content_auto_archive_settings() {

	$admin = new Leaky_Paywall_Content_Auto_Archiver_Settings();
	return $admin->get_settings();

}

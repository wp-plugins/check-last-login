<?php
/*
Plugin Name: Check Last Login
Plugin URI: http://www.techforum.sk/
Description: Checks user's login status
Version: 0.1
Author: Ján Bočínec
Author URI: http://johnnypea.wp.sk/
License: GPL2
*/

register_activation_hook(__FILE__, 'cron_activation');
register_deactivation_hook(__FILE__, 'cron_deactivation');

add_action('cron_daily_event', 'is_user_active');

function cron_activation() {
	wp_schedule_event(time(), 'daily', 'cron_daily_event');
}

function cron_deactivation() {
	wp_clear_scheduled_hook('cron_daily_event');
}

function registration_login($user_ID) {
	update_usermeta( $user_ID, 'last_user_login', 'No login' );
}
add_action('user_register', 'registration_login');

function last_user_login($login) {
    $user = get_userdatabylogin($login);
    update_usermeta( $user->ID, 'last_user_login', time() );
}
add_action('wp_login','last_user_login');

function is_user_active() {
	global $wpdb;	
	$last_user_login = $wpdb->get_results("SELECT * FROM $wpdb->users INNER JOIN $wpdb->usermeta ON ID = user_id WHERE meta_key  = 'last_user_login' AND meta_value = 'No login' AND DATEDIFF(NOW(),user_registered) >= 30", ARRAY_A);
	
	if ( $last_user_login )
		foreach ( $last_user_login as $user_login )
			wp_delete_user( $user_login['ID'] );	
}

function users_manage_columns( $empty, $column_name, $userid) {
	$user_data = get_userdata( $userid );
	if ( $column_name == 'registration_date' ) {
		return  date( "j.n. Y G:i", strtotime($user_data->user_registered) );
	} elseif( $column_name == 'last_log_in' ) {
		$last_user_login = get_user_meta( $userid, 'last_user_login', TRUE );
		if ( $last_user_login && $last_user_login == 'No login') {
			return $last_user_login;
		} elseif ( $last_user_login ) {
			return date( "j.n. Y G:i", $last_user_login );
		}
	}
}
add_filter( 'manage_users_custom_column', 'users_manage_columns', 10, 3);

function users_edit_columns($columns) {
		$columns['registration_date'] = 'Registered';
		$columns['last_log_in'] = 'Last log in';
		return $columns;
}
// add custom columns
add_filter( 'manage_users_columns', 'users_edit_columns');
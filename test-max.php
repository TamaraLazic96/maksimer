<?php
/*
Plugin Name: Max Event
Plugin
Description: Something quick and simple
Author: Tamara L.
Version: 1.0.1
Author URI:
*/
require_once 'EventClient.php';

function maxCreateTableEvent() {
	global $wpdb;
	$table          = EventClient::TABLE_PREFIX . EventClient::TABLE_NAME;
	$charsetCollate = $wpdb->get_charset_collate();

	if ( $wpdb->get_var( "show tables like '$table'" ) != $table ) {
		$sql = "CREATE TABLE `" . $table . "` ( ";
		$sql .= "  `id`  int(11)   NOT NULL auto_increment, ";
		$sql .= "  `user_id`  int(128)   NOT NULL, ";
		$sql .= "  `event_id`  int(128)   NOT NULL, ";
		$sql .= "  `name`  varchar(255) NOT NULL, ";
		$sql .= "  `price`  int(128) NOT NULL, ";
		$sql .= "  `currency`  varchar(10), ";
		$sql .= "  `venue`  varchar(255) NOT NULL, ";
		$sql .= "  `city`  varchar(255) NOT NULL, ";
		$sql .= "  `country_code` varchar(10) NOT NULL, ";
		$sql .= "  `is_sold` tinyint(1) DEFAULT 0 NOT NULL, ";
		$sql .= "  `event_date` datetime DEFAULT '1970-01-01 00:00:01' NOT NULL, ";
		$sql .= "   PRIMARY KEY  (id) ";
		$sql .= ") " . $charsetCollate;

		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
register_activation_hook( __FILE__, 'maxCreateTableEvent' );

add_shortcode( 'max_event_display', 'maxEventsDisplay' );
function maxEventsDisplay() {
	return '<div class="max-main">
			<div class="max-navigation">
			<ul>
			<li data-prev="" id="prev-page-event">Previous</li>
			<li data-first="" id="first-page-event">Home</li>
			<li data-next="" id="next-page-event">Next</li>
			</ul>
			<ul>
			<li><span>Sort By:</span></li>
			<li id="max-label" data-filter="sort=label">Label</li>
			<li id="max-date" data-filter="sort=datetime">Date</li>
			</ul>
			</div>
			<div class="max-datepicker">
			<span>Pick Date:</span>
			<input type="text" name="daterange" id="max-datepicker" value="" />
			<button id="max-reset-date" class="btn btn-dark">Reset Date</button>
			</div>
			<div class="loader">Loading data...</div>
			<div id="events-max" class="justify-content-center">
			</div>';
}

function eventTemplate( $eventClient ) {

	$body = "";

	foreach ( $eventClient->events as $event ) {
		$date  = new DateTime( $event->datetime );
		$venue = EventClient::getVenue( $event->venue_id );

		$button  = "";
		$soldOut = "";
		$price   = "";
		if ( $event->sold_out_date ) {
			$soldOut = "Sold Out!";
		}
		if ( is_user_logged_in() ) {
			$button = "<button data-eventId='$event->id' class='save-user-event'>Save</button>";
		}
		if ( ! $event->costing_capacity ) {
			$price = "<p>Price: " . $event->costing_capacity . $event->currency . "</p>";
		}

		$body .= "
<div class='card' style='width: 18rem;margin:0 auto'>
  <div class='max-card-body'>
    <h5 class='card-title'>$event->label</h5>
    <p>Date: " . $date->format( 'Y/m/d' ) . "</p>
    $price
    <p>Venue: $venue->label</p>
    <p>City: $venue->city</p><p>Country Code: $venue->country_code</p>
    <p class='card-text sold'>" . $soldOut . "</p>
    $button
  </div>
</div>";
	}

	return [
		"body" => $body,
		"next" => $eventClient->next,
		"prev" => $eventClient->prev,
	];
}

add_action( 'wp_ajax_nopriv_event_page', 'eventMaxCall' );
add_action( 'wp_ajax_event_page', 'eventMaxCall' );
function eventMaxCall() {
	$eventClient = ( new EventClient() )->filterEvents( $_REQUEST['sort'], $_REQUEST['page'], $_REQUEST['dateFrom'], $_REQUEST['dateTo'] );

	echo json_encode( eventTemplate( $eventClient ) );
	wp_die();
}

add_action( 'wp_ajax_event_save', 'saveEventMaxCall' );
function saveEventMaxCall() {
	$user        = _wp_get_current_user();
	$eventClient = ( new EventClient() )->saveEvent( $_REQUEST['event'], $user->ID );
	echo json_encode( $eventClient );
	wp_die();
}

add_action( 'wp_enqueue_scripts', 'includeScriptMax' );
function includeScriptMax() {
	if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
		wp_enqueue_script( 'jquery' );
	}
	wp_enqueue_script( 'test-max', plugins_url( '/assets/test-max.js', __FILE__ ) );
	wp_enqueue_script( 'cookie-plugin', plugins_url( '/assets/jquery-cookie/jquery.cookie.js', __FILE__ ) );

	wp_enqueue_script( 'moment-js', plugins_url( '/assets/moment/moment.min.js', __FILE__ ) );
	wp_enqueue_script( 'datepicker-script', plugins_url( '/assets/datepicker/daterangepicker.js', __FILE__ ) );

	wp_localize_script( 'test-max', 'maxData', [ 'maxURL' => admin_url( 'admin-ajax.php' ) ] );

	wp_enqueue_style( 'test-max', plugins_url( '/assets/style.css', __FILE__ ) );
	wp_enqueue_style( 'datepicker-style', plugins_url( '/assets/datepicker/daterangepicker.css', __FILE__ ) );
}
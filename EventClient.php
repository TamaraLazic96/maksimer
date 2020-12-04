<?php


class EventClient {

	const TABLE_NAME = "event";
	const TABLE_PREFIX = "max_";
	const API = "https://sandbox.pims.io/api/";
	const EVENT = "events";
	const VENUE = "venues";

	const USERNAME = "sandbox";
	const PASSWORD = "c5jI1ABi8d0x87oWfVzvXALqkf0hToGq";

	public $events;
	public $next = null;
	public $prev = null;
	public $first = 1;


	public function filterEvents( $sort, $page, $dateFrom, $dateTo ) {
		$pageNum = ! empty( $page ) ? "&page=" . $page : "";
		$date    = ! empty( $dateFrom ) && ! empty( $dateTo ) ? "&from_datetime=" . $dateFrom . "&to_datetime=" . $dateTo : "";
		$sort    = empty( $sort ) ? "?sort=-datetime" : "?sort=" . $sort;

		$clientWP = wp_remote_get( self::API . self::EVENT . $sort . $pageNum . $date, [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( self::USERNAME . ':' . self::PASSWORD )
			]
		] );

		if ( ! array_key_exists( 'body', $clientWP ) || empty( $clientWP['body'] ) ) {
			return null;
		}

		$obj          = json_decode( $clientWP['body'] );
		$this->events = $obj->_embedded->events;

		if ( ! empty( $obj->_links->next ) ) {
			$this->next = (int) $page + 1;
		}

		if ( ! empty( $obj->_links->prev ) ) {
			$this->prev = (int) $page - 1;
		}

		// this is totally unnecessary
		$this->first = 1;

		return $this;
	}

	public static function getVenue( $id ) {
		$clientWP = wp_remote_get( self::API . self::VENUE . '/' . $id, [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( self::USERNAME . ':' . self::PASSWORD )
			]
		] );

		if ( ! array_key_exists( 'body', $clientWP ) || empty( $clientWP['body'] ) ) {
			return null;
		}

		return json_decode( $clientWP['body'] );
	}

	private function getEvent( $id ) {
		$clientWP = wp_remote_get( self::API . self::EVENT . '/' . $id, [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( self::USERNAME . ':' . self::PASSWORD )
			]
		] );

		if ( ! array_key_exists( 'body', $clientWP ) || empty( $clientWP['body'] ) ) {
			return null;
		}

		return json_decode( $clientWP['body'] );
	}

	public function saveEvent( $id, $userId ) {
		global $wpdb;
		$event = $this->getEvent( $id );
		$venue = self::getVenue( $event->venue_id );;

		if($wpdb->get_row('SELECT * FROM ' . self::TABLE_PREFIX . self::TABLE_NAME . '  WHERE user_id = ' . $userId . ' and event_id = ' . $event->id ))
			return 'exists';

		return $wpdb->insert( self::TABLE_PREFIX . self::TABLE_NAME, [
			'user_id'      => $userId,
			'event_id'     => $event->id,
			'name'         => $event->label,
			'price'        => $event->costing_capacity ? $event->costing_capacity : 0,
			'currency'     => $event->currency,
			'venue'        => $venue->label,
			'city'         => $venue->city,
			'country_code' => $venue->country_code,
			'is_sold'      => $event->sold_out_date ? 1 : 0,
			'event_date'   => $event->datetime,
		] );
	}
}
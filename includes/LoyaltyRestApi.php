<?php

namespace Loyalty\includes;

use WP_REST_Request;
use WP_REST_Response;

class LoyaltyRestApi
{
	public function __construct()
	{
		add_action('rest_api_init', function () {
			register_rest_route('loyalty/v1', '/cardNumber', array(
				'methods' => 'POST',
				'callback' => array($this, 'update_card_number'),
			));
		});
	}

	public function update_card_number(WP_REST_Request $request): WP_REST_Response
	{
		$user_id = $request->get_param('user_id');
		$card_number = $request->get_param('card_number');

		if (empty($user_id) || empty($card_number)) {
			return new WP_REST_Response(array('error' => 'Invalid parameters'), 400);
		}

		update_user_meta($user_id, 'card_number', $card_number);

		return new WP_REST_Response(array('success' => true), 200);
	}
}

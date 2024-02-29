<?php

namespace Loyalty\includes;

class LoyaltySyncBalance
{
	public function __construct()
	{
		add_action('woocommerce_add_to_cart', array($this, 'sync_balance'));
	}

	public function sync_balance(): void
	{
		$user_id = get_current_user_id();
		$user_response = LoyaltyApi::checkUserById($user_id);

		\WC_Points_Rewards_Manager::set_points_balance(
			$user_id,
			$user_response['totalPoints'],
			'manual_sync_with_api'
		);
	}
}

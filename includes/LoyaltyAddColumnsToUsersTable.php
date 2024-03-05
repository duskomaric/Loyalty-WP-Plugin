<?php

namespace Loyalty\includes;

use WC_Points_Rewards_Manager;

require  ABSPATH . '/wp-content/plugins/woocommerce-points-and-rewards/includes/class-wc-points-rewards-manager.php';


class LoyaltyAddColumnsToUsersTable {

	public function __construct()
	{
		add_filter( 'manage_users_columns', array($this, 'add_columns_headers') );
		add_filter( 'manage_users_custom_column', array($this, 'add_columns_data'), 10, 3 );
	}

	public function add_columns_headers($columns): array
	{
		$columns['user_id'] = 'User ID';
		$columns['balance'] = 'Balance';
		$columns['card_number'] = 'Card Number';

		return $columns;
	}

	public function add_columns_data($value, $column_name, $user_id): string
	{
		return match ($column_name) {
			'user_id' => $user_id,
			'balance' => WC_Points_Rewards_Manager::get_users_points( $user_id ),
			'card_number' => get_user_meta( $user_id, 'card_number', true ) ?: '',
			default => $value,
		};
	}
}
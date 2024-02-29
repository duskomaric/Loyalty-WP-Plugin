<?php

/**
 *
 * Plugin Name: Loyalty Plugin
 * Description: Sync points with API
 * Requires PHP: 7.4
 * Version: 2.1.0
 */

namespace Loyalty;

use Loyalty\includes\LoyaltyApi;
use Loyalty\includes\LoyaltySettingsPage;
use Loyalty\includes\LoyaltyCheckRequiredPlugins;
use Loyalty\includes\LoyaltyUserRegistration;
use Loyalty\includes\LoyaltySyncBalance;
use Loyalty\includes\LoyaltyRestApi;

require 'includes/LoyaltySettingsPage.php';
require 'includes/LoyaltyCheckRequiredPlugins.php';
require 'includes/LoyaltyApi.php';
require 'includes/LoyaltyUserRegistration.php';
require 'includes/LoyaltySyncBalance.php';
require 'includes/LoyaltyRestApi.php';

class Loyalty_Plugin
{
    public function __construct()
    {
	    add_action('wc_points_rewards_after_increase_points', array($this, 'points_added_callback'), 10, 5);
	    add_filter('wc_points_rewards_decrease_points', array($this, 'points_deduction_callback'), 10, 5);

        new LoyaltyApi();
        new LoyaltySettingsPage();
        new LoyaltyCheckRequiredPlugins();
        new LoyaltyUserRegistration();
		new LoyaltySyncBalance();
		new LoyaltyRestApi();
    }

	public function points_added_callback($user_id, $points, $event_type, $data, $order_id): void
	{
		if ($event_type === 'account-signup' || $event_type === 'manual_sync_with_api') {
			return;
		}

		LoyaltyApi::updatePoints($user_id, $points, 0);

		error_log('Points Added');
		error_log('User ID: ' . $user_id);
		error_log('Points Added: ' . $points);
		error_log('Event Type: ' . $event_type);
		error_log('Order ID: ' . $order_id);
	}

	public function points_deduction_callback($points, $user_id, $event_type, $data, $order_id)
	{
		LoyaltyApi::updatePoints($user_id, 0, $points);

		error_log('Points Deducted');
		error_log('User ID: ' . $user_id);
		error_log('Points Deducted: ' . $points);
		error_log('Event Type: ' . $event_type);
		error_log('Order ID: ' . $order_id);

		// Return the original points value without modification
		// for points and rewards plugin to process
		return $points;
	}

}

new Loyalty_Plugin();

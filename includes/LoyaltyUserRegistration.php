<?php

namespace Loyalty\includes;

class LoyaltyUserRegistration
{

	public function __construct()
	{
		add_action('woocommerce_edit_account_form', array($this, 'add_card_number_to_account_edit_form'));
		add_action('woocommerce_register_form', array($this, 'add_card_number_to_registration_form'));
		add_action('woocommerce_created_customer', array($this, 'createUser'));
	}

	public function add_card_number_to_account_edit_form(): void
	{
		$user_id = get_current_user_id();
		$card_number = get_user_meta($user_id, 'card_number', true);
		?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="card_number">Loyalty Kartica <span class="required">*</span></label>
            <input type="number" class="woocommerce-Input woocommerce-Input--text input-text" name="card_number"
                   id="card_number" value="<?php echo esc_attr($card_number); ?>"/>
        </p>
		<?php
	}

	public function add_card_number_to_registration_form(): void
	{
		?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="card_number">Loyalty Kartica <span class="required">*</span></label>
            <input type="number" class="woocommerce-Input woocommerce-Input--text input-text" name="card_number"
                   id="card_number" value="">
        </p>
		<?php
	}

	public function createUser($user_id): void
	{
		LoyaltyApi::createUser($user_id);
		$user_response = LoyaltyApi::checkUserById($user_id);

        \WC_Points_Rewards_Manager::set_points_balance(
                $user_id,
                $user_response['totalPoints'],
                'manual_sync_with_api'
        );
	}
}



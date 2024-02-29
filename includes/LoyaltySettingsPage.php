<?php

namespace Loyalty\includes;

class LoyaltySettingsPage
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'register_loyalty_settings_page'));
		add_action('admin_init', array($this, 'register_loyalty_settings'));
		add_filter('plugin_action_links_Loyalty/LoyaltyPlugin.php', array($this, 'add_loyalty_plugin_settings_link'));
	}

	private array $field_icons = array(
		'loyalty_username' => 'admin-users',
		'loyalty_password' => 'lock',
		'loyalty_token' => 'admin-network',
		'loyalty_base_api_path' => 'admin-site'
	);

	public function register_loyalty_settings_page(): void
	{
		add_menu_page(
			'Loyalty Settings',
			'Loyalty',
			'manage_options',
			'loyalty-settings',
			array($this, 'render_custom_settings_page'),
			'dashicons-share-alt'
		);
	}

	public function register_loyalty_settings(): void
	{
		register_setting('loyalty-settings-group', 'loyalty_username');
		register_setting('loyalty-settings-group', 'loyalty_password');
		register_setting('loyalty-settings-group', 'loyalty_token');
		register_setting('loyalty-settings-group', 'loyalty_base_api_path');
	}

	public function render_custom_settings_page(): void
	{
		?>
        <style>
            .loyalty-settings-wrap {
                max-width: 700px;
                margin: 25px auto;
                padding: 25px;
                background-color: #fefefe;
                border: 1px solid #ccc;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            .loyalty-settings-wrap h2 {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
            }
            .loyalty-settings-wrap h2 .dashicons {
                width: auto;
                height: auto;
                font-size: 36px;
            }
            .loyalty-settings-wrap input[type="password"],
            .loyalty-settings-wrap input[type="text"] {
                width: calc(100% - 20px);
                border: 1px solid #ccc;
                border-radius: 5px;
                background-color: #fff;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
                padding: 8px 8px 8px 40px;
            }
            .loyalty-settings-wrap input[type="password"]:focus,
            .loyalty-settings-wrap input[type="text"]:focus {
                border-color: #007cba;
                box-shadow: 0 0 0 2px rgba(0, 123, 186, 0.25);
            }
            .loyalty-settings-wrap .form-field {
                margin-bottom: 20px;
                position: relative;
            }
            .loyalty-settings-wrap .form-field .dashicons {
                position: absolute;
                left: 15px;
                top: 65%;
                transform: translateY(-50%);
                color: #555;
            }
            .loyalty-settings-wrap .submit input {
                padding: 14px 24px;
                font-size: 18px;
                background-color: #007cba;
                color: #fff;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s ease;
            }
            .loyalty-settings-wrap .submit input:hover {
                background-color: #005b82;
            }
        </style>

        <div class="wrap loyalty-settings-wrap">
            <h2><span class="dashicons dashicons-share-alt"></span>Loyalty Settings</h2>
            <form method="post" action="options.php">
				<?php settings_fields('loyalty-settings-group'); ?>
				<?php do_settings_sections('loyalty-settings-group'); ?>
				<?php
				foreach ($this->field_icons as $field => $icon) {
					?>
                    <div class="form-field">
                        <label for="<?= $field; ?>">
                            <span class="dashicons dashicons-<?= $icon; ?>"></span>
							<?= ucfirst(str_replace('_', ' ', $field)); ?>:
                        </label>
                        <input type="<?= strpos($field, 'password') !== false ? 'password' : 'text'; ?>" id="<?= $field; ?>" name="<?= $field; ?>" value="<?= esc_attr(get_option($field)); ?>" />
                    </div>
					<?php
				}
				?>
                <div class="submit">
					<?php submit_button(); ?>
                </div>
            </form>
        </div>
		<?php
	}

	public function add_loyalty_plugin_settings_link( $links ) {
		$settings_link = '<a style="font-weight: 700;" href="' . admin_url('admin.php?page=loyalty-settings') .'">' . __('Settings') . '</a>';
		array_unshift($links, $settings_link);

		return $links;
	}
}

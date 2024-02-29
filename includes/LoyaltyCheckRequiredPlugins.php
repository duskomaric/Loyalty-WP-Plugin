<?php

namespace Loyalty\includes;

class LoyaltyCheckRequiredPlugins {

	public function __construct()
	{
		add_action( 'admin_init', array($this, 'check_required_plugins'));
	}

	public function check_required_plugins(): void
	{
		if (get_admin_page_parent() === 'plugins.php' || get_admin_page_parent() === 'loyalty-settings') {
			$this->add_notice(
				'woocommerce/woocommerce.php',
				'WooCommerce',
				'https://woocommerce.com/',
				'WooCommerce'
			);

			$this->add_notice(
				'woocommerce-points-and-rewards/woocommerce-points-and-rewards.php',
				'WooCommerce Points and Rewards',
				'https://woo.com/products/woocommerce-points-and-rewards/',
				'WooCommerce Points and Rewards'
			);
		}
	}

	private function add_notice($plugin_file, $plugin_name, $download_link, $download_name): void
	{
		if (!is_plugin_active($plugin_file)) {
			add_action('admin_notices', function () use ($plugin_name, $download_link, $download_name) {
				?>
				<div class="notice notice-error" style="border-color: #dc3232; color: #dc3232; display: flex; align-items: center;">
                    <span style="margin-right: 10px;" class="dashicons dashicons-warning"></span>
					<div>
                        <p style="margin-bottom: 0;">The <strong>Loyalty Plugin</strong> requires <em><?= $plugin_name; ?></em> to be installed and active.</p>
                        <p style="margin-top: 0;"><small>You can download <a href="<?= $download_link; ?>" target="_blank"><?= $download_name; ?></a> here.</small></p>
                    </div>
				</div>
				<?php
			});
		}
	}
}

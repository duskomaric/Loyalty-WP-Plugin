<?php

/**
 * Plugin Name: Loyalty Plugin
 * Description: Sync points with API
 * Version: 1.2.0
 */

error_reporting(0);
ini_set('display_errors', 0);

class Loyalty_Plugin
{
    private string $username;
    private string $password;
    private string $loyalty_base_api_path;

    public function __construct()
    {
        $this->username = get_option('loyalty_username');
        $this->password = get_option('loyalty_password');
        $this->loyalty_base_api_path = get_option('loyalty_base_api_path');
        add_action('woocommerce_edit_account_form', array($this, 'add_card_number_field_to_account'));

        add_action('woocommerce_register_form', array($this, 'registerForm'));
        add_action('woocommerce_created_customer', array($this, 'createUser'));

        add_action('increase_points_custom_hook', array($this, 'points_increased'), 10, 6);
        add_action('reduce_points_custom_hook', array($this, 'points_reduced'), 10, 6);

        add_action('rest_api_init', function () {
            register_rest_route('loyalty/v1', '/cardNumber', array(
                'methods' => 'POST',
                'callback' => array($this, 'loyalty_plugin_update_user_meta')
            ));
        });
    }


    public function registerForm(): void
    {
        echo '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="card_number">Loyalty Kartica<span
                        class="required">*</span></label>
            <input type="number" class="woocommerce-Input woocommerce-Input--text input-text" name="card_number"
                   id="card_number" value="">
        </p>';
    }

    public function loyalty_plugin_update_user_meta(WP_REST_Request $request): WP_REST_Response
    {
        $user_id = $request->get_param('user_id');
        $card_number = $request->get_param('card_number');

        if (empty($user_id) || empty($card_number)) {
            return new WP_REST_Response(array('error' => 'Invalid parameters'), 400);
        }

        update_user_meta($user_id, 'card_number', $card_number);

        return new WP_REST_Response(array('success' => true), 200);
    }

    public function createUser($user_id): void
    {
        $userCreated = $this->createUserApiCall($user_id);
        error_log('$userCreated' . $userCreated);

        if ($userCreated) {
            $userFromApi = $this->checkUserByIdApiCall($user_id);

            update_user_meta($user_id, 'card_number', $userFromApi['cardNumber']);

            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_points_rewards_user_points';

            $query = $wpdb->prepare(
                "INSERT INTO $table_name (points_balance, points, user_id, date) VALUES (%d, %d, %d, %s)",
                $userFromApi['totalPoints'],
                $userFromApi['totalPoints'],
                $user_id,
                date_default_timezone_get()
            );
            $wpdb->query($query);
        }
    }

    public function points_increased($user_id, $pointsToIncrease): void
    {
        error_log('user id: ' . $user_id . ', pointsToIncrease ' . $pointsToIncrease);
        $card_number = get_user_meta($user_id, 'card_number', true);

        $enterPointsSuccessful = $this->enterPointsApiCall($card_number, $pointsToIncrease, 0);

//        if ($enterPointsSuccessful) {
//            $userFromApi = $this->checkUserByIdApiCall($user_id);
//            error_log('$userFromApi' . print_r($userFromApi, true));
//
//            global $wpdb;
//            $table_name = $wpdb->prefix . 'wc_points_rewards_user_points';
//            $query = $wpdb->prepare(
//                "INSERT INTO $table_name (points_balance, points, user_id, date) VALUES (%d, %d, %d, %s)",
//                $pointsToIncrease,
//                $pointsToIncrease,
//                $user_id,
//                date_default_timezone_get()
//            );
//            $wpdb->query($query);
//        }
    }

    public function points_reduced($user_id, $pointsToReduce): void
    {
        error_log('user id: ' . $user_id . ', pointsToReduce ' . $pointsToReduce);
        $card_number = get_user_meta($user_id, 'card_number', true);

        $enterPointsSuccessful = $this->enterPointsApiCall($card_number, 0, $pointsToReduce);

//        if ($enterPointsSuccessful) {
//            $userFromApi = $this->checkUserByIdApiCall($user_id);
//            global $wpdb;
//            $table_name = $wpdb->prefix . 'wc_points_rewards_user_points';
//            $query = $wpdb->prepare(
//                "INSERT INTO $table_name (points_balance, points, user_id, date) VALUES (%d, %d, %d, %s)",
//                $userFromApi['totalPoints'],
//                $userFromApi['totalPoints'],
//                $user_id,
//                date_default_timezone_get()
//            );
//            $wpdb->query($query);
//        }
    }

    public function add_card_number_field_to_account(): void
    {
        $user_id = get_current_user_id();
        $card_number = get_user_meta($user_id, 'card_number', true);
        ?>

        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="card_number"><?php esc_html_e('Loyalty Kartica', 'text-domain'); ?> <span
                        class="required">*</span></label>
            <input type="number" class="woocommerce-Input woocommerce-Input--text input-text" name="card_number"
                   id="card_number" value="<?php echo esc_attr($card_number); ?>"/>
        </p>

        <?php
    }

    public function save_settings(): void
    {
        if (
            isset($_POST['username'])
            || isset($_POST['password'])
            || isset($_POST['loyalty_base_api_path'])
        ) {
            $this->username = sanitize_text_field($_POST['username']);
            $this->password = sanitize_text_field($_POST['password']);
            $this->loyalty_base_api_path = sanitize_text_field($_POST['loyalty_base_api_path']);

            $fields = [
                'username' => 'Username',
                'password' => 'Password',
                'loyalty_base_api_path' => 'API Endpoint URL'
            ];

            $validation_errors = []; // Initialize the validation errors array

            foreach ($fields as $field => $label) {
                if (empty($_POST[$field])) {
                    $validation_errors[] = $label . ' is required.';
                }
            }

            if (empty($validation_errors)) {
                update_option('loyalty_username', $this->username);
                update_option('loyalty_password', $this->password);
                update_option('loyalty_base_api_path', $this->loyalty_base_api_path);

                echo '<div class="notice-to-remove notice notice-success"><p>Settings saved!</p></div>';
            } else {
                foreach ($validation_errors as $error) {
                    echo '<div class="notice-to-remove notice notice-error"><p>' . $error . '</p></div>';
                }
            }
        }
    }

    public function loyalty_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=product', // Parent menu slug (WooCommerce "Products" menu)
            'Loyalty', // Page title
            'Loyalty', // Menu title
            'manage_options', // Capability required to access the menu item
            'loyalty', // Menu slug
            array($this, 'loyalty_page') // Callback function to render the page
        );
    }

    public function loyalty_page(): void
    {
            $this->display_form_and_button();
    }

    public function display_form_and_button(): void
    {
        ?>
        <div class="wrap">
            <h1 style="margin-bottom: 20px;">Loyalty Plugin</h1>
            <form method="post" action="">
                <div style="margin-bottom: 15px;">
                    <label for="username" style="display: block; margin-bottom: 5px;">Username:</label>
                    <input type="text" name="username" value="<?php echo esc_attr($this->username); ?>" style="width: 400px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="password" style="display: block; margin-bottom: 5px;">Password:</label>
                    <input type="password" name="password" value="<?php echo esc_attr($this->password); ?>"
                           style="width: 400px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label for="loyalty_base_api_path" style="display: block; margin-bottom: 5px;">API Endpoint: <small>(without
                            /
                            at the end)</small></label>

                    <input type="text" name="loyalty_base_api_path" value="<?php echo esc_attr($this->loyalty_base_api_path); ?>"
                           style="width: 400px;">
                </div>

                <?php
                if (!empty($this->loyalty_base_api_path)) {
                    ?>
                    <div style="margin-bottom: 15px;">
                        <p style="margin-bottom: 0;">API Endpoints in use:</p>
                        <small><?php echo $this->loyalty_base_api_path . '/korisnik/' ?></small><br>
                        <small><?php echo $this->loyalty_base_api_path . '/unesiBodove/' ?></small>
                        <small><?php echo $this->loyalty_base_api_path . '/kreirajKorisnika/' ?></small>
                    </div>
                    <?php
                }
                ?>

                <div style="margin-bottom: 15px; margin-top: 30px;">
                    <button type="submit" name="save_settings"
                            style="cursor: pointer; padding: 8px 16px; font-size: 14px;">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    private function createUserApiCall(int $user_id): bool
    {
        define("KREIRAJ_KORISNIKA", $this->loyalty_base_api_path . '/kreirajKorisnika/');

        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');

        $retry_count = 0;
        $max_retry_attempts = 5;
        $response_code = null;

        $user_data = get_userdata($user_id);

        while ($response_code !== 200 && $retry_count < $max_retry_attempts) {

            $response = wp_remote_get(
                KREIRAJ_KORISNIKA .
                '?ime=' . $user_data->first_name .
                '&prezime=' . $user_data->last_name .
                '&email=' . $user_data->user_email .
                '&idkorisnikweb=' . $user_data->ID .
                '&brojkarticestarikor=' . $_POST['card_number'] .
                '&username=' . $this->username .
                '&password=' . base64_encode($this->password),
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json'
                    ),
                    'timeout' => 30
                ));

            $response_code = wp_remote_retrieve_response_code($response);
            $retry_count++;

            if ($response_code !== 200 && $retry_count < $max_retry_attempts) {
                sleep(3);
            }
        }

        if ($response_code === 200) {
            return true;
        } else {
            return false;
        }
    }

    private function checkUserByIdApiCall(int $user_id): array
    {
        define("KORISNIK", $this->loyalty_base_api_path . '/korisnik/');
        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');
        $user_response = wp_remote_get(
            KORISNIK .
            '?webid=' . $user_id .
            '&username=' . $this->username .
            '&password=' . base64_encode($this->password),
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));

        $response = json_decode(wp_remote_retrieve_body($user_response))[0];


        return ['cardNumber' => $response->BrojKartice, 'totalPoints' => $response->UkupnoBodova];
    }

    private function enterPointsApiCall(int $card_number, int $pointsToIncrease, int $pointsToReduce)
    {
        define("UNESI_BODOVE", $this->loyalty_base_api_path . '/unesiBodove/');

        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');


        wp_remote_get(
            UNESI_BODOVE .
            '?brojkartice=' . $card_number .
            '&skupljenibodovi=' . $pointsToIncrease .
            '&iskoristenibodovi=' . $pointsToReduce .
            '&username=' . $this->username .
            '&password=' . base64_encode($this->password),
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));

        error_log(
            UNESI_BODOVE .
            '?brojkartice=' . $card_number .
            '&skupljenibodovi=' . $pointsToIncrease .
            '&iskoristenibodovi=' . $pointsToReduce .
            '&username=' . $this->username .
            '&password=' . base64_encode($this->password)
        );

        return true;
    }
}

$loyalty_plugin = new Loyalty_Plugin();
add_action('admin_menu', array($loyalty_plugin, 'loyalty_menu'));
add_action('admin_init', array($loyalty_plugin, 'save_settings'));

// quick fix
// add this line: do_action('reduce_points_custom_hook', $user_id, $points);
// to woocommerce-points-and-rewards/includes/class-wc-points-rewards-manager.php in the beginning of function decrease_points();
// disable plugin update
<?php

namespace Loyalty\includes;

class LoyaltyApi
{
	public static string $username;
	public static string $password;
	public static string $token;
	public static string $loyalty_base_api_path;

	public function __construct()
	{
		self::$username = get_option('loyalty_username');
		self::$password = get_option('loyalty_password');
		self::$token = get_option('loyalty_token');
		self::$loyalty_base_api_path = get_option('loyalty_base_api_path');
	}

    public static function createUser(int $user_id): void
    {
        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');

        $user = get_userdata($user_id);

		wp_remote_get(
            self::$loyalty_base_api_path .
            '?method=kreirajKorisnika'.
            '&ime=' . $user->first_name .
            '&prezime=' . $user->last_name .
            '&email=' . $user->user_email .
            '&idkorisnikweb=' . $user->ID .
            '&brojkarticestarikor=' . $_POST['card_number'] .
            '&username=' . self::$username .
            '&password=' . self::$password.
            '&token=' . self::$token,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
        ));

		$user_response = self::checkUserById($user_id);

		if (!empty($user_response)) {
			update_user_meta($user_id, 'card_number', $user_response['cardNumber']);
		}
    }

    public static function checkUserById($user_id): array
    {
        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');

        $user_response = wp_remote_get(
	        self::$loyalty_base_api_path .
	        '?method=korisnik'.
            '&webid=' . $user_id .
            '&username=' . self::$username .
            '&password=' . self::$password .
            '&token=' . self::$token,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));

        $response = json_decode(wp_remote_retrieve_body($user_response));

        return ['cardNumber' => $response->brojkartice, 'totalPoints' => $response->ukupnobodova];
    }

    public static function updatePoints(int $user_id, int $pointsToIncrease, int $pointsToReduce): void
    {
        add_filter('https_ssl_verify', '__return_false');
        add_filter('https_local_ssl_verify', '__return_false');

        wp_remote_get(
	        self::$loyalty_base_api_path .
	        '?method=unesiBodove'.
	        '&username=' . self::$username .
            '&password=' . self::$password .
            '&token=' . self::$token .
	        '&brojkartice=' . get_user_meta($user_id, 'card_number', true).
	        '&skupljenibodovi='. $pointsToIncrease .
	        '&iskoristenibodovi='. $pointsToReduce,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 30
            ));
    }
}

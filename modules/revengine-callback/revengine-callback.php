<?php
class RevEngineCallback {

    private $options = [
        "revengine_callback_url_subscription_updated",
        "revengine_callback_url_user_profile_updated",
        "revengine_callback_url_user_profile_created",
        "revengine_callback_url_user_profile_deleted",
        "revengine_callback_token",
        "revengine_callback_enable",
        "revengine_callback_debug",
    ];

    public function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        $this->apikey = get_option("revengine_callback_token");
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        if (get_option("revengine_callback_enable")) {
            add_action('woocommerce_subscription_status_updated', [ $this, 'woocommerce_subscription_status_updated' ], 10, 3);
            add_action('profile_update', [ $this, 'wordpress_profile_updated' ], 10, 3);
            add_action('user_register', [ $this, 'wordpress_user_profile_created' ], 10, 1);
            add_action('delete_user', [ $this, 'wordpress_user_profile_deleted' ], 10, 1);
        }
    }

    protected function _handle_errors($response) {
        if (is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            throw new Exception("RevEngine error: " . $error_message);
        }
        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400) {
            $body = wp_remote_retrieve_body($response);
            $body = json_decode($body);
            if (isset($body->message)) {
                throw new Exception("RevEngine error: " . $body->message);
            } else {
                throw new Exception("RevEngine error: " . $body);
            }
        }
    }

    public function add_headers($data = null, $headers = array()) {
        $result = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->apikey,
                'Content-Type' => 'application/json',
            ),
        );
        if ($headers) {
            $result['headers'] = array_merge($result['headers'], $headers);
        }
        if ($data) {
            $result['body'] = wp_json_encode($data);
        }
        return $result;
    }

    protected function post($url, $data = []) {
        try {
            $args = $this->add_headers($data);
        } catch (Exception $e) {
            throw new Exception("RevEngine error: " . $e->getMessage());
        }
        if (is_callable('vip_safe_wp_remote_post')) {
            $response = vip_safe_wp_remote_post($url, $args);
        } else {
            // phpcs:ignore
            $response = wp_remote_post($url, $args);
        }
        $this->_handle_errors($response);
        return $response;
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('RevEngine Callbacks', 'revengine-callback'),
            __('RevEngine Callbacks', 'revengine-callback'),
            // an admin-level user.
            'manage_options',
            'revengine-callback-options',
            [ $this, 'admin_options_template' ]
        );
        
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-callback-options-group', $option );
        }
    }

    function admin_options_template() {
        $callback_result = null;
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('You do not have sufficient permissions to access this page.'));
        }
        if (!empty(get_option("revengine_callback_url"))) {
            $callback_result = revengine_test_callback();
        }
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-callback/templates/admin/revengine-callback-settings.php';
    }

    function woocommerce_subscription_status_updated($subscription, $old, $new) {
        try {
            $url = get_option("revengine_callback_url_subscription_updated");
            if (empty($url)) return;
            $subscription_data = $subscription->get_data();
            foreach($subscription_data["line_items"] as $line_item) {
                $line_item_data = $line_item->get_data();
                $subscription_data["products"][] = array(
                    "name" => $line_item_data["name"],
                    "quantity" => $line_item_data["quantity"],
                    "total" => $line_item_data["total"],
                );
            }
            $result = [
                "action" => "woocommerce_subscription_status_updated",
                "subscription" => $subscription_data,
                "old" => $old,
                "new" => $new,
            ];
            $this->post($url, $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_profile_updated($user_id, $old, $new) {
        try {
            $url = get_option("revengine_callback_url_user_profile_updated");
            if (empty($url)) return;
            $user = get_userdata($user_id);
            $result = [
                "action" => "wordpress_profile_updated",
                "user" => $user,
                "old" => $old->data,
                "new" => $new,
            ];
            $this->post($url, $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_user_profile_created($user_id) {
        try {
            $url = get_option("revengine_callback_url_user_profile_created");
            if (empty($url)) return;
            $user = get_userdata($user_id);
            $result = [
                "action" => "wordpress_user_profile_created",
                "user" => $user,
            ];
            $this->post($url, $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_user_profile_deleted($user_id) {
        try {
            $url = get_option("revengine_callback_url_user_profile_deleted");
            if (empty($url)) return;
            $user = get_userdata($user_id);
            $result = [
                "action" => "wordpress_user_profile_deleted",
                "user" => $user,
            ];
            $this->post($url, $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }
}
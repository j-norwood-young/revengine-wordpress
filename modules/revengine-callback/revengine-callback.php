<?php
class RevEngineCallback {

    private $options = [
        "revengine_callback_url",
        "revengine_callback_token",
        "revengine_callback_enable",
        "revengine_callback_debug",
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        if (get_option("revengine_callback_enable")) {
            add_action('woocommerce_subscription_status_updated', [ $this, 'woocommerce_subscription_status_updated' ], 10, 3);
            add_action('profile_update', [ $this, 'wordpress_profile_update' ], 10, 3);
            add_action('user_register', [ $this, 'wordpress_user_register' ], 10, 1);
            add_action('delete_user', [ $this, 'wordpress_delete_user' ], 10, 1);
        }
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
                "subscription" => $subscription_data,
                "old" => $old,
                "new" => $new,
            ];
            revengine_fire_callback("/woocommerce/subscription/update", $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_profile_update($user_id, $old, $new) {
        try {
            $user = get_userdata($user_id);
            $result = [
                "user" => $user,
                "old" => $old,
                "new" => $new,
            ];
            revengine_fire_callback("/wordpress/user/update", $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_user_register($user_id) {
        try {
            $user = get_userdata($user_id);
            $result = [
                "user" => $user,
            ];
            revengine_fire_callback("/wordpress/user/create", $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }

    function wordpress_delete_user($user_id) {
        try {
            $user = get_userdata($user_id);
            $result = [
                "user" => $user,
            ];
            revengine_fire_callback("/wordpress/user/delete", $result);
        } catch (Exception $e) {
            //phpcs:ignore
            error_log($e->getMessage());
        }
    }
}
<?php

class RevEngineUserLabels {

    private $options = [
        "revengine_user_label_schedule_enabled",
    ];

    public function __construct($revengine_globals) {
        // add_action( 'init', [ $this, 'fake_inject_revengine_labels' ] );
        add_action( 'wp_login', [ $this, 'inject_revengine_labels' ], 10, 2 );
        add_action( 'edit_user_profile', [ $this, 'show_revengine_labels_on_edit_profile' ], 5, 1);
        add_action( 'show_user_profile', [ $this, 'show_revengine_labels_on_edit_profile' ], 5, 1);
        /*$revengine_user_label_schedule_enabled = get_option("revengine_user_label_schedule_enabled");
        if (!empty($revengine_user_label_schedule_enabled)) {
            if ( false === as_has_scheduled_action( 'revengine_user_label_sync' ) ) {
                as_schedule_recurring_action( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'revengine_user_label_sync', array(), 'revengine' );
            }
        } else {
            as_unschedule_all_actions( 'revengine_user_label_sync' );
        }*/
        add_action("revengine_user_label_sync", [ $this, 'revengine_user_label_sync' ]);
        add_action("admin_post_revengine_user_label_sync", [ $this, 'queue_now' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
    }

    public function options_page() {
        add_submenu_page(
            'revengine_options',
            __('RevEngine Labels', 'revengine-labels'),
            __('RevEngine Labels', 'revengine-labels'),
            'manage_options',
            'revengine-label-options',
            [ $this, 'admin_options_template' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-user_label-options-group', $option );
        }
    }

    public function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-user_labels/templates/admin/revengine-user_labels-options.php';
    }

    public function revengine_user_label_sync() {
        $users = get_users();
        foreach($users as $user) {
            try {
                $this->fetch_labels($user->ID);
            } catch (Exception $e) {
                // phpcs:ignore
                error_log("Error fetching labels for user {$user->ID}: {$e->getMessage()}");
            }
        }
    }

    protected function fetch_labels($user_id) {
        $data = revengine_fire_callback("/reader/labels", [
            "user_id" => $user_id,
        ]);
        update_user_meta( $user_id, "revengine_labels", $data );
    }

    public function fake_inject_revengine_labels() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        $user = wp_get_current_user();
        $this->inject_revengine_labels( $user->user_login, $user );
    }

    function inject_revengine_labels($user_login, WP_User $user) {
        $data = revengine_fire_callback("/reader/labels", [
            "user_id" => $user->ID,
        ]);
        update_user_meta( $user->ID, "revengine_labels", $data );
    }

    public function show_revengine_labels_on_edit_profile(WP_User $user) {
        $html = "<h3>RevEngine</h3>";
        $this->fetch_labels($user->ID);
        $data = json_decode(get_user_meta( $user->ID, "revengine_labels", true ));
        if ( empty( $data ) ) {
            return;
        }
        if ( empty( $data->labels ) && empty( $data->segments ) ) {
            return;
        }
        $labels = $data->labels;
        $html .= "<h4>Labels</h4>";
        $html .= "<div style='display: flex; flex-wrap: wrap;'>";
        foreach ( $labels as $label ) {
            $html .= "<div style='background-color: #2271b1; border-radius: 2px; margin-right: 4px; padding: 4px; font-size: 0.9em; color: #FFF'>{$label}</div>";
        }
        $html .= "</div>";
        if ( empty( $data->segments ) ) {
            // phpcs:ignore
            echo $html;
            return;
        }
        $segments = $data->segments;
        $html .= "<h4>Segments</h4>";
        $html .= "<div style='display: flex; flex-wrap: wrap;'>";
        foreach ( $segments as $segment ) {
            $html .= "<div style='background-color: #2271b1; border-radius: 2px; margin-right: 4px; padding: 4px; font-size: 0.9em; color: #FFF'>{$segment}</div>";
        }
        $html .= "</div>";
        // phpcs:ignore
        echo $html;
    }

    public function queue_now() {
        as_enqueue_async_action( 'revengine_user_label_sync', [], 'revengine');
        add_settings_error( 'revengine-user_label-options-group', 'revengine-user_label-options-group', 'Sync queued', 'updated');
        wp_redirect( admin_url( 'admin.php?page=revengine-label-options' ) );
        exit;
    }
}

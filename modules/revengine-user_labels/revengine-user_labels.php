<?php

class RevEngineUserLabels {
    public function __construct($revengine_globals) {
        // add_action( 'init', [ $this, 'fake_inject_revengine_labels' ] );
        add_action( 'wp_login', [ $this, 'inject_revengine_labels' ], 10, 2 );
        add_action( 'edit_user_profile', [ $this, 'show_revengine_labels_on_edit_profile' ], 5, 1);
        add_action( 'show_user_profile', [ $this, 'show_revengine_labels_on_edit_profile' ], 5, 1);
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
        $labels = $data->labels;
        $html .= "<h4>Labels</h4>";
        $html .= "<div style='display: flex; flex-wrap: wrap;'>";
        foreach ( $labels as $label ) {
            $html .= "<div style='background-color: #2271b1; border-radius: 2px; margin-right: 4px; padding: 4px; font-size: 0.9em; color: #FFF'>{$label}</div>";
        }
        $html .= "</div>";
        $segments = $data->segments;
        $html .= "<h4>Segments</h4>";
        $html .= "<div style='display: flex; flex-wrap: wrap;'>";
        foreach ( $segments as $segment ) {
            $html .= "<div style='background-color: #2271b1; border-radius: 2px; margin-right: 4px; padding: 4px; font-size: 0.9em; color: #FFF'>{$segment}</div>";
        }
        $html .= "</div>";
        echo $html;
    }
}
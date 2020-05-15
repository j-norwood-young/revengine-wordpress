<?php
class RevEngineTracker {
    private $options = [
        "revengine_enable_tracking",
        "revengine_tracker_server_address"
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('wp_footer', [ $this, 'hit' ]);
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('RevEngine Tracker', 'revengine-tracker'),
            __('RevEngine Tracker', 'revengine-tracker'),
            // an admin-level user.
            'manage_options',
            'revengine-tracker-options',
            [ $this, 'admin_options_template' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-tracker-options-group', $option );
        }
    }

    function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/admin/revengine-tracker-options.php';
    }

    function hit() {
        if (is_admin()) return; // Front end only
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if ($options["revengine_enable_tracking"]) {
            $browser_token = $_COOKIE["revengine-browser-token"];
            if (empty($browser_token)) {
                $browser_token = bin2hex(openssl_random_pseudo_bytes(16));
                setcookie("revengine-browser-token", $browser_token);
            }
            $post_id = get_the_ID();
            $post = get_post($post_id);
            $data = (object) [
                "action" => "pageview",
                "ip" => $_SERVER["REMOTE_ADDR"],
                "user_agent" => $_SERVER["HTTP_USER_AGENT"],
                "referer" => $_SERVER["HTTP_REFERER"],
                "raw_uri" => $_SERVER["REQUEST_URI"],
                "uri" => $_SERVER["REDIRECT_URL"],
                "query_string" => $_SERVER["QUERY_STRING"],
                "request_time" => $_SERVER["REQUEST_TIME"],
                "post_id" => $post_id,
                "post_title" => esc_html(get_the_title()),
                "post_author" => get_the_author_meta("display_name", $post->post_author),
                "post_tags" => array_map(function($i) { return $i->name; }, get_the_terms($post_id, "article_tag")),
                "post_sections" => array_map(function($i) { return $i->name; }, get_the_terms($post_id, "section")),
                "user_id" => get_current_user_id(),
                "browser_id" => $browser_token,
            ];
            $ch = curl_init($options["revengine_tracker_server_address"]);
            $data_encoded = json_encode($data);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_encoded);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            $result = curl_exec($ch);
            $error = "";
            if(curl_error($ch)) {
                $error = curl_error($ch);
            }
            curl_close($ch);
        }
    }
}
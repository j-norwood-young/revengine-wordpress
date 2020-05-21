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
        if (is_404()) return; // Don't log 404s
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if ($options["revengine_enable_tracking"]) {
            $post = get_queried_object();
            $post_id = get_queried_object_id();
            if (!empty($post->post_type)) {
                $post_type = $post->post_type;
            } else if (!empty($post->taxonomy)) {
                $post_type = $post->taxonomy;
            } else {
                $post_type = "";
            }
            if (!empty($post->post_title)) {
                $post_title = $post->post_title;
            } else if (!empty($post->name)) {
                $post_title = $post->name;
            } else {
                $post_title = "";
            }
            // trigger_error(json_encode($post), E_USER_NOTICE);
            $browser_token = $_COOKIE["revengine-browser-token"];
            if (empty($browser_token)) {
                $browser_token = bin2hex(openssl_random_pseudo_bytes(16));
                setcookie("revengine-browser-token", $browser_token);
            }
            $data = (object) [
                "action" => "pageview",
                "ip" => $_SERVER["REMOTE_ADDR"],
                "user_agent" => $_SERVER["HTTP_USER_AGENT"],
                "referer" => $_SERVER["HTTP_REFERER"],
                "url" => $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
                "query_string" => $_SERVER["QUERY_STRING"],
                "request_time" => $_SERVER["REQUEST_TIME"],
                "post_id" => $post_id,
                "user_id" => get_current_user_id(),
                "browser_id" => $browser_token,
                "post_title" => esc_html($post_title),
                "post_type" => $post_type,
                "home_page" => is_front_page(),
            ];
            if ($post_type == "article" || $post_type == "opinion-piece") { // Empty post types are section pages, home pages etc
                $data->post_author = get_the_author_meta("display_name", $post->post_author);
                $terms = get_the_terms($post_id, "section");
                if (is_array($terms)) {
                    $data->post_sections = array_map(function($i) { return $i->name; }, $terms);
                }
                $tags = get_the_terms($post_id, "article_tag");
                if (is_array($tags)) {
                    $data->post_tags = array_map(function($i) { return $i->name; }, $tags);
                }
            }
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
                trigger_error($error, E_USER_WARNING);
            }
            curl_close($ch);
        }
    }
}
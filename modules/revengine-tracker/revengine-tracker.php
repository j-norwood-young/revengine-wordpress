<?php
class RevEngineTracker {
    private $options = [
        "revengine_enable_tracking",
        "revengine_tracker_server_address",
        "revengine_tracker_server_port",
        "revengine_tracker_ssl",
        "revengine_tracker_debug",
        "revengine_tracker_timeout",
        "revengine_tracker_iframe",
        "revengine_tracker_amp"
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('wp_footer', [ $this, 'iframe' ]);
        add_action('amp_post_template_footer', [ $this, 'amp' ]);
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

    protected function _get_post_data() {
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
        $data = (object) [
            "action" => "pageview",
            "post_id" => $post_id,
            "user_id" => get_current_user_id(),
            "post_title" => esc_html($post_title),
            "post_type" => $post_type,
            "home_page" => is_front_page(),
        ];
        if (isset($_SERVER["HTTP_REFERER"])) {
            $data->referer = sanitize_text_field($_SERVER["HTTP_REFERER"]);
        }
        return $data;
    }

    function amp() {
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if (!($options["revengine_tracker_amp"])) return;
        $debug = false;
        if ($options["revengine_tracker_debug"]) {
            $debug = true;
        }
        if ($options["revengine_enable_tracking"]) {
            $revengine_server = $options["revengine_tracker_server_address"];
            if ($options["revengine_tracker_ssl"]) {
                $revengine_server = "https://" . $revengine_server;
                if ($options["revengine_tracker_server_port"] !== 443 && !empty($options["revengine_tracker_server_port"])) {
                    $revengine_server = $revengine_server . ":" . $options["revengine_tracker_server_port"];
                }
            } else {
                $revengine_server = "http://" . $revengine_server;
                if ($options["revengine_tracker_server_port"] !== 80 && !empty($options["revengine_tracker_server_port"])) {
                    $revengine_server = $revengine_server . ":" . $options["revengine_tracker_server_port"];
                }
            }
            $data = $this->_get_post_data();
            $revengine_server = $revengine_server . "?" . http_build_query($data);
            require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/amp.php';
        }
    }

    function iframe() {
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if (!($options["revengine_tracker_iframe"])) return;
        $debug = false;
        if ($options["revengine_tracker_debug"]) {
            $debug = true;
        }
        if ($options["revengine_enable_tracking"]) {
            $revengine_server = $options["revengine_tracker_server_address"];
            if ($options["revengine_tracker_ssl"]) {
                $revengine_server = "https://" . $revengine_server;
            } else {
                $revengine_server = "http://" . $revengine_server;
                if ($options["revengine_tracker_server_port"] !== 80 && !empty($options["revengine_tracker_server_port"])) {
                    $revengine_server = $revengine_server . ":" . $options["revengine_tracker_server_port"];
                }
            }
            $data = $this->_get_post_data();
            $revengine_server = $revengine_server . "?" . http_build_query($data);
            require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/iframe.php';
        }
    }
}
<?php
class RevEngineTracker {
    private $options = [
        "revengine_enable_tracking",
        "revengine_tracker_url",
        "revengine_tracker_method", // iframe, javascript, amp, img
        "revengine_tracker_server_address", // Deprecated
        "revengine_tracker_server_port", // Deprecated
        "revengine_tracker_ssl",
        "revengine_tracker_debug",
        "revengine_tracker_timeout",
        "revengine_tracker_iframe", // Deprecated
        "revengine_tracker_amp" // Deprecated
    ];

    function __construct() {
        add_action('init', [ $this, 'check_upgrade' ]);
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        if (!get_option("revengine_enable_tracking")) return; // Don't load if tracking is disabled
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        $method = get_option("revengine_tracker_method", "img");
        switch ($method) {
            case "iframe":
                add_action('wp_footer', [ $this, 'iframe' ]);
                break;
            case "amp":
                add_action('amp_post_template_footer', [ $this, 'amp' ]);
                break;
            case "img":
                add_action('wp_footer', [ $this, 'img' ]);
                break;
            case "javascript":
                add_action('wp_footer', [ $this, 'javascript' ]);
                break;
            default:
                add_action('wp_footer', [ $this, 'img' ]);
                break;
        }
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

    public function check_upgrade() {
        $current_version = get_option("revengine_tracker_version");
        if ($current_version !== REVENGINE_WORDPRESS_VERSION) {
            $this->upgrade($current_version);
            $this->register_settings();
            update_option("revengine_tracker_version", REVENGINE_WORDPRESS_VERSION);
        }
    }

    protected function upgrade() {
        if (empty(get_option("revengine_tracker_url")) && !empty(get_option("revengine_tracker_server_address"))) {
            $revengine_server = get_option("revengine_tracker_server_address");
            if (get_option("revengine_tracker_ssl")) {
                $revengine_server = "https://" . $revengine_server;
            } else {
                $revengine_server = "http://" . $revengine_server;
                if (get_option("revengine_tracker_server_port") !== 80 && !empty(get_option("revengine_tracker_server_port"))) {
                    $revengine_server = $revengine_server . ":" . get_option("revengine_tracker_server_port");
                }
            }
            update_option("revengine_tracker_url", $revengine_server);
        }
        if (empty(get_option("revengine_tracker_method"))) {
            if (get_option("revengine_tracker_iframe")) {
                update_option("revengine_tracker_method", "iframe");
            } else if (get_option("revengine_tracker_amp")) {
                update_option("revengine_tracker_method", "amp");
            } else {
                update_option("revengine_tracker_method", "javascript");
            }
        }
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
        $url = get_option("revengine_tracker_url") . "?" . http_build_query($this->_get_post_data());
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/amp.php';
        
    }

    function iframe() {
        $url = get_option("revengine_tracker_url") . "?" . http_build_query($this->_get_post_data());
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/iframe.php';
    }

    function img() {
        $url = get_option("revengine_tracker_url") . "?" . http_build_query($this->_get_post_data());
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/img.php';
    }

    function javascript() {
        $url = get_option("revengine_tracker_url");
        $data = $this->_get_post_data();
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/javascript.php';
    }

}
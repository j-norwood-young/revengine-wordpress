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
        $this->content_data = new RevEngineContentData();
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('wp_footer', [ $this, 'hit' ]);
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
            $data = $this->content_data->get_post_data();
            $revengine_server = $revengine_server . "?" . http_build_query($data);
            require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/amp.php';
        }
    }

    function iframe() {
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        $options = [];
        // print_r($this->options);
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
                // if ($options["revengine_tracker_server_port"] !== 443 && !empty($options["revengine_tracker_server_port"])) {
                //     $revengine_server = $revengine_server . ":" . $options["revengine_tracker_server_port"];
                // }
            } else {
                $revengine_server = "http://" . $revengine_server;
                if ($options["revengine_tracker_server_port"] !== 80 && !empty($options["revengine_tracker_server_port"])) {
                    $revengine_server = $revengine_server . ":" . $options["revengine_tracker_server_port"];
                }
            }
            $data = $this->get_post_data();
            $revengine_server = $revengine_server . "?" . http_build_query($data);
            require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-tracker/templates/frontend/iframe.php';
        }
    }

    function hit() {
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if ($options["revengine_tracker_iframe"]) return;
        if (empty($options["revengine_tracker_timeout"])) {
            $options["revengine_tracker_timeout"] = 1; // Default 1s
        }
        $debug = false;
        if ($options["revengine_tracker_debug"]) {
            $debug = true;
        }
        if ($options["revengine_enable_tracking"]) {
            $data = $this->get_post_data();
            $data->user_agent = $_SERVER["HTTP_USER_AGENT"];
            $data->url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $data_encoded = json_encode($data);
            $server_address = $options["revengine_tracker_server_address"];
            if ($options["revengine_tracker_ssl"]) {
                $server_address = "ssl://" . $server_address;
            }
            $fp = pfsockopen($server_address, $options["revengine_tracker_server_port"], $errno, $errstr, $options["revengine_tracker_timeout"]);
            $out ="POST / HTTP/1.1\r\n";
            $out.= "Host: " . $options["revengine_tracker_server_address"] . "\r\n";
            $out.= "Content-Type: application/json\r\n";
            $out.= "Content-Length: " . strlen($data_encoded)."\r\n";
            $out.= "Connection: Close\r\n\r\n";
            $out.= $data_encoded;
            fwrite($fp, $out);
            // fflush($fp);
            // fclose($fp);
            if ($debug) {
                trigger_error($server_address . ":" . $options["revengine_tracker_server_port"], E_USER_NOTICE);
                trigger_error($out, E_USER_NOTICE);
            }
            if ($errno) {
                trigger_error($errstr, E_USER_WARNING);
            }
        }
    }
}
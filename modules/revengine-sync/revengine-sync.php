<?php
class RevEngineSync {
    const PER_PAGE = 100;

    private $options = [
        "revengine_sync_active",
        "revengine_sync_api_url",
        "revengine_sync_api_key",
        "revengine_sync_test_mode",
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('rest_api_init', [$this, 'register_api_routes' ]);
        add_action('revengine_sync_all_users', [$this, 'sync_all_users'] );
        add_action('init', [$this, 'setup_cron'] );
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('RevEngine Sync', 'revengine-sync'),
            __('RevEngine Sync', 'revengine-sync'),
            // an admin-level user.
            'manage_options',
            'revengine-sync-options',
            [ $this, 'admin_options_template' ]
        );
        add_submenu_page(
            null,
            __('Run RevEngine Sync', 'revengine-sync'),
            __('Run RevEngine Sync', 'revengine-sync'),
            // an admin-level user.
            'manage_options',
            'revengine-sync-run',
            [ $this, 'run_sync' ]
        );
        add_submenu_page(
            null,
            __('Run RevEngine Sync', 'revengine-sync'),
            __('Run RevEngine Sync', 'revengine-sync'),
            // an admin-level user.
            'manage_options',
            'revengine-sync-run-all',
            [ $this, 'sync_all_users' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-sync-options-group', $option );
        }
    }

    function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-sync/templates/admin/revengine-sync-options.php';
    }

    public function run_sync() {
        $wordpress_id_field = "wordpress_id";
        $test_mode = get_option("revengine_sync_test_mode");
        if ($test_mode) {
            $wordpress_id_field = "test_wordpress_id";
        }
        $jsvars = [];
        $jsvars["api_url"] = get_option("revengine_sync_api_url");
        $jsvars["api_key"] = get_option("revengine_sync_api_key");
        $jsvars["revengine_api_key"] = get_option("revengine_api_key");
        $jsvars["wordpress_id_field"] = $wordpress_id_field;
        $furl = plugin_dir_url( __FILE__ ) . 'js/sync.js';
        $fname = plugin_dir_path( __FILE__ ) . 'js/sync.js';
        $ver = gmdate("ymd-Gis", filemtime($fname));
        wp_enqueue_script( 'jquery-ui-progressbar');  // the progress bar
        wp_enqueue_script( "revengine-piano-sync", $furl, null, $ver, true );
        wp_localize_script( "revengine-piano-sync", "revengine_piano_sync_vars", $jsvars);
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-sync/templates/admin/revengine-sync-run.php';
    }

    function register_api_routes() {
        register_rest_route( 'revengine/v1', '/sync_users', array(
            'methods' => 'GET',
            'callback' => [$this, 'sync_users'],
            'permission_callback' => [$this, 'check_access']
        ));
        register_rest_route( 'revengine/v1', '/sync_user/(?P<revengine_id>[a-z0-9]+)', array(
            'methods' => 'GET',
            'callback' => [$this, 'sync_user'],
            'permission_callback' => [$this, 'check_access'],
        ));
    }

    function check_access(WP_REST_Request $request) {
        $headers = getallheaders();
        $authorization = "";
        foreach($headers as $key => $val) {
            if (strtolower($key) == "authorization") {
                $authorization = $val;
            }
        }
        if (empty($authorization)) {
            return false;
        }
        $api_key = get_option("revengine_api_key");
        if ($authorization == "Bearer $api_key") {
            return true;
        }
        return false;
    }

    private function _save_reader($reader) {
        $revengine_sync_api_key = get_option("revengine_sync_api_key");
        $revengine_sync_api_url = get_option("revengine_sync_api_url");
        $wordpress_id_field = "wordpress_id";
        try {
            $reader_id = $reader->_id;
            $reader_email = $reader->email;
            $reader_name = $reader->fist_name ?? $reader->display_name ?? explode("@", $reader->email)[0];
            $user_id = email_exists( $reader_email );
            $reader->reader_name = $reader_name;
            if (!$user_id) {
                if (username_exists($reader_name)) {
                    $reader_name = $reader_email;
                    $reader->reader_name = $reader_name;
                }
                $random_password = wp_generate_password( 12, false );
                $user_id = wp_create_user( $reader_name, $random_password, $reader_email );
                if (!is_wp_error($user_id)) {
                    Requests::put( "$revengine_sync_api_url/api/reader/$reader_id?apikey=$revengine_sync_api_key", [], [
                        $wordpress_id_field => $user_id
                    ]);
                    $reader->wordpress_user_id = $user_id;
                    $reader->op = "create";
                } else {
                    $reader->error = $user_id;
                    // phpcs:ignore
                    trigger_error($user_id->get_error_message(), E_USER_WARNING);
                }
            } else {
                Requests::put( "$revengine_sync_api_url/api/reader/$reader_id?apikey=$revengine_sync_api_key", [], [
                    $wordpress_id_field => $user_id
                ]);
                $reader->wordpress_user_id = $user_id;
                $reader->op = "update";
            }
        } catch(Exception $err) {
            throw $err;
        }
        return $reader;
    }

    private function _sync_user_page($page, $per_page=self::PER_PAGE) {
        $revengine_sync_api_key = get_option("revengine_sync_api_key");
        $revengine_sync_api_url = get_option("revengine_sync_api_url");
        $test_mode = get_option("revengine_sync_test_mode");
        $wordpress_id_field = "wordpress_id";
        if ($test_mode) {
            $wordpress_id_field = "test_wordpress_id";
        }
        $readers =  json_decode((Requests::get( "$revengine_sync_api_url/api/reader?page=$page&limit=$per_page&fields=email,first_name,last_name,display_name,wordpress_id&filter[$wordpress_id_field]=\$exists:false&apikey=$revengine_sync_api_key"))->body)->data;
        for($x = 0; $x < sizeof($readers); $x++) {
            $reader = $readers[$x];
            try {
                $reader = $this->_save_reader($reader);
            } catch(Exception $err) {
                $reader->error = $err->getMessage();
                // phpcs:ignore
                trigger_error($err->getMessage(), E_USER_WARNING);
            }
        }
        return $readers;
    }

    function sync_users(WP_REST_Request $request) {
        $per_page = intval($request->get_param( "per_page") ?? self::PER_PAGE);
        $page = intval($request->get_param( "page") ?? 1);
        $result = $this->_sync_user_page($page, $per_page);
        return $result;
    }

    private function _sync_user($revengine_id) {
        $revengine_sync_api_key = get_option("revengine_sync_api_key");
        $revengine_sync_api_url = get_option("revengine_sync_api_url");
        $reader =  json_decode((Requests::get( "$revengine_sync_api_url/api/reader/$revengine_id?apikey=$revengine_sync_api_key"))->body)->data;
        if (!$reader) {
            return new WP_Error( 'revengine_sync_user_not_found', __( 'RevEngine user not found', 'revengine' ), array( 'status' => 404 ) );
        }
        try {
            $reader = $this->_save_reader($reader);
        } catch(Exception $err) {
            $reader->error = $err->getMessage();
            // phpcs:ignore
            trigger_error($err->getMessage(), E_USER_WARNING);
        }
        return $reader;
    }

    function sync_user(WP_REST_Request $request) {
        $revengine_id = $request->get_param("revengine_id");
        $result = $this->_sync_user($revengine_id);
        return $result;
    }

    public function sync_all_users() {
        $revengine_sync_api_key = get_option("revengine_sync_api_key");
        $revengine_sync_api_url = get_option("revengine_sync_api_url");
        $test_mode = get_option("revengine_sync_test_mode");
        $wordpress_id_field = "wordpress_id";
        if ($test_mode) {
            $wordpress_id_field = "test_wordpress_id";
        }
        $count_result = json_decode((Requests::get( "$revengine_sync_api_url/count/reader?filter[wordpress_id]=\$exists:false&filter[$wordpress_id_field]=\$exists:false&apikey=$revengine_sync_api_key"))->body);
        if ($count_result->status === "error") {
            // phpcs:ignore
            trigger_error("RevEngine Sync All Users Error: {$count_result->message->error}", E_USER_ERROR);
        }
        // trigger_error($count_result, E_USER_NOTICE);
        $count = json_decode((Requests::get( "$revengine_sync_api_url/count/reader?filter[wordpress_id]=\$exists:false&filter[$wordpress_id_field]=\$exists:false&apikey=$revengine_sync_api_key"))->body)->count;
        $per_page = self::PER_PAGE;
        $pages = ceil($count / $per_page);
        for($page = 0; $page < $pages; $page++) {
            $this->_sync_user_page($page);
            // trigger_error("RevEngine Sync Users Page $page of $pages, per page $per_page, count $count", E_USER_NOTICE);
        }
    }

    function setup_cron() {
        $revengine_sync_active = get_option("revengine_sync_active", false);
        if ($revengine_sync_active) {
            if ( ! wp_next_scheduled( 'revengine_sync_all_users' ) ) {
                wp_schedule_event( time(), 'daily', 'revengine_sync_all_users' );
            }
        } else {
            $timestamp = wp_next_scheduled( 'revengine_sync_all_users' );
            wp_unschedule_event( $timestamp, 'revengine_sync_all_users' );
        }
    }

}
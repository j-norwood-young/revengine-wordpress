<?php
class RevEngineAPI {
    private $options = [
        "revengine_enable_api",
        "revengine_api_server_address",
        "revengine_api_server_port",
        "revengine_api_ssl",
        "revengine_api_debug",
        "revengine_api_timeout",
        "revengine_api_types"
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        $revengine_enable_api = get_option("revengine_enable_api");
        if (!empty($revengine_enable_api)) {
            add_action('rest_api_init', [$this, 'register_api_routes' ]);
        }
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('RevEngine API', 'revengine-api'),
            __('RevEngine API', 'revengine-api'),
            // an admin-level user.
            'manage_options',
            'revengine-api-options',
            [ $this, 'admin_options_template' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-api-options-group', $option );
        }
    }

    function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'revengine-api/templates/admin/revengine-api-options.php';
    }

    function register_api_routes() {
        register_rest_route( 'revengine/v1', '/posts', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_posts'],
            'permission_callback' => [$this, 'check_access']
        ) );
        register_rest_route( 'revengine/v1', '/users', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_users'],
            'permission_callback' => [$this, 'check_access']
        ) );
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

    function get_posts(WP_REST_Request $request) {
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $args = ([
            'post_type'   => 'article',
            'post_status' => 'publish',
            'perm'        => 'readable',
            'posts_per_page' => $per_page,
            'offset'      => ($page - 1) * $per_page,
            'order'       => 'ASC',
            'orderby'     => "modified",
            "ignore_sticky_posts" => true,
            'no_found_rows' => false
        ]);
        if (!empty($request->get_param( "modified_after"))) {
            $args["date_query"] = array(
                array(
                    'column'     => 'post_modified_gmt',
                    'after'      => $request->get_param( "modified_after"),
                ),
            );
        }
        $wp_query = new WP_Query($args);
        $posts = $wp_query->posts;
        $count = intval($wp_query->found_posts);
        $page_count = ceil(intval($count) / $per_page);
        if ( empty( $posts ) ) {
            return null;
        }
        $result = [];
        foreach ($posts as $key => $post) {
            $post->author = get_author_name($post->post_author);
            $result[] = [
                "author" => $post->author,
                "date_published" => $post->post_date,
                "date_updated" => $post->post_modified,
                "api" => strip_tags($post->post_api),
                "title" => $post->post_title,
                "excerpt" => $post->post_excerpt,
                "urlid" => $post->post_name,
                "type" => $post->post_type
            ];
        }
        $next_url = add_query_arg( ["page" => $page + 1, "per_page" => $per_page], home_url($wp->request) );
        $prev_url = add_query_arg( ["page" => $page - 1, "per_page" => $per_page], home_url($wp->request) );
        $data = [
            "page" => $page,
            "per_page" => $per_page,
            "page_count" => $page_count,
            "total_count" => $count,
        ];
        if ($page > 1) {
            $data["prev"] = $prev_url;
        }
        if ($page < $page_count) {
            $data["next"] = $next_url;
        }
        $data["data"] = $result;
        return $data;
    }

    function get_users(WP_REST_Request $request) {
        function isSerialized($str) {
            return ($str == serialize(false) || @unserialize($str) !== false);
        }
        global $wpdb;
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $result = [];
        $sql = "SELECT COUNT(*) AS count FROM wp_users";
        $count = intval($wpdb->get_results($sql)[0]->count);
        $page_count = ceil(intval($count) / $per_page);
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT * FROM wp_users LIMIT $per_page OFFSET $offset";
        $users = $wpdb->get_results($sql);
        foreach($users as $user) {
            $sql = "SELECT * FROM wp_usermeta WHERE user_id={$user->ID}";
            $usermeta = $wpdb->get_results($sql);
            foreach($usermeta as $meta) {
                $val = isSerialized($meta->meta_value) ? unserialize($meta->meta_value) : $meta->meta_value;
                $user->{$meta->meta_key} = $val;
            }
            $result[] = $user;
        }
        $next_url = add_query_arg( ["page" => $page + 1, "per_page" => $per_page], home_url($wp->request) );
        $prev_url = add_query_arg( ["page" => $page - 1, "per_page" => $per_page], home_url($wp->request) );
        $data = [
            "page" => $page,
            "per_page" => $per_page,
            "page_count" => $page_count,
            "total_count" => $count,
        ];
        if ($page > 1) {
            $data["prev"] = $prev_url;
        }
        if ($page < $page_count) {
            $data["next"] = $next_url;
        }
        $data["data"] = $result;
        return $data;
    }
}
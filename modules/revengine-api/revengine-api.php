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

    private $order_filtered_fields = [
        "id",
        "date_created",
        "date_modified",
        "date_completed",
        "date_paid",
        "total",
        "customer_id",
        "order_key",
        "user",
        "payment_method",
        "customer_ip_address",
        "customer_user_agent",
        "products",
    ];

    private $subscription_filtered_fields = [
        "id",
        "status",
        "date_created",
        "date_modified",
        "date_completed",
        "schedule_start",
        "scedule_cancelled",
        "schedule_next_payment",
        "schedule_payment_retry",
        "date_paid",
        "total",
        "customer_id",
        "order_key",
        "payment_method",
        "customer_ip_address",
        "customer_user_agent",
        "created_via",
        "customer_note",
        "billing_period",
        "billing_interval",
        "suspension_count",
        "requires_manual_renewal",
        "cancelled_email_sent",
        "products",
        "meta_data"
    ];

    private $product_filtered_fields = [
        "id",
        "name",
        "slug",
        "date_created",
        "date_modified",
    ];

    private $membership_filtered_fields = [
        "id",
        "customer_id",
        "status",
        "start_date",
        "end_date",
        "cancelled_date",
        "paused_date",
        "paused_intervals",
        "date_created",
        "date_modified",
        "order",
        "product"
    ];

    private $user_filtered_fields = [
        "id",
        "billing_phone",
        "current_login",
        "description",
        "display_name",
        "dm-ad-free-interacted",
        "dm-ad-free-toggle",
        "dm-status-user",
        "facebook",
        "first_name",
        "followAuthor",
        "followedAuthors",
        "followAuthorNotice",
        "gender",
        "googleplus",
        "last_login",
        "last_name",
        "last_update",
        "nickname",
        "paying_customer",
        "rs_saved_for_later",
        "saveForLaterNotice",
        "session_tokens",
        "twitter",
        "user_dob",
        "user_email",
        "user_facebook",
        "user_industry",
        "user_linkedin",
        "user_login",
        "user_nicename",
        "user_pass",
        "user_registered",
        "user_status",
        "user_twitter",
        "user_url",
        "wc_last_active",
        "wp_capabilities",
        "wp_user_level",
        "wsl_current_provider",
        "wsl_current_user_image",
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
        register_rest_route( 'revengine/v1', '/articles', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_articles'],
            'permission_callback' => [$this, 'check_access']
        ));
        register_rest_route( 'revengine/v1', '/users', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_users'],
            'permission_callback' => [$this, 'check_access']
        ));
        register_rest_route( 'revengine/v1', '/woocommerce_orders', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_woocommerce_orders'],
            'permission_callback' => [$this, 'check_access']
        ));
        register_rest_route( 'revengine/v1', '/woocommerce_subscriptions', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_woocommerce_subscriptions'],
            'permission_callback' => [$this, 'check_access']
        ));
        register_rest_route( 'revengine/v1', '/woocommerce_memberships', array(
            'methods' => 'GET',
            'callback' => [$this, 'get_woocommerce_memberships'],
            'permission_callback' => [$this, 'check_access']
        ));
    }

    function filter_fields($data, $filtered_fields) {
        $result = [];
        foreach($data as $key => $val) {
            if (in_array($key, $filtered_fields)) {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    function isSerialized($str) {
        return ($str == serialize(false) || @unserialize($str) !== false);
    }

    function normalise_fields($data) {
        $result = [];
        $start_unix_time = strtotime("1900-01-01");
        $end_unix_time = strtotime("2100-01-01");
        $data = (array) $data;
        foreach($data as $key => $val) {
            $lowercase_key = strtolower($key);
            if ((gettype($val) === "object") && (get_class($val) === "WC_DateTime")) {
                $result[$lowercase_key] = $val->date("c");
            } else {
                $result[$lowercase_key] = $val;
            }
            if (strpos($key, "date") !== false) {
                $result[$key] = $this->convert_to_date($val);
            }
        }
        return $result;
    }

    function convert_to_date($d) {
        if ($d == "0000-00-00 00:00:00") return "";
        if ($d == "") return "";
        if (is_numeric($d)) {
            return date("c", $d);
        }
        return date("c", strtotime($d));
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

    function get_articles(WP_REST_Request $request) {
        global $wp;
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
            $posts = [];
        }
        $result = [];
        foreach ($posts as $key => $post) {
            $post->author = get_author_name($post->post_author);
            $tags = get_the_terms($post->ID, "article_tag");
            if (is_array($tags)) {
                $post->tags = array_map(function($i) { return $i->name; }, $tags);
            } else {
                $post->tags = [];
            }
            $terms = get_the_terms($post->ID, "section");
            if (is_array($terms)) {
                $post->sections = array_map(function($i) { return $i->name; }, $terms);
            } else {
                $post->sections = [];
            }
            $result[] = [
                "author" => $post->author,
                "date_published" => $post->post_date,
                "date_modified" => $post->post_modified,
                "title" => $post->post_title,
                "excerpt" => $post->post_excerpt,
                "urlid" => $post->post_name,
                "type" => $post->post_type,
                "tags" => $post->tags,
                "sections" => $post->sections
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
        global $wpdb;
        global $wp;
        $date_fields = [
            "last_update",
            "last_login",
            "current_login",
            "wc_last_active",
            "user_registered"
        ];
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $result = [];
        $sql = "SELECT COUNT(*) AS count FROM wp_users";
        $count = intval($wpdb->get_results($sql)[0]->count);
        $page_count = ceil(intval($count) / $per_page);
        $offset = ($page - 1) * $per_page;
        $sql = "SELECT * FROM wp_users LIMIT $per_page OFFSET $offset";
        $users = (array) $wpdb->get_results($sql);
        foreach($users as $user) {
            $sql = "SELECT * FROM wp_usermeta WHERE user_id={$user->ID}";
            $user_meta = $wpdb->get_results($sql);
            foreach($user_meta as $meta) {
                $val = $this->isSerialized($meta->meta_value) ? 
                    unserialize($meta->meta_value) : 
                    $meta->meta_value;
                $user->{$meta->meta_key} = $val;
            }
            // print_r($user);
            $user = $this->normalise_fields($user);
            $user = $this->filter_fields($user, $this->user_filtered_fields);
            foreach($date_fields as $date_field) {
                if ($user[$date_field]) {
                    $user[$date_field] = $this->convert_to_date($user[$date_field]);
                }
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

    function get_woocommerce_orders(WP_REST_Request $request) {
        global $wp;
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $args = ([
            'post_type'   => 'shop_order',
            'posts_per_page' => $per_page,
            'offset'      => ($page - 1) * $per_page,
            'order'       => 'ASC',
            'orderby'     => "modified",
            'no_found_rows' => false,
            "post_status" => array("any"),
            'fields'         => 'ids',
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
            $posts = [];
        }
        $result = [];
        foreach ($posts as $post) {
            try {
                $order = wc_get_order( $post );
                $order_data = $order->get_data();
                $items = $order->get_items();
                if ($items) {
                    foreach ($items  as $item ) {
                        $product = $item->get_product();
                        if ($product) {
                            $order_data["products"][] = array(
                                "name" => $product->get_name(),
                                "quantity" => $item->get_quantity(),
                                "total" => $item->get_total(),
                            );
                        }
                    }
                }
                $order_data = $this->filter_fields($order_data, $this->order_filtered_fields);
                $order_data = $this->normalise_fields($order_data);
                $result[] = $order_data;
            } catch(Exception $err) {
                trigger_error($err, E_USER_WARNING);
            }
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

    function get_woocommerce_subscriptions(WP_REST_Request $request) {
        global $wp;
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $args = ([
            'post_type'   => 'shop_subscription',
            'posts_per_page' => $per_page,
            'offset'      => ($page - 1) * $per_page,
            'order'       => 'ASC',
            'orderby'     => "modified",
            'no_found_rows' => false,
            "post_status" => array("any"),
            'fields'         => 'ids',
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
            $posts = [];
        }
        $result = [];
        foreach ($posts as $post) {
            $subscription = wcs_get_subscription( $post );
            $subscription_data = $subscription->get_data();
            if ($subscription_data["parent_id"]) {
                $order = wc_get_order($subscription_data["parent_id"]);
                if ($order) {
                    $items = $order->get_items();
                    foreach ($items  as $item ) {
                        $product = $item->get_product();
                        if ($product) {
                            $subscription_data["products"][] = array(
                                "name" => $product->get_name(),
                                "quantity" => $item->get_quantity(),
                                "total" => $item->get_total(),
                            );
                        }
                    }
                }
            }
            $filtered_subscription_data = [];
            foreach($subscription_data as $key => $val) {
                if (in_array($key, $this->subscription_filtered_fields)) {
                    $filtered_subscription_data[$key] = $val;
                }
            }
            foreach($filtered_subscription_data as $key => $val) {
                if ((gettype($val) === "object") && (get_class($val) === "WC_DateTime")) {
                    $filtered_subscription_data[$key] = $val->date("c");
                }
            }
            $result[] = $filtered_subscription_data;
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

    function get_woocommerce_memberships(WP_REST_Request $request) {
        global $wp;
        global $wpdb;
        $per_page = intval($request->get_param( "per_page") ?? 10);
        $page = intval($request->get_param( "page") ?? 1);
        $args = ([
            'post_type'   => 'wc_user_membership',
            'posts_per_page' => $per_page,
            'offset'      => ($page - 1) * $per_page,
            'order'       => 'ASC',
            'orderby'     => "modified",
            'no_found_rows' => false,
            "post_status" => array("any")
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
            $posts = [];
        }
        $result = [];
        foreach ($posts as $post) {
            try {
                $sql = "SELECT * FROM wp_postmeta WHERE post_id={$post->ID}";
                $post_meta = $wpdb->get_results($sql);
                foreach($post_meta as $meta) {
                    $val = $this->isSerialized($meta->meta_value) ? 
                        unserialize($meta->meta_value) : 
                        $meta->meta_value;
                    $post->{$meta->meta_key} = $val;
                }
                $post->customer_id = $post->post_author;
                $post->status = str_replace("wcm-", "", $post->post_status);
                $post->start_date = $post->_start_date;
                $post->end_date = $post->_end_date;
                $post->cancelled_date = $post->_cancelled_date;
                $post->paused_intervals = [];
                if (gettype($post->_paused_intervals) === "array") {
                    foreach ($post->_paused_intervals as $start => $end) {
                        $obj = new StdClass();
                        $obj->start = $this->convert_to_date($start);
                        if ($end) {
                            $obj->end = $this->convert_to_date($end);
                        }
                        $post->paused_intervals[] = $obj;
                    }
                    if (!$end) {
                        $post->paused_date = $this->convert_to_date($start);
                    }
                }
                $post->date_created = $post->post_date_gmt;
                $post->date_modified = $post->post_modified_gmt;
                $order = wc_get_order( $post->_order_id );
                if ($order) {
                    $post->order = $this->normalise_fields($this->filter_fields($order->get_data(), $this->order_filtered_fields));
                }
                $product = wc_get_product($post->_product_id);
                if ($product) {
                    $post->product = $this->normalise_fields($this->filter_fields($product->get_data(), $this->product_filtered_fields));
                }
                $post = $this->normalise_fields($post);
                $post = $this->filter_fields($post, $this->membership_filtered_fields);
                $result[] = $post;
            } catch(Exception $err) {
                trigger_error($err, E_USER_WARNING);
            }
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
<?php
class ContentPromoter {
    private $options = [
        "revengine_content_promoter_active",
        "revengine_content_promoter_api_url",
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        // add_action('wp_head', [ $this, 'test' ]);
        $enabled = get_option("revengine_content_promoter_active");
        if (empty($enabled)) return;
        add_action('toplevel_page_featured-flagged-post', [ $this, 'front_page_hits']);
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('Content Promoter', 'content-promoter'),
            __('Content Promoter', 'content-promoter'),
            // an admin-level user.
            'manage_options',
            'revengine-content_promoter-options',
            [ $this, 'admin_options_template' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-content_promoter-options-group', $option );
        }
    }

    function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'content-promoter/templates/admin/content-promoter-options.php';
    }

    function front_page_hits() {
        $front_page = $this->get_data("/front_page");
        if (empty($front_page)) return;
        $options = new StdClass();
        $options->articles = $front_page;
        $furl = plugin_dir_url( __FILE__ ) . 'js/frontpage_inject.js';
        $fname = plugin_dir_path( __FILE__ ) . 'js/frontpage_inject.js';
        $ver = date("ymd-Gis", filemtime($fname));
        wp_enqueue_script( "revengine-content-promoter", $furl, null, $ver, true );
        wp_localize_script( "revengine-content-promoter", "revengine_content_promoter_vars", $options);
    }

    private function get_data($endpoint) {
        try {
            $url = get_option("revengine_content_promoter_api_url") . $endpoint;
            $response = file_get_contents($url);
            return json_decode($response);
        } catch(Exception $e) {
            trigger_error("Unable to access RevEngine Content API", E_WARNING);
            return [];
        }
    }

    public function revengine_top_articles($section = null) {
        $endpoint = "/top_articles";
        if (!empty($section)) {
            $endpoint = "/top_articles_by_section/$section";
        }
        try {
            $posts = $this->get_data($endpoint);
            $result = [];
            foreach($posts as $post) {
                $wp_post = get_post($post->post_id);
                if (!empty($wp_post)) {
                    $result[] = $wp_post;
                }
            }
            return $result;
        } catch(Exception $e) {
            trigger_error("Unable to access RevEngine Content API", E_WARNING);
        }
    }

    public function revengine_front_page($section = null) {
        $endpoint = "/front_page";
        try {
            $posts = $this->get_data($endpoint);
            return $posts;
            // print_r($posts);
            // $result = [];
            // foreach($posts as $post) {
            //     $wp_post = get_post($post->post_id);
            //     if (!empty($wp_post)) {
            //         $result[] = $wp_post;
            //     }
            // }
            // return $result;
        } catch(Exception $e) {
            trigger_error("Unable to access RevEngine Content API", E_WARNING);
        }
    }
}
<?php
class PianoComposer {
    private $options = [
        "revengine_piano_active",
        "revengine_piano_sandbox_mode",
        "revengine_piano_id",
        "revengine_exclude_urls"
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        $sections = get_terms("section");
        foreach($sections as $section) {
            $this->options[] = "revengine_exclude_section_{$section->slug}";
        }
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('wp_head', [ $this, 'scripts' ]);
        add_action('amp_post_template_head', [ $this, 'amp' ]);
    }

    function options_page() {
        add_submenu_page(
            'revengine_options',
            __('Piano Composer', 'piano-composer'),
            __('Piano Composer', 'piano-composer'),
            // an admin-level user.
            'manage_options',
            'revengine-piano_composer-options',
            [ $this, 'admin_options_template' ]
        );
    }

    public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-piano_composer-options-group', $option );
        }
    }

    function admin_options_template() {
        require_once plugin_dir_path( dirname( __FILE__ ) ).'piano-composer/templates/admin/piano-composer-options.php';
    }

    private function ignore_url() {
        $url = get_permalink(get_the_ID());
        if (empty($url)) return false;
        $ignore_urls = get_option("revengine_exclude_urls");
        if (empty($ignore_urls)) return false;
        $ignore_array = array_map("trim", explode(",", $ignore_urls));
        foreach ($ignore_array as $ignore_url) {
            if (stripos($url, $ignore_url)) {
                return true;
            }
        }
        return false;
    }

    private function ignore_section() {
        $post_id = get_queried_object_id();
        $sections = wp_get_post_terms($post_id, 'section');
        $ignore = false;
        foreach($sections as $section) {
            if (get_option["revengine_exclude_section_{$section->slug}"]) {
                $ignore = true;
            }
        }
        return $ignore;
    }

    private function get_post_data() {
        $result = [];
        $post_id = get_queried_object_id();
        $post = get_queried_object();
        if (!empty($post->post_type)) {
            $post_type = $post->post_type;
        } else if (!empty($post->taxonomy)) {
            $post_type = $post->taxonomy;
        } else {
            $post_type = "";
        }
        $result["post_type"] = $post_type;
        if ($post_type === "article") {
            $result["author"] = get_the_author_meta("display_name");
            $tags = get_the_terms($post_id, "article_tag");
            if ($tags) {
                $result["tags"] = array_map(function($i) { return $i->name; }, $tags);
            }
            $sections = get_the_terms($post_id, "section");
            if ($sections) {
                $result["sections"] = array_map(function($i) { return $i->name; }, $sections);
            }
            $term_list = wp_get_post_terms($post_id, 'section', ['fields' => 'all']);
            foreach($term_list as $term) {
                if( get_post_meta($post_id, '_yoast_wpseo_primary_section',true) == $term->term_id ) {
                    $result["primary_section"] = $term->name;
                }
            }
        } else if ($post_type === "opinion-piece") {
            $result["author"] = get_the_author_meta("display_name");
            $tags = get_the_terms($post_id, "opinion-piece-tag");
            if ($tags) {
                $result["tags"] = array_map(function($i) { return $i->name; }, $tags);
            }
            $result["sections"] = ["opinionista"];
        }
        return $result;
    }

    function scripts() {
        if ($this->ignore_url()) return;
        if ($this->ignore_section()) return;
        $post_id = get_queried_object_id();
        $post = get_queried_object();
        if (!empty($post->post_type)) {
            $post_type = $post->post_type;
        } else if (!empty($post->taxonomy)) {
            $post_type = $post->taxonomy;
        } else {
            $post_type = "";
        }
        $options = [
            "post_type" => $post_type,
            "date_published" => get_the_date("c"),
            "logged_in" => !empty(get_current_user_id()),
        ];
        if ( function_exists( 'wc_memberships' ) ) {
            $memberships = array_map(function($i) { return $i->plan->name; },wc_memberships_get_user_active_memberships());
            $options["memberships"] = $memberships;
        }
        if ($post_type === "article") {
            $options["author"] = get_the_author_meta("display_name");
            $tags = get_the_terms($post_id, "article_tag");
            if ($tags) {
                $options["tags"] = array_map(function($i) { return $i->name; }, $tags);
            }
            $sections = get_the_terms($post_id, "section");
            if ($sections) {
                $options["sections"] = array_map(function($i) { return $i->name; }, $sections);
            }
            $term_list = wp_get_post_terms($post_id, 'section', ['fields' => 'all']);
            foreach($term_list as $term) {
                if( get_post_meta($post_id, '_yoast_wpseo_primary_section',true) == $term->term_id ) {
                    $options["primary_section"] = $term->name;
                }
            }
        } else if ($post_type === "opinion-piece") {
            $options["author"] = get_the_author_meta("display_name");
            $tags = get_the_terms($post_id, "opinion-piece-tag");
            if ($tags) {
                $options["tags"] = array_map(function($i) { return $i->name; }, $tags);
            }
            $options["sections"] = ["opinionista"];
        }
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        // trigger_error(json_encode($options), E_USER_NOTICE);
        if (!$options["revengine_piano_active"]) return;
        $furl = plugin_dir_url( __FILE__ ) . 'js/piano.js';
        $fname = plugin_dir_path( __FILE__ ) . 'js/piano.js';
        $ver = date("ymd-Gis", filemtime($fname));
        wp_enqueue_script( "revengine-piano-composer", $furl, null, $ver, true );
        wp_localize_script( "revengine-piano-composer", "revengine_piano_composer_vars", $options);
    }

    public function amp() {
        if (is_admin()) return; // Front end only
        if (is_404()) return; // Don't log 404s
        if ($this->ignore_url()) return;
        if ($this->ignore_section()) return;
        $post = $this->get_post_data();
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if (!$options["revengine_piano_active"]) return;
        $url = ($options["revengine_piano_sandbox_mode"]) ? "sandbox.tinypass.com" : "experience.tinypass.com";
        $piano_url = "https://" . $url . "/xbuilder/experience/executeAmp?protocol_version=1&aid=" . $options["revengine_piano_id"] ."&reader_id=READER_ID&url=SOURCE_URL&referer=DOCUMENT_REFERRER&_=RANDOM";
        require_once plugin_dir_path( dirname( __FILE__ ) ).'piano-composer/templates/frontend/amp.php';
    }
}
<?php

class RevEngineContentData {
    public function get_post_data() {
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
        if (isset($_COOKIE["revengine-browser-token"])) {
            $browser_token = $_COOKIE["revengine-browser-token"];
        } else {
            $browser_token = bin2hex(openssl_random_pseudo_bytes(16));
            setcookie("revengine-browser-token", $browser_token);
        }
        $data = (object) [
            "action" => "pageview",
            "ip" => $_SERVER["REMOTE_ADDR"],
            "request_time" => $_SERVER["REQUEST_TIME"],
            "post_id" => $post_id,
            "user_id" => get_current_user_id(),
            "browser_id" => $browser_token,
            "post_title" => esc_html($post_title),
            "post_type" => $post_type,
            "home_page" => is_front_page(),
            "date_published" => get_the_date("c"),
        ];
        if (isset($_SERVER["HTTP_REFERER"])) {
            $data->referer = $_SERVER["HTTP_REFERER"];
        }
        if ($post_type == "article" || $post_type == "opinion-piece" || $post_type == "post") { // Empty post types are section pages, home pages etc
            $data->post_author = get_the_author_meta("display_name", $post->post_author);
            // $taxonomies = get_object_taxonomies( $post_type, 'objects' );
            // print_r($taxonomies);
            $data->post_sections = [];
            $terms = get_the_terms($post_id, "section");
            if (is_array($terms)) {
                $data->post_sections = array_merge($data->post_sections, array_map(function($i) { return $i->name; }, $terms));
            }
            $categories = get_the_terms($post_id, "category");
            if (is_array($categories)) {
                $data->post_sections = array_merge($data->post_sections, array_map(function($i) { return $i->name; }, $categories));
            }
            $term_list = wp_get_post_terms($post_id, 'section', ['fields' => 'all']);
            foreach($term_list as $term) {
                if( get_post_meta($post_id, '_yoast_wpseo_primary_section',true) == $term->term_id ) {
                    $data->primary_section = $term->name;
                }
            }
            $data->post_tags = [];
            $tags = get_the_terms($post_id, "post_tag");
            if (is_array($tags)) {
                $data->post_tags = array_merge($data->post_tags, array_map(function($i) { return $i->name; }, $tags));
            }
            $tags = get_the_terms($post_id, "article_tag");
            if (is_array($tags)) {
                $data->post_tags = array_merge($data->post_tags, array_map(function($i) { return $i->name; }, $tags));
            }
            $tags = get_the_terms($post_id, "opinionista_tag");
            if (is_array($tags)) {
                $data->post_tags = array_merge($data->post_tags, array_map(function($i) { return $i->name; }, $tags));
            }
        }
        return $data;
    }
}
<?php
class PianoComposer {
    private $options = [
        "revengine_piano_active",
        "revengine_piano_sandbox_mode",
        "revengine_piano_id",
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
        add_action('wp_head', [ $this, 'scripts' ]);
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

    function scripts() {
        $options = [];
        foreach($this->options as $option) {
            $options[$option] = get_option($option);
        }
        if ($options["revengine_piano_active"]) {
            $fname = plugin_dir_url( __FILE__ ) . 'js/piano.js';
            $ver = date("ymd-Gis", filemtime($fname));
            wp_enqueue_script( "revengine-piano-composer", $fname, null, $ver, true );
            wp_localize_script( "revengine-piano-composer", "revengine_piano_composer_vars", $options);
        }
    }
}
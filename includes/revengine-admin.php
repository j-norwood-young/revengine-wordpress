<?php

class RevEngineAdmin {
    protected $options = [];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'register_settings' ]);
        add_action('admin_menu', [ $this, 'options_page' ]);
    }

    function options_page() {
        add_menu_page(
			'RevEngine',
			'RevEngine',
			'manage_options',
			'revengine_options',
			[ $this, 'revengine_options' ]
		);
    }

     public function register_settings() {
        foreach($this->options as $option) {
            register_setting( 'revengine-options-group', $option );
        }
    }

    function revengine_options() {
		require_once plugin_dir_path( dirname( __FILE__ ) ).'templates/admin/options.php';
    }
}
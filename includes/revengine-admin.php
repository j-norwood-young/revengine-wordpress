<?php

class RevEngineAdmin {
    protected $options = [
        "revengine_server_address",
        "revengine_enable_tracking",
        "revengine_api_key"
    ];

    function __construct($revengine_globals) {
        $this->revengine_globals = &$revengine_globals;
        add_action('admin_init', [ $this, 'check_api_key' ]);
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
		require_once plugin_dir_path( dirname( __FILE__ ) ).'templates/admin/revengine-options.php';
    }

    function check_api_key() {
        $key = get_option("revengine_api_key");
        if (empty($key)) {
            update_option("revengine_api_key", bin2hex(random_bytes(32)));
        }
    }
}
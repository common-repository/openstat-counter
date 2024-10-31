<?php

// ==============================================================================================
// Licensed under the GPLv2 license
// ==============================================================================================
// @author     WEBO Software (http://www.webogroup.com/)
// @version    0.1
// @copyright  Copyright &copy; 2013 Openstat, All Rights Reserved
// ==============================================================================================
/*
Plugin Name: Openstat Counter
Plugin URI: https://www.openstat.ru/
Description: Openstat counter for your website, automatic installation
Author: Openstat
Version: 0.1
Author URI: https://www.openstat.ru/
*/
/* get generic library */
	include(dirname(__FILE__) . '/openstat.counter.api.php');
	if (@is_file(dirname(__FILE__) . '/languages/' . WPLANG . '.php')) {
		@include(dirname(__FILE__) . '/languages/' . WPLANG . '.php');
	} else {
		@include(dirname(__FILE__) . '/languages/en_US.php');
	}

	if (!function_exists('openstat_counter_activate')) {
/* main activation function */
		function openstat_counter_activate () {
			update_option('openstat_counter_option_name', openstat_counter_api_add(get_option('admin_email'), get_option('siteurl'), 'WEBO@WordPress ' . $wp_version));
		}
	}
/* register new account or get ID for the current one */
	register_activation_hook(__FILE__, 'openstat_counter_activate');
	
	if (!function_exists('openstat_counter_notice')) {
/* show activation message */
		function openstat_counter_notice () {
			echo '<div class="updated">' .
				PLG_OPENSTATCOUNTER_ABOUT1 .
				get_option('openstat_counter_option_name', '') .
				PLG_OPENSTATCOUNTER_ABOUT2 .
				'</div>';
		}
	}
	if (empty($_COOKIE['openstat_counter_id'])) {
		add_action('admin_notices', 'openstat_counter_notice');
	}
	
	if (!function_exists('openstat_counter_footer')) {
/* main function for every page execution */
		function openstat_counter_footer() {
		$options = get_option('openstat_counter_option_name', '');
		$code = is_array($options) ? $options['openstat_counter_code'] : $options;
?>
<?php echo openstat_counter_api_code($options['openstat_counter_code']) ?>
<?php
		}
	}
	if (!is_admin()) {
/* add init and finish hook */
		add_action('wp_footer', 'openstat_counter_footer');
	}

class OpenstatCounterSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            PLG_OPENSTATCOUNTER, 
            'manage_options', 
            'openstat-counter-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'openstat_counter_option_name' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php echo PLG_OPENSTATCOUNTER ?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'openstat_counter_option_group' );   
                do_settings_sections( 'openstat-counter-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'openstat_counter_option_group', // Option group
            'openstat_counter_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'openstat_countet_section', // ID
            PLG_OPENSTATCOUNTER, // Title
            array( $this, 'print_section_info' ), // Callback
            'openstat-counter-admin' // Page
        );  

        add_settings_field(
            'openstat_counter_code', // ID
            PLG_OPENSTATCOUNTER_CODE, // Title 
            array( $this, 'openstat_counter_callback' ), // Callback
            'openstat-counter-admin', // Page
            'openstat_countet_section' // Section           
        );   
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if (isset( $input['openstat_counter_code'])) {
            $new_input['openstat_counter_code'] = preg_replace("!<^[>]+>!is", "",  $input['openstat_counter_code'] );
		}
        return $new_input;
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function openstat_counter_callback()
    {
		$code = esc_attr(is_array($this->options) ? $this->options['openstat_counter_code'] : get_option('openstat_counter_option_name'));
        printf(
            '<textarea id="openstat_counter_code" name="openstat_counter_option_name[openstat_counter_code]" cols="80" rows="10" title="' . PLG_OPENSTATCOUNTER_CODE_DESCRIPTION . '">%s</textarea>', $code);
    }
}

	if (is_admin()) {
		$openstat_counter_settings_page = new OpenstatCounterSettingsPage();
	}

	if (!function_exists('openstat_counter_settings_link')) {
// Add settings link on plugin page
		function openstat_counter_settings_link ($links) { 
			$settings_link = '<a href="options-general.php?page=openstat-counter-admin">' . __('Settings') . '</a>'; 
			array_unshift($links, $settings_link); 
			return $links; 
		}
	}
	add_filter("plugin_action_links_" . plugin_basename( __FILE__ ), 'openstat_counter_settings_link' );

// Adding localization
	if (!function_exists('openstatcounter_text_domain')) {
		function openstatcounter_text_domain() {
			load_plugin_textdomain('openstatcounter');
		}
	}
	add_action('plugins_loaded', 'openstatcounter_text_domain');
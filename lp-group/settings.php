<?php
if(!class_exists('lp_group_Settings'))
{
	class lp_group_Settings
	{
		/**
		 * Construct the plugin object
		 */
        public $lp_group_Settings;
		public function __construct()
		{
			// register actions
            add_action('admin_init', array(&$this, 'admin_init'));
        	add_action('admin_menu', array(&$this, 'add_menu'));
		} // END public function __construct
		
        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
        	// register your plugin's settings
        	register_setting('lp_group-group', 'setting_a');
        	register_setting('lp_group-group', 'setting_b');

        	// add your settings section
        	add_settings_section(
        	    'lp_group-section', 
        	    'WP Plugin Template Settings', 
        	    array(&$this, 'settings_section_lp_group'), 
        	    'lp_group'
        	);
        	
        	// add your setting's fields
            add_settings_field(
                'lp_group-setting_a', 
                'Setting A', 
                array(&$this, 'settings_field_input_text'), 
                'lp_group', 
                'lp_group-section',
                array(
                    'field' => 'setting_a'
                )
            );
            add_settings_field(
                'lp_group-setting_b', 
                'Setting B', 
                array(&$this, 'settings_field_input_text'), 
                'lp_group', 
                'lp_group-section',
                array(
                    'field' => 'setting_b'
                )
            );
            // Possibly do additional admin_init tasks
        } // END public static function activate
        
        public function settings_section_lp_group()
        {
            // Think of this as help text for the section.
            echo 'These settings do things for the LP Groups Plugin.';
        }
        
        /**
         * This function provides text inputs for settings fields
         */
        public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            $value = get_option($field);
            // echo a proper input type="text"
            echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
        } // END public function settings_field_input_text($args)
        
        /**
         * Menu Callback
         */		
        public function plugin_settings_page()
        {
        	if(!current_user_can('manage_options'))
        	{
        		wp_die(__('You do not have sufficient permissions to access this page.'));
        	}
	
        	// Render the settings template
        	include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()
    } // END class lp_group_Settings
} // END if(!class_exists('lp_group_Settings'))

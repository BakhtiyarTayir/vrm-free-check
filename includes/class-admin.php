<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('VRM Check Settings', 'vrm-check-plugin'),
            __('VRM Check', 'vrm-check-plugin'),
            'manage_options',
            'vrm-check-plugin',
            array($this, 'admin_page')
        );
    }
    
    public function init_settings() {
        register_setting('vrm_check_settings', 'vrm_check_api_key');
        
        add_settings_section(
            'vrm_check_api_section',
            __('API Settings', 'vrm-check-plugin'),
            array($this, 'api_section_callback'),
            'vrm_check_settings'
        );
        
        add_settings_field(
            'vrm_check_api_key',
            __('API Key', 'vrm-check-plugin'),
            array($this, 'api_key_callback'),
            'vrm_check_settings',
            'vrm_check_api_section'
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="vrm-check-admin-content">
                <div class="vrm-check-settings">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('vrm_check_settings');
                        do_settings_sections('vrm_check_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="vrm-check-info">
                    <h3><?php _e('How to use', 'vrm-check-plugin'); ?></h3>
                    <p><?php _e('Use the shortcode [vrm_check] to display the VRM check form on any page or post.', 'vrm-check-plugin'); ?></p>
                    
                    <h3><?php _e('API Information', 'vrm-check-plugin'); ?></h3>
                    <p><?php _e('This plugin uses Vehicle Data Global API to retrieve vehicle information.', 'vrm-check-plugin'); ?></p>
                    <p><?php _e('API URL: https://uk.api.vehicledataglobal.com/r2/lookup', 'vrm-check-plugin'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function api_section_callback() {
        echo '<p>' . __('Configure your Vehicle Data Global API key below.', 'vrm-check-plugin') . '</p>';
    }
    
    public function api_key_callback() {
        $api_key = get_option('vrm_check_api_key', '15D0A432-A308-4B28-89B4-6E07F0C55DCE');
        echo '<input type="text" id="vrm_check_api_key" name="vrm_check_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Enter your Vehicle Data Global API key.', 'vrm-check-plugin') . '</p>';
    }
}
<?php
namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcode {
    
    public function __construct() {
        add_shortcode('vrm_check', array($this, 'render_form'));
        add_shortcode('vrm_check_premium', array($this, 'render_premium_template'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Free Car Check', 'vrm-check-plugin'),
            'placeholder' => __('Enter registration number', 'vrm-check-plugin'),
            'button_text' => __('Check Vehicle', 'vrm-check-plugin'),
            'show_example' => 'no'
        ), $atts);
        
        ob_start();
        ?>
        <div class="vrm-check-container">
            <div class="vrm-check-form-wrapper">
                <div class="vrm-check-header">
                    <h2 class="vrm-check-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="vrm-check-subtitle">
                        <?php _e('Get instant access to vehicle information including MOT, tax status, and vehicle specifications', 'vrm-check-plugin'); ?>
                    </p>
                </div>
                
                <form id="vrm-check-form" class="vrm-check-form">
                    <div class="vrm-input-group">
                        <div class="vrm-input-wrapper">
                            <input 
                                type="text" 
                                id="vrm-input" 
                                name="vrm" 
                                class="vrm-input" 
                                placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                                maxlength="8"
                                required
                            >
                            <div class="vrm-buttons-group">
                                <button type="button" class="vrm-submit-btn vrm-basic-btn" onclick="checkVRMBasic()">
                                    <span class="vrm-btn-text"><?php echo esc_html($atts['button_text']); ?></span>
                                    <span class="vrm-btn-loading" style="display: none;">
                                        <i class="vrm-spinner"></i>
                                        <?php _e('Checking...', 'vrm-check-plugin'); ?>
                                    </span>
                                </button>
                            
                            </div>
                        </div>
                        
                        <?php if ($atts['show_example'] === 'yes'): ?>
                        <div class="vrm-example">
                            <span class="vrm-example-text"><?php _e('Example:', 'vrm-check-plugin'); ?></span>
                            <button type="button" class="vrm-example-btn" data-vrm="BL66VPO">BL66VPO</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php wp_nonce_field('vrm_check_nonce', 'vrm_check_nonce'); ?>
                </form>
                
                <div class="vrm-features">
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('MOT Status', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Tax Information', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Vehicle Specifications', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Instant Results', 'vrm-check-plugin'); ?></span>
                    </div>
                </div>
            </div>
            
            <div id="vrm-check-results" class="vrm-check-results-container" style="display: none;"></div>
            
            <div id="vrm-check-error" class="vrm-check-error" style="display: none;">
                <div class="vrm-error-content">
                    <i class="vrm-icon-warning"></i>
                    <span class="vrm-error-message"></span>
                </div>
            </div>
        </div>
        

        <?php
         return ob_get_clean();
    }
     
    public function render_premium_template($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Premium Car Check', 'vrm-check-plugin'),
            'placeholder' => __('Enter registration number', 'vrm-check-plugin'),
            'button_text' => __('Check Vehicle', 'vrm-check-plugin'),
            'show_example' => 'no'
        ), $atts);
        
        ob_start();
        ?>
        <div class="vrm-check-container">
            <div class="vrm-check-form-wrapper">
                <div class="vrm-check-header">
                    <h2 class="vrm-check-title"><?php echo esc_html($atts['title']); ?></h2>
                    <p class="vrm-check-subtitle">
                        <?php _e('Get instant access to vehicle information including MOT, tax status, and vehicle specifications', 'vrm-check-plugin'); ?>
                    </p>
                </div>
                
                <form id="vrm-check-form" class="vrm-check-form">
                    <div class="vrm-input-group">
                        <div class="vrm-input-wrapper">
                            <input 
                                type="text" 
                                id="vrm-input" 
                                name="vrm" 
                                class="vrm-input" 
                                placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                                maxlength="8"
                                required
                            >
                            <button type="button" class="vrm-submit-btn vrm-premium-btn" onclick="checkVRMPremium()">
                                <span class="vrm-btn-text"><?php echo esc_html($atts['button_text']); ?></span>
                                <span class="vrm-btn-loading" style="display: none;">
                                    <i class="vrm-spinner"></i>
                                    <?php _e('Checking Premium...', 'vrm-check-plugin'); ?>
                                </span>
                            </button>
                        </div>
                        
                        <?php if ($atts['show_example'] === 'yes'): ?>
                        <div class="vrm-example">
                            <span class="vrm-example-text"><?php _e('Example:', 'vrm-check-plugin'); ?></span>
                            <button type="button" class="vrm-example-btn" data-vrm="BL66VPO">BL66VPO</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php wp_nonce_field('vrm_check_nonce', 'vrm_check_nonce'); ?>
                </form>
                
                <div class="vrm-features">
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('MOT Status', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Tax Information', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Vehicle Specifications', 'vrm-check-plugin'); ?></span>
                    </div>
                    <div class="vrm-feature-item">
                        <i class="vrm-icon-check"></i>
                        <span><?php _e('Instant Results', 'vrm-check-plugin'); ?></span>
                    </div>
                </div>
            </div>
            
            <div id="vrm-check-results" class="vrm-check-results-container" style="display: none;"></div>
            
            <div id="vrm-check-error" class="vrm-check-error" style="display: none;">
                <div class="vrm-error-content">
                    <i class="vrm-icon-warning"></i>
                    <span class="vrm-error-message"></span>
                </div>
            </div>
        </div>
        

        <?php
         return ob_get_clean();
    }
     
    public function enqueue_scripts() {
         // Only enqueue on pages that contain the shortcode
         global $post;
         if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'vrm_check') || has_shortcode($post->post_content, 'vrm_check_premium'))) {
             wp_enqueue_style('vrm-check-style', VRM_CHECK_PLUGIN_URL . 'assets/css/vrm-check-style.css', array(), VRM_CHECK_PLUGIN_VERSION);
             
             // Enqueue premium styles for premium shortcode
             if (has_shortcode($post->post_content, 'vrm_check_premium')) {
                 wp_enqueue_style('vrm-check-premium-style', VRM_CHECK_PLUGIN_URL . 'assets/css/premium-style.css', array(), VRM_CHECK_PLUGIN_VERSION);
             }
             
             wp_enqueue_script('vrm-check-script', VRM_CHECK_PLUGIN_URL . 'assets/js/vrm-check-script.js', array('jquery'), VRM_CHECK_PLUGIN_VERSION, true);
             
             // Localize script for AJAX
            wp_localize_script('vrm-check-script', 'vrm_check_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vrm_check_nonce')
            ));
         }
    }
}
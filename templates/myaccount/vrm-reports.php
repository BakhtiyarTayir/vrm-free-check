<?php
/**
 * My Account - VRM Reports
 *
 * @package VRM_Check_Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="woocommerce-MyAccount-content">
    <h2><?php esc_html_e('My Vehicle Check Reports', 'vrm-check-plugin'); ?></h2>
    
    <?php
    // Отображаем историю проверок
    if (shortcode_exists('vrm_check_history')) {
        echo do_shortcode('[vrm_check_history]');
    } else {
        ?>
        <div class="woocommerce-error">
            <p><?php esc_html_e('History shortcode not found. Please contact support.', 'vrm-check-plugin'); ?></p>
        </div>
        <?php
    }
    ?>
</div>

<?php
/**
 * Checkout Fields Customization
 * 
 * Настройка полей оформления заказа WooCommerce
 *
 * @package VRM_Check_Plugin
 */

namespace VrmCheckPlugin;

if (!defined('ABSPATH')) {
    exit;
}

class CheckoutFields {
    
    /**
     * Инициализация
     */
    public static function init() {
        // Сделать postcode необязательным
        add_filter('woocommerce_billing_fields', array(__CLASS__, 'customize_billing_fields'));
        add_filter('woocommerce_shipping_fields', array(__CLASS__, 'customize_shipping_fields'));
        
        // Отключить валидацию postcode
        add_filter('woocommerce_validate_postcode', array(__CLASS__, 'disable_postcode_validation'), 10, 3);
    }
    
    /**
     * Настроить поля биллинга
     */
    public static function customize_billing_fields($fields) {
        // Сделать postcode необязательным
        if (isset($fields['billing_postcode'])) {
            $fields['billing_postcode']['required'] = false;
            $fields['billing_postcode']['label'] = __('Postcode (optional)', 'vrm-check-plugin');
        }
        
        return $fields;
    }
    
    /**
     * Настроить поля доставки
     */
    public static function customize_shipping_fields($fields) {
        // Сделать postcode необязательным
        if (isset($fields['shipping_postcode'])) {
            $fields['shipping_postcode']['required'] = false;
            $fields['shipping_postcode']['label'] = __('Postcode (optional)', 'vrm-check-plugin');
        }
        
        return $fields;
    }
    
    /**
     * Отключить валидацию postcode
     */
    public static function disable_postcode_validation($valid, $postcode, $country) {
        // Если postcode пустой, считаем его валидным
        if (empty($postcode)) {
            return true;
        }
        
        // Иначе используем стандартную валидацию
        return $valid;
    }
}

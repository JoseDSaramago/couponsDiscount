<?php
/*
 * Plugin Name:       Trendee Coupons Discount
 * Plugin URI:        https://github.com/JoseDMarck/couponsDiscount
 * Description:       Aplicar cupones sobre el descuento ahorrado
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            @josedsamamago
 * Author URI:        https://github.com/JoseDMarck/
 * License:           GPL v2 or later
 * License URI:       https://github.com/JoseDMarck/couponsDiscount/blob/main/licence.txt
 */


if (!defined('ABSPATH')) {
    die('silent is golden...');
}

if (!class_exists('TrendeeCoupons')) {

    class TrendeeCoupons
    {

        public static $totalLastOrder = 0;
        public static $coupons = array();
        public static $atp_saldo;

        public function __construct()
        {
            define("PLUGIN_PATH", plugin_dir_path(__FILE__));
            define('TC__FILE__', __FILE__);
        }
        public function initialize()
        {
            require_once PLUGIN_PATH . 'includes/register_activation_hook.php';
            require_once PLUGIN_PATH . 'includes/register_coupons_types.php';
            require_once PLUGIN_PATH . 'includes/client_atp_saldo.php';
            require_once PLUGIN_PATH . 'shortcodes/coupon_modal.php';
        }

        public static function setTotalLastOrder($value)
        {
            TrendeeCoupons::$totalLastOrder = $value;
        }

        public static function setCoupons($value)
        {
            TrendeeCoupons::$coupons = $value;
        }

        public static function setATPSaldo($value)
        {
            TrendeeCoupons::$atp_saldo = $value;
        }

        public function getClientData()
        {
            require_once PLUGIN_PATH . 'includes/client_orders.php';
            require_once PLUGIN_PATH . 'includes/client_coupons.php';
            require_once PLUGIN_PATH . 'includes/insert_client_data.php';
        }

    }

    $TrendeeCoupons = new TrendeeCoupons();
    $TrendeeCoupons->initialize();
    $TrendeeCoupons->getClientData();

}






/*----------------------------------------------------------------
/*  Revisamos si el usario tiene pedidos
/*----------------------------------------------------------------*/

add_action("init", 'checkisUserHaveOrder', 10);
function checkisUserHaveOrder()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-coupons-discount.php';
    $CPD = new CouponsDiscount();

    /*----------------------------------------------------------------
    /*  1.- Verificar si el cliente tiene pedidos
    /*----------------------------------------------------------------*/

    $clientHasOrder = $CPD->clientHasOrder();

    if (!$clientHasOrder):
        return false;
    endif;

    /*----------------------------------------------------------------
    /* 2.- Obtener el ultimo pedido (completado o precesando) del cliente 
    /*----------------------------------------------------------------*/
    $CPD->getLastClientOrder();

    /*----------------------------------------------------------------
    /* 3.- Si Notiene datos; se inserta
    /*----------------------------------------------------------------*/
    $checkIsUserHasData = $CPD->checkIsUserHasData();
    if (!$checkIsUserHasData):
        $CPD->insertLastClientOrder(); //Si no tiene datos insertarlos en DB
    endif;

    /*----------------------------------------------------------------
    /* 4.- Si ya tiene data setteamos user data
    /*----------------------------------------------------------------*/
    if ($checkIsUserHasData):
        $CPD->setUserData();
    endif;

    $checkIsCouponUsed = $CPD->checkIsCouponUsed();
    if ($checkIsCouponUsed):
        return false;
    endif;

    /*----------------------------------------------------------------
    /* 5.- Si ya tiene datos se actualiza
    /*----------------------------------------------------------------*/

    if ($checkIsUserHasData):
        $CPD->updateLastClientOrder(); //Si ya tiene datos actualizarlos en DB
    endif;

}





/*----------------------------------------------------------------
/*  Aplicar el cupon de descuento
/*----------------------------------------------------------------*/

add_action('woocommerce_thankyou', 'applyDiscountCoupon', 9);
function applyDiscountCoupon()
{


    require_once plugin_dir_path(__FILE__) . 'includes/class-coupons-discount.php';
    $CPD = new CouponsDiscount();


    /*----------------------------------------------------------------
    /* 1.- Setteamos los datos de usuario
    /*----------------------------------------------------------------*/
    $CPD->setUserData();



    $checkIsCouponUsed = $CPD->checkIsCouponUsed();
    if ($checkIsCouponUsed):
        return false;
    endif;


    /*----------------------------------------------------------------
    /* 2.- Get Coupon Data
    /*----------------------------------------------------------------*/

    $CPD->getLastClientOrder();

    /*----------------------------------------------------------------
    /* 2.- Get Coupon Data
    /*----------------------------------------------------------------*/
    $getCouponsData = $CPD->getCouponsData();

    if (!$getCouponsData):
        return false;
    endif;

    $CPD->setCouponData($getCouponsData);


    /*----------------------------------------------------------------
    /* 3.- Aplicamos las operaciones para aplicar el cupon
    /*----------------------------------------------------------------*/
    $getDiscount = $CPD->getCouponDiscount();

    if (!$getDiscount):
        return false;
    endif;

    /*----------------------------------------------------------------
    /* 4.- Actualizamos los nuevos datos en la base de datos
    /*----------------------------------------------------------------*/
    $CPD->updateLastClientOrder();

    /*----------------------------------------------------------------
    /* 5.- Guardamos en el localstorage el nuevo valor
    /*----------------------------------------------------------------*/
    $accumulated = $CPD->updateLastClientOrder()->accumulated_savings;

    //print_r($CPD->userData);
    $user_data = $CPD->userData;

    saveSaldoOnLocalStorage($accumulated, $user_data);
}



/*----------------------------------------------------------------
/* Guardamos Los nuevos valores en el localstorage
/*----------------------------------------------------------------*/
function saveSaldoOnLocalStorage($saldo, $user_data)
{
    wp_enqueue_script('coupon_discount_save_saldo', plugins_url('/public/js/gd__save_saldo.js', __FILE__), array('jquery'), '20200110');

    wp_localize_script(
        'coupon_discount_save_saldo',
        'wp_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'newSaldo' => $saldo,
            'userdata' => $user_data
        )
    );
    wp_enqueue_script('coupon_discount_save_saldo');
}

/**
 * Register and enqueue a custom stylesheet in the WordPress admin.
 */
add_action('init', 'wpdocs_enqueue_custom_admin_style');
function wpdocs_enqueue_custom_admin_style()
{
    wp_register_style('custom_wp_admin_css', plugin_dir_url(__FILE__) . 'public/css/gd__modal_coupon.css', false, '1.0.0');
    wp_enqueue_style('custom_wp_admin_css');
}

add_action('init', 'wpdocs_enqueue_magic_library');
function wpdocs_enqueue_magic_library()
{
    wp_register_style('magic_library_css', plugin_dir_url(__FILE__) . 'public/css/magic.min.css', false, '1.0.0');
    wp_enqueue_style('magic_library_css');
}

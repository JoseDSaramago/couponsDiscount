<?php

add_action('init', 'insertClientData');
function insertClientData()
{
    $coupons = TrendeeCoupons::$coupons;

    foreach ($coupons as $coupon) {

        $couponCode = $coupon["code"];
        $haveCoupon = checkIsUserHasCoupon($couponCode);

        if (empty($haveCoupon)):
            inserUserInfo($coupon);
        endif;
    }

}


/*----------------------------------------------------------------
/* Revisamos si el usario tiene cupones 
/*----------------------------------------------------------------*/
function checkIsUserHasCoupon($coupon)
{
    global $wpdb;
    $result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM wp_coupons_data WHERE id_user = %d AND coupon_code = %s",
            get_current_user_id(),
            $coupon
        )
    );

    return $result;
}

/*----------------------------------------------------------------
/* Se insertan los datos de los cupones 
/*----------------------------------------------------------------*/
function inserUserInfo($coupon)
{

    if (get_current_user_id() > 0):
        global $wpdb;
        $totalLastOrder = TrendeeCoupons::$totalLastOrder;

        echo "ATP Saldo";
        echo $atp_saldo = TrendeeCoupons::$atp_saldo;

        $wpdb->insert(
            'wp_coupons_data',
            array(
                'last_purchase_mount' => $totalLastOrder,
                'coupon_code' => $coupon["code"],
                'coupon_type' => $coupon["type"],
                'coupon_value' => $coupon["amount"] . "%",
                'id_user' => get_current_user_id()
            ),
        );
    endif;

}

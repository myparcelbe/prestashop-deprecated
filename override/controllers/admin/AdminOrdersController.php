<?php
/**
 * Backend orders controller
 * 
 * @copyright Copyright (c) 2014 MyParcel (http://www.myparcel.nl/)
 */

class AdminOrdersController extends AdminOrdersControllerCore
{
    public function __construct()
    {
        parent::__construct();

        $myParcelFlag = Configuration::get('MYPARCEL_ACTIVE');

        $this->context->smarty->assign(
            array(
                'base_uri'          => rtrim(_PS_BASE_URL_.__PS_BASE_URI__, '/'),
                'myParcel'          => $myParcelFlag,
                'prestaShopVersion' => substr(_PS_VERSION_, 0, 3),
            )
        );

        if (true == $myParcelFlag) {
            if ('' == session_id()) {
                session_start();
            }

            $_SESSION['MYPARCEL_VISIBLE_CONSIGNMENTS'] = '';
        }
    }
}
<?php



class OrderOpcController extends OrderOpcControllerCore
{
   /*
   * module: myparcel
   * date: 2015-05-06 05:33:41
   * version: 1.0.0
   */
    /*
	* module: myparcel
	* date: 2016-03-08 23:38:09
	* version: 1.0.0
	*/
    public function setMedia()
    {
        parent::setMedia();

        $frontend_plugin = Configuration::get('MYPARCEL_FRONTEND_PLUGIN');

        if (empty($frontend_plugin))
            return;

        $myparcel = Module::getInstanceByName('myparcel');
        $myparcel_plugin_folder = $myparcel->getPluginFolderLabel();

        /* Start custom code */
        Media::addJsDef(array(
            'MYPARCEL_FORM_OPTION_OVERLAY' => $this->getFormOptionOverlay(),
            'MYPARCEL_BPOST_AJAX_URL' => __PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/myparcel-deliveryoptions-request.php',
            'MYPARCEL_AJAX_LOADING_ICON_URL' => __PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/images/bpost/ajax-loader.gif',
        ));
        /* End custom code */

        /*$url = $myparcel->getMyParcelUrl();
        if(!$myparcel->isPrestashop15()){
            Media::addJsDef(array(
                'MYPARCEL_PAKJEGEMAK_URL' => $url,
            ));
        }*/

        $this->addCSS(__PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/css/bpost/myparcel-deliveryoptions.css');

        $this->addJS(array(
            __PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/js/frontend.js',
            __PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/js/jquery.popupoverlay.js',
            __PS_BASE_URI__.'modules/' . $myparcel_plugin_folder . '/js/myparcel-deliveryoptions.js'
        ));
    }

    public function getFormOptionOverlay()
    {
        return '<div id="delivery_options_overlay" class="block white bordershadow orangeborder"> <div class="container"> <div class="col-set"> <div class="logo">Bpost</div> <h2>Bezorgopties</h2> <a href="#" class="delivery_options_overlay_close right" onclick="showSpinners(true, true);">Sluiten</a> </div> <div class="col-set"> <div id="pickup" class="col"> <div class="bordershadow orangeborder desktop"> <h3 class="orange">Ophalen</h3> </div> <div class="bordershadow orangeborder mobile"> <h3 class="orange">Ophalen</h3> <h3 class="white">Bezorgen</h3> </div> <div id="pickup_inputs" class="input-set text"> <label for="pickup_input">Postcode</label> <input type="text" value="" id="pickup_input" name="pickup_input" /> </div> <div class="locations"> <img class="loading" src="' . __PS_BASE_URI__. 'modules/myparcel/images/bpost/ajax-loader.gif' . '" /> </div> <div> <a class="right" id="show_pickup_inputs" href="#">kies ander ophaalpunt</a> <a id="show_more_pickups" href="#">meer locaties weergeven</a> </div> </div> </div> </div> </div>';
    }
}
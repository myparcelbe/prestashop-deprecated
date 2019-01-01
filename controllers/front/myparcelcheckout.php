<?php
/**
 * 2017-2018 DM Productions B.V.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@dmp.nl so we can send you a copy immediately.
 *
 * @author     Michael Dekker <info@mijnpresta.nl>
 * @copyright  2010-2018 DM Productions B.V.
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    return;
}

require_once dirname(__FILE__).'/../../myparcelbpost.php';

/**
 * Class MyParcelBPostmyparcelcheckoutModuleFrontController
 *
 * @since 2.0.0
 */
class MyParcelBPostmyparcelcheckoutModuleFrontController extends ModuleFrontController
{
    const BASE_URI = 'https://api.myparcel.nl/delivery_options';

    /** @var MPBpostCarrierDeliverySetting $mpBpostCarrierDeliverySetting */
    protected $mpBpostCarrierDeliverySetting;

    /**
     * MyParcelmyparcelcheckoutModuleFrontController constructor.
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * Initialize content
     *
     * @return string
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     * @throws SmartyException
     */
    public function initContent()
    {
        if (!Configuration::get(MyParcelBpost::API_KEY)) {
            exit;
        }

        if (Tools::isSubmit('ajax')) {
            $this->getDeliveryOptions();

            return;
        }

        $context = Context::getContext();

        /** @var Cart $cart */
        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            $this->hideMe();
        }

        $address = new Address((int) $cart->id_address_delivery);

        if (!preg_match(MyParcelBpost::SPLIT_STREET_REGEX, MPBpostTools::getAddressLine($address), $m)) {
            // No house number
            $this->hideMe();
        }

        $streetName = isset($m['street']) ? $m['street'] : '';
        $houseNumber = isset($m['street_suffix']) ? $m['street_suffix'] : '';

        // id_carrier is not defined in database before choosing a carrier,
        // set it to a default one to match a potential cart _rule
        if (empty($cart->id_carrier) ||
            !in_array(
                $cart->id_carrier,
                array_filter(explode(',', Cart::desintifier($cart->simulateCarrierSelectedOutput())))
            )) {
            $checked = $cart->simulateCarrierSelectedOutput();
            $checked = ((int) Cart::desintifier($checked));
            $cart->id_carrier = $checked;
            $cart->update();
            CartRule::autoRemoveFromCart($this->context);
            CartRule::autoAddToCart($this->context);
        }

        $carrier = new Carrier($cart->id_carrier);
        if (!Validate::isLoadedObject($carrier)) {
            $this->hideMe();
        }

        $this->mpBpostCarrierDeliverySetting =
            MPBpostCarrierDeliverySetting::getByCarrierReference($carrier->id_reference);
        if (!$this->mpBpostCarrierDeliverySetting
            || !Validate::isLoadedObject($this->mpBpostCarrierDeliverySetting)
        ) {
            $this->hideMe();
        }

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>')
            && !($this->mpBpostCarrierDeliverySetting->delivery || $this->mpBpostCarrierDeliverySetting->pickup)
        ) {
            $this->hideMe();
        }

        $cutoffTimes = $this->mpBpostCarrierDeliverySetting->getCutOffTimes(
            date('Y-m-d'),
            MPBpostCarrierDeliverySetting::ENUM_DELIVERY
        );
        if (isset($cutoffTimes[0]['time'])) {
            $cutoffTime = $cutoffTimes[0]['time'];
        } else {
            $cutoffTime = MPBpostCarrierDeliverySetting::DEFAULT_CUTOFF;
        }

        $countryIso = Tools::strtolower(Country::getIsoById($address->id_country));
        if (!in_array($countryIso, array('nl', 'be'))) {
            $this->hideMe();
        }

        // Calculate the conversion to make before displaying prices
        // It is comprised of taxes and currency conversions
        /** @var Currency $defaultCurrency */
        $defaultCurrency = Currency::getCurrencyInstance(Configuration::get('PS_CURRENCY_DEFAULT'));
        /** @var Currency $currentCurrency */
        $currentCurrency = $this->context->currency;
        $conversion = $defaultCurrency->conversion_rate * $currentCurrency->conversion_rate;
        // Extra costs are entered with 21% VAT
        $conversion /= 1.21;

        // Calculate tax rate
        $useTax = (Group::getPriceDisplayMethod($this->context->customer->id_default_group) == PS_TAX_INC)
            && Configuration::get('PS_TAX');
        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if ($useTax) {
                $conversion *= (1 + $cart->getAverageProductsTaxRate());
            }
        } else {
            if ($useTax && $carrier->getTaxesRate($address)) {
                $conversion *= (1 + ($carrier->getTaxesRate($address) / 100));
            }
        }

        $smartyVars = array(
            'base_dir_ssl'                  => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
                    .Tools::getShopDomainSsl().__PS_BASE_URI__,
            'streetName'                    => $streetName,
            'houseNumber'                   => $houseNumber,
            'postcode'                      => $address->postcode,
            'langIso'                       => Tools::strtolower(Context::getContext()->language->iso_code),
            'language_code'                 => Context::getContext()->language->language_code,
            'currencyIso'                   => Tools::strtolower(Context::getContext()->currency->iso_code),
            'countryIso'                    => $countryIso,
            'delivery'                      => (bool) $this->useTimeframes(),
            'daytime'                       => (bool) $this->useTimeframes(),
            'pickup'                        => (bool) $this->mpBpostCarrierDeliverySetting->pickup,
            'pickupFeeTaxIncl'              => ($carrier->is_free)
                ? 0
                : (float) $this->mpBpostCarrierDeliverySetting->pickup_fee_tax_incl * $conversion,
            'deliveryDaysWindow'            => 8,
            'saturdayDelivery'              => (bool) $this->mpBpostCarrierDeliverySetting->saturday_delivery,
            'saturdayDeliveryFeeTaxIncl'    => ($carrier->is_free)
                    ? 0
                    : (float) $this->mpBpostCarrierDeliverySetting->saturday_delivery_fee_tax_incl * $conversion,
            'signed'                        => (bool) $this->mpBpostCarrierDeliverySetting->signed,
            'signedFeeTaxIncl'              => ($carrier->is_free)
                    ? 0
                    : (float) $this->mpBpostCarrierDeliverySetting->signed_fee_tax_incl * $conversion,
            'fontFamily'                    => Configuration::get(MyParcelBpost::CHECKOUT_FONT) ?: 'Exo',
            'fontSize'                      => (int) Configuration::get(MyParcelBpost::CHECKOUT_FONT_SIZE),
            'mypaBpostCheckoutJs'           => Media::getJSPath(_PS_MODULE_DIR_.'myparcelbpost/views/js/dist/front-cbfee8e3cbc20b2b.bundle.min.js'),
            'link'                          => $context->link,
            'foreground1color'              => Configuration::get(MyParcelBpost::CHECKOUT_FG_COLOR1),
            'foreground2color'              => Configuration::get(MyParcelBpost::CHECKOUT_FG_COLOR2),
            'foreground3color'              => Configuration::get(MyParcelBpost::CHECKOUT_FG_COLOR3),
            'background1color'              => Configuration::get(MyParcelBpost::CHECKOUT_BG_COLOR1),
            'background2color'              => Configuration::get(MyParcelBpost::CHECKOUT_BG_COLOR2),
            'highlightcolor'                => Configuration::get(MyParcelBpost::CHECKOUT_HL_COLOR),
            'inactiveColor'                 => Configuration::get(MyParcelBpost::CHECKOUT_INACTIVE_COLOR),
            'fontfamily'                    => Configuration::get(MyParcelBpost::CHECKOUT_FONT),
            'dropoffDelay'                  => (int) $this->mpBpostCarrierDeliverySetting->dropoff_delay,
            'dropoffDays'                   => implode(
                ';',
                $this->mpBpostCarrierDeliverySetting->getDropoffDays(date('Y-m-d H:i:s'))
            ),
            'cutoffTime'                    => $cutoffTime,
            'signedPreferred'               =>
                (bool) Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_SIGNED),
            'recipientOnlyPreferred'        =>
                (bool) Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY),
            'mpbpost_ajax_checkout_link'    => $this->context->link->getModuleLink(
                'myparcelbpost',
                'myparcelcheckout',
                array('ajax' => true),
                Tools::usingSecureMode()
            ),
            'mpbpost_deliveryoptions_link'  => $this->context->link->getModuleLink(
                'myparcelbpost',
                'deliveryoptions',
                array(),
                Tools::usingSecureMode()
            ),
            'mpbCheckoutFont'               => Configuration::get(MyParcelBpost::CHECKOUT_FONT),
            'mpbAsync'                      => (bool) Configuration::get(MyParcelBpost::DEV_MODE_ASYNC),
        );
        $cacheKey = md5(
            mypa_json_encode($smartyVars)
            .$this->mpBpostCarrierDeliverySetting->getCutoffExceptionsHash()
            .$carrier->id
        );
        $this->context->smarty->assign(
            array_merge(
                $smartyVars,
                array('cacheKey' => $cacheKey)
            )
        );

        echo $context->smarty->fetch(_PS_MODULE_DIR_.'myparcelbpost/views/templates/front/myparcelcheckout.tpl');
        die();
    }

    /**
     * Use timeframes
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function useTimeframes()
    {
        if (!$this->context->cart->checkQuantities()) {
            return false;
        }

        if (!$this->mpBpostCarrierDeliverySetting->delivery) {
            return false;
        }

        return true;
    }

    /**
     * Get delivery options
     * (API Proxy)
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function getDeliveryOptions()
    {
        header('Content-Type: application/json;charset=utf-8');
        if (!Tools::isSubmit('ajax')) {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        // @codingStandardsIgnoreStart
        $input = file_get_contents('php://input');
        // @codingStandardsIgnoreEnd
        $request = @json_decode($input, true);
        if (!$request) {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        $allowedParams = array(
            'cc',
            'postal_code',
            'number',
            'carrier',
            'delivery_time',
            'delivery_date',
            'cutoff_time',
            'dropoff_days',
            'dropoff_delay',
            'deliverydays_window',
            'exclude_delivery_type',
            'saturday_delivery',
        );

        $query = array();
        foreach ($allowedParams as &$param) {
            if (!isset($request[$param])) {
                continue;
            }
            if ($param === 'exclude_delivery_type') {
                if (empty($request[$param])) {
                    continue;
                }
            }

            $value = $request[$param];
            if ($param === 'number') {
                $value = (int) preg_replace('/[^\d]*(\d+).*$/', '$1', $value);
            }

            $query[$param] = $value;
        }

        $url = static::BASE_URI.'?'.http_build_query($query);
        $requestHeaders = array();
        $requestHeaders[] = trim(MyParcelBpost::getUserAgent());

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        die(mypa_json_encode(array(
            'success'  => true,
            'response' => @json_decode($response),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Hide the iframe
     *
     * @return void
     * @throws SmartyException
     */
    protected function hideMe()
    {
        echo Context::getContext()->smarty->fetch(_PS_MODULE_DIR_.'myparcelbpost/views/templates/front/removeiframe.tpl');
        die();
    }
}

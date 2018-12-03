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

require_once dirname(__FILE__).'/vendor/autoload.php';

/**
 * Class MyParcelBpost
 *
 * @since 1.0.0
 */
class MyParcelBpost extends Module
{
    const MENU_MAIN_SETTINGS = 0;
    const MENU_DEFAULT_SETTINGS = 1;
    const MENU_DEFAULT_DELIVERY_OPTIONS = 2;

    const BPOST_DEFAULT_CARRIER = 'MPBPOST_DEFAULT_CARRIER';
    const MYPARCEL_BASE_URL = 'https://www.sendmyparcel.be/';
    const SUPPORTED_COUNTRIES_URL = 'https://backoffice.myparcel.nl/api/system_country_codes';

    const API_KEY = 'MPBPOST_API_KEY';
    const API_TIMEOUT = 20;

    const LINK_EMAIL = 'MPBPOST_LINK_EMAIL';
    const LINK_PHONE = 'MPBPOST_LINK_PHONE';
    const USE_PICKUP_ADDRESS = 'MPBPOST_USE_PICKUP_ADDRESS';

    const LABEL_DESCRIPTION = 'MPBPOST_LABEL_DESCRIPTION';
    const PAPER_SELECTION = 'MPBPOST_PAPER_SELECTION';
    const ASK_PAPER_SELECTION = 'MPBPOST_ASK_PAPER_SELECTION';

    const CHECKOUT_LIVE = 'MPBPOST_LIVE_CHECKOUT';
    const CHECKOUT_FG_COLOR1 = 'MPBPOST_CHECKOUT_FG_COLOR1';
    const CHECKOUT_FG_COLOR2 = 'MPBPOST_CHECKOUT_FG_COLOR2';
    const CHECKOUT_FG_COLOR3 = 'MPBPOST_CHECKOUT_FG_COLOR3';
    const CHECKOUT_BG_COLOR1 = 'MPBPOST_CHECKOUT_BG_COLOR1';
    const CHECKOUT_BG_COLOR2 = 'MPBPOST_CHECKOUT_BG_COLOR2';
    const CHECKOUT_HL_COLOR = 'MPBPOST_CHECKOUT_HL_COLOR';
    const CHECKOUT_INACTIVE_COLOR = 'MPBPOST_CHECKOUT_IA_COLOR';
    const CHECKOUT_FONT = 'MPBPOST_CHECKOUT_FONT';
    const CHECKOUT_FONT_SIZE = 'MPBPOST_CHECKOUT_FSIZE';

    const ENUM_NONE = 0;
    const ENUM_SAMEDAY = 1;
    const ENUM_DELIVERY = 2;
    const ENUM_DELIVERY_SELF_DELAY = 3;

    const DEFAULT_CONCEPT_PARCEL_TYPE = 'MPBPOST_DEFCON_PT';
    const DEFAULT_CONCEPT_LARGE_PACKAGE = 'MPBPOST_DEFCON_LP';
    const DEFAULT_CONCEPT_HOME_DELIVERY_ONLY = 'MPBPOST_DEFCON_HDO';
    const DEFAULT_CONCEPT_RETURN = 'MPBPOST_DEFCON_RETURN';
    const DEFAULT_CONCEPT_SIGNED = 'MPBPOST_DEFCON_S';
    const DEFAULT_CONCEPT_INSURED = 'MPBPOST_DEFCON_I';
    const SUPPORTED_COUNTRIES = 'MPBPOST_SUPPORTED';

    const INSURED_TYPE_500 = 3;
    const TYPE_PARCEL = 1;
    const TYPE_UNSTAMPED = 3;
    const TYPE_POST_OFFICE = 4;

    const WEBHOOK_CHECK_INTERVAL = 86400;
    const WEBHOOK_LAST_CHECK = 'MPBPOST_WEBHOOK_UPD';
    const WEBHOOK_ID = 'MPBPOST_WEBHOOK_ID'; //daily check

    const DEV_MODE_SET_VERSION = 'MPBPOST_SET_VERSION';
    const DEV_MODE_CHECK_WEBHOOKS = 'MPBPOST_CHECK_WEBHOOKS';
    const DEV_MODE_ASYNC = 'MYPARCEL_ASYNC'; // Force the same mode as the MyParcel NL module
    const DEV_MODE_HIDE_PREFERRED = 'MYPARCEL_HIDE_PREFERRED'; // Force the same mode as the MyParcel NL module

    const UPDATE_ORDER_STATUSES = 'MPBPOST_UPDATE_OS';
    const CONNECTION_ATTEMPTS = 3;
    const LOG_API = 'MPBPOST_LOG_API';

    const PRINTED_STATUS = 'MPBPOST_PRINTED_STATUS';
    const SHIPPED_STATUS = 'MPBPOST_SHIPPED_STATUS';
    const RECEIVED_STATUS = 'MPBPOST_RECEIVED_STATUS';
    const NOTIFICATIONS = 'MPBPOST_NOTIFS';
    const NOTIFICATION_MOMENT = 'MPBPOST_NOTIF_MOMENT';
    const MOMENT_SCANNED = 0;
    const MOMENT_PRINTED = 1;

    const FONT_SMALL = 1;
    const FONT_MEDIUM = 2;
    const FONT_LARGE = 3;

    const CACHE_OLD_VERSION_INSTALLED = 'MPBPOST_OLD_VERSION';

    // @codingStandardsIgnoreStart
    /**
     * Split street RegEx
     *
     * @author Reindert Vetter <reindert@myparcel.nl>
     * @author Richard Perdaan <richard@myparcel.nl>
     */
    const SPLIT_STREET_REGEX = '~(?P<street>.*?)\s(?P<street_suffix>(?P<number>[^\s]{1,8})\s?(?P<box_separator>bus?)?\s?(?P<box_number>\d{0,8}$))$~';

    /**
     * Address format regex
     *
     * This is a RegEx that can be used to grab the address fields from the AddressFormat object
     */
    const ADDRESS_FORMAT_REGEX = '~^(address1)(?: +([a-zA-Z0-9_]+))?(?: +([a-zA-Z0-9_]+))?~m';
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    /** @var array $cachedCarriers */
    protected static $cachedCarriers = array();
    /** @var int $id_carrier */
    public $id_carrier;
    /** @var Carrier $carrier */
    public $carrier;
    /** @var array $hooks */
    public $hooks = array(
        'displayCarrierList',
        'displayHeader',
        'displayBackOfficeHeader',
        'adminOrder',
        'orderDetail',
        'actionValidateOrder',
        'actionAdminOrdersListingFieldsModifier',
        'actionAdminLogsListingFieldsModifier',
        'registerGDPRConsent',
        'actionDeleteGDPRCustomer',
        'actionExportGDPRData',
    );
    /** @var array $statuses */
    protected $statuses = array();
    /** @var int $menu */
    protected $menu = self::MENU_MAIN_SETTINGS;
    /** @var string $baseUrl */
    protected $baseUrl;
    /** @var string $baseUrlWithoutToken */
    protected $baseUrlWithoutToken;
    // @codingStandardsIgnoreEnd

    /**
     * MyParcelBpost constructor.
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'myparcelbpost';
        $this->tab = 'shipping_logistics';
        $this->version = '2.2.2';
        $this->author = 'MyParcel BE';
        $this->module_key = 'c9bb3b85a9726a7eda0de2b54b34918d';
        $this->bootstrap = true;
        $this->controllers = array('myparcelcheckout', 'myparcelcheckoutdemo', 'deliveryoptions', 'hook');

        parent::__construct();

        if (!empty(Context::getContext()->employee->id)) {
            $this->baseUrlWithoutToken =
                Context::getContext()->link->getAdminLink('AdminModules', false)
                .'&'
                .http_build_query(
                    array(
                        'configure'   => $this->name,
                        'tab_module'  => $this->tab,
                        'module_name' => $this->name,
                    )
                );
            $this->baseUrl = Context::getContext()->link->getAdminLink('AdminModules', true)
                .'&'
                .http_build_query(array(
                    'configure'   => $this->name,
                    'module_name' => $this->name,
                    'tab_module'  => $this->tab,
                ));
        }

        $this->displayName = $this->l('MyParcel Belgium');
        $this->description = $this->l('Shipping made easy with SendMyParcel.be');
    }

    /**
     * @return void
     *
     * @throws PrestaShopException
     * @throws ErrorException
     */
    public function ajaxProcessCheckWebhooks()
    {
        $this->checkWebhooks();
    }

    /**
     * Check webhooks + update info
     *
     * @return void
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected function checkWebhooks()
    {
        $lastCheck = (int) Configuration::get(static::WEBHOOK_LAST_CHECK);
        $webHookId = trim(Configuration::get(static::WEBHOOK_ID));

        if ((time() > ($lastCheck + static::WEBHOOK_CHECK_INTERVAL)) || empty($webHookId)) {
            // Time to update webhooks
            $ch = curl_init('https://api.myparcel.nl/webhook_subscriptions/'.(string) $webHookId);
            // @codingStandardsIgnoreStart
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: basic ".base64_encode(Configuration::get(static::API_KEY)),
                trim(static::getUserAgent())
            ));
            // @codingStandardsIgnoreEnd
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $sslEnabled = (bool) Configuration::get('PS_SSL_ENABLED');
            $webhookUrl = Context::getContext()->link->getModuleLink(
                $this->name,
                'hook',
                array(),
                $sslEnabled,
                (int) Configuration::get('PS_LANG_DEFAULT')
            );
            $found = false;
            $idWebhook = (int) Configuration::get(static::WEBHOOK_ID);
            $data = @json_decode($response, true);
            if ($data) {
                if (isset($data['data']['webhook_subscriptions']) && is_array($data['data']['webhook_subscriptions'])) {
                    foreach ($data['data']['webhook_subscriptions'] as $subscription) {
                        if ((int) $subscription['id'] !== $idWebhook) {
                            continue;
                        } elseif ($subscription['url'] == $webhookUrl) {
                            $found = true;

                            break;
                        }
                    }
                }
            }

            if (!$found) {
                // @codingStandardsIgnoreStart
                $apiKey = base64_encode(Configuration::get(static::API_KEY));
                // @codingStandardsIgnoreEnd
                $ch = curl_init('https://api.myparcel.nl/webhook_subscriptions');
                curl_setopt(
                    $ch,
                    CURLOPT_HTTPHEADER,
                    array(
                        "Authorization: basic $apiKey",
                        'Content-Type: application/json;charset=utf-8',
                        trim(static::getUserAgent()),
                    )
                );
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                $postData = mypa_json_encode(array(
                    'data' => array(
                        'webhook_subscriptions' => array(
                            array(
                                'hook' => 'shipment_status_change',
                                'url'  => $webhookUrl,
                            ),
                        ),
                    ),
                ));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                $response = curl_exec($ch);
                curl_close($ch);

                if ($response) {
                    $data = @json_decode($response, true);
                    if (isset($data['data']['ids'][0]['id'])) {
                        Configuration::updateValue(static::WEBHOOK_ID, (int) $data['data']['ids'][0]['id']);
                    }
                }
            }

            Configuration::updateValue(static::WEBHOOK_LAST_CHECK, time());

            static::retrieveSupportedCountries();
        }
    }

    /**
     * Retrieve suported countries from MyParcel API
     *
     * @return bool|mixed|string Raw json or false if not found
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected static function retrieveSupportedCountries()
    {
        // Time to update country list
        $ch = curl_init(static::SUPPORTED_COUNTRIES_URL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $countries = curl_exec($ch);
        curl_close($ch);

        if (!$countries) {
            $countries = mypa_json_encode(MPBpostTools::getSupportedCountriesOffline());
        }

        $countries = @json_decode($countries, true);
        if (isset($countries['data']['countries'][0]['BE']['region'])) {
            $countries['data']['countries'][0]['BE']['region'] = 'EU';
        }
        if (isset($countries['data']['countries'][0]['NL']['region'])) {
            $countries['data']['countries'][0]['NL']['region'] = 'EU';
        }
        $countries = mypa_json_encode($countries);

        Configuration::updateValue(static::SUPPORTED_COUNTRIES, $countries);

        return $countries;
    }

    /**
     * Add error message
     *
     * @param string $message Message
     * @param bool   $private Only display on module's configuration page
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function addError($message, $private = true)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->errors[] = '<a href="'.$this->baseUrl.'">'
                    .$this->displayName.': '.$message.'</a>';
            }
        } else {
            // Do not add an error in this case
            // It will halt execution of the ModuleAdminController
            $this->context->controller->errors[] = $message;
        }
    }

    /**
     * Delete folder recursively
     *
     * @param string $dir Directory
     */
    protected function recursiveDeleteOnDisk($dir)
    {
        if (strpos(realpath($dir), realpath(_PS_MODULE_DIR_)) === false) {
            return;
        }
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->recursiveDeleteOnDisk($dir.'/'.$object);
                    } else {
                        @unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    /**
     * Check if shipment is sent on date
     *
     * @param int    $idMPBpostCarrierDeliverySetting MyParcel Delivery Option ID
     * @param string $date                            Date in European format d-m-Y
     *
     * @return bool Whether the store dispatches on this date
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getShipmentAvailableOnDay($idMPBpostCarrierDeliverySetting, $date = null)
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $deliveryOption = new MPBpostCarrierDeliverySetting($idMPBpostCarrierDeliverySetting);
        if (Validate::isLoadedObject($deliveryOption)) {
            $dayOfWeek = date('w', strtotime($date));

            $cutoffExceptions = $deliveryOption->cutoff_exceptions;

            if (!empty($cutoffExceptions)) {
                if (is_array($cutoffExceptions) && array_key_exists($date, $cutoffExceptions)) {
                    if (array_key_exists('cutoff', $cutoffExceptions[$date])) {
                        return true;
                    } elseif (array_key_exists('nodispatch', $cutoffExceptions[$date])
                        && $cutoffExceptions[$date]['nodispatch']
                    ) {
                        return false;
                    }
                }
            }

            switch ($dayOfWeek) {
                case 1:
                    return $deliveryOption->monday_enabled;
                case 2:
                    return $deliveryOption->tuesday_enabled;
                case 3:
                    return $deliveryOption->wednesday_enabled;
                case 4:
                    return $deliveryOption->thursday_enabled;
                case 5:
                    return $deliveryOption->friday_enabled;
                case 6:
                    return $deliveryOption->saturday_enabled;
            }
        }

        return false;
    }

    /**
     * Get Cut Off time on day
     *
     * @param int    $idMPBpostCarrierDeliverySetting MPBpostCarrierDeliverySetting ID
     * @param string $date                             Custom date
     *
     * @return bool|string Cut off time or false if no shipment on that day
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getCutOffTime($idMPBpostCarrierDeliverySetting, $date = null)
    {
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        $carrierDeliverySetting = new MPBpostCarrierDeliverySetting($idMPBpostCarrierDeliverySetting);
        if (Validate::isLoadedObject($carrierDeliverySetting)) {
            $dayOfWeek = date('w', strtotime($date));

            $cutoffExceptions = $carrierDeliverySetting->cutoff_exceptions;

            if (!empty($cutoffExceptions)) {
                if (is_array($cutoffExceptions) && array_key_exists($date, $cutoffExceptions)) {
                    if (array_key_exists('cutoff', $cutoffExceptions[$date])) {
                        return $cutoffExceptions[$date]['cutoff'];
                    } else {
                        return false;
                    }
                }
            }

            switch ($dayOfWeek) {
                case 1:
                    $cutoff = $carrierDeliverySetting->monday_cutoff;
                    break;
                case 2:
                    $cutoff = $carrierDeliverySetting->tuesday_cutoff;
                    break;
                case 3:
                    $cutoff = $carrierDeliverySetting->wednesday_cutoff;
                    break;
                case 4:
                    $cutoff = $carrierDeliverySetting->thursday_cutoff;
                    break;
                case 5:
                    $cutoff = $carrierDeliverySetting->friday_cutoff;
                    break;
                case 6:
                    $cutoff = $carrierDeliverySetting->saturday_cutoff;
                    break;
            }

            if (empty($cutoff)) {
                return false;
            } else {
                return $cutoff;
            }
        }

        return false;
    }

    /**
     * Installs the module
     *
     * @return bool Indicates whether the module has been successfully installed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     * @throws Adapter_Exception
     * @throws ReflectionException
     */
    public function install()
    {
        if (version_compare(PHP_VERSION, '5.3.3', '<')) {
            $this->addError(
                $this->l('Unable to install the MyParcel module. Please enable PHP 5.3.3 or higher.'),
                false
            );

            return false;
        }

        if (!function_exists('curl_init')) {
            $this->addError(
                $this->l('Unable to install the MyParcel module. Please enable the PHP cURL extension.'),
                false
            );

            return false;
        }

        if (!parent::install()) {
            return false;
        }

        if (!$this->installSql()) {
            parent::uninstall();

            return false;
        }

        $this->addCarrier('bpost', static::BPOST_DEFAULT_CARRIER);

        // On 1.7 only the hook `displayBeforeCarrier` works properly
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $index = array_search('displayCarrierList', $this->hooks);
            unset($this->hooks[$index]);
            $this->hooks[] = 'displayBeforeCarrier';
        }
        foreach ($this->hooks as $hook) {
            try {
                $this->registerHook($hook);
            } catch (PrestaShopException $e) {
            }
        }

        Configuration::updateValue(static::CHECKOUT_FG_COLOR1, '#FFFFFF');
        Configuration::updateValue(static::CHECKOUT_FG_COLOR2, '#000000');
        Configuration::updateValue(static::CHECKOUT_FG_COLOR3, '#000000');
        Configuration::updateValue(
            static::CHECKOUT_BG_COLOR1,
            version_compare(_PS_VERSION_, '1.7.0.0', '>=') ? 'transparent' : '#FBFBFB'
        );
        Configuration::updateValue(static::CHECKOUT_BG_COLOR2, '#01BBC5');
        Configuration::updateValue(static::CHECKOUT_HL_COLOR, '#FF8C00');
        Configuration::updateValue(static::CHECKOUT_INACTIVE_COLOR, '#848484');
        Configuration::updateValue(static::CHECKOUT_FONT, 'Exo');
        Configuration::updateValue(static::CHECKOUT_FONT_SIZE, 2);
        Configuration::updateValue(static::LABEL_DESCRIPTION, '{order.reference}');
        Configuration::updateValue(static::PRINTED_STATUS, 0);
        Configuration::updateValue(static::SHIPPED_STATUS, (int) Configuration::get('PS_OS_SHIPPING'));
        Configuration::updateValue(static::RECEIVED_STATUS, (int) Configuration::get('PS_OS_DELIVERED'));
        Configuration::updateValue(static::LINK_EMAIL, true);
        Configuration::updateValue(static::LINK_PHONE, true);
        Configuration::updateValue(static::USE_PICKUP_ADDRESS, true);
        Configuration::updateValue(static::NOTIFICATIONS, true);
        Configuration::updateValue(static::NOTIFICATION_MOMENT, static::MOMENT_SCANNED);
        Configuration::updateValue(static::PAPER_SELECTION, mypa_json_encode(array(
            'size' => 'standard',
            'labels' => array(
                1 => true,
                2 => true,
                3 => true,
                4 => true,
            ),
        )), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (method_exists('Tools', 'clearCache')) {
            Tools::clearCache();
        }

        return true;
    }

    /**
     * Install DB tables
     *
     * @return bool Indicates whether the DB tables have been successfully installed
     *
     * @throws PrestaShopException
     * @throws ReflectionException
     * @since 1.0.0
     */
    protected function installSql()
    {
        if (!(MPBpostCarrierDeliverySetting::createDatabase()
            && MPBpostDeliveryOption::createDatabase()
            && MPBpostOrder::createDatabase()
            && MPBpostOrderHistory::createDatabase())
        ) {
            $this->addError(Db::getInstance()->getMsgError(), false);
            $this->uninstallSql();

            return false;
        }
        try {
            Db::getInstance()->execute(
                'ALTER TABLE `'._DB_PREFIX_.bqSQL(MPBpostDeliveryOption::$definition['table'])
                .'` ADD CONSTRAINT `id_cart` UNIQUE (`id_cart`)'
            );
        } catch (Exception $e) {
            $this->addError("MyParcel installation error: {$e->getMessage()}", false);
        }

        return true;
    }

    /**
     * Remove DB tables
     *
     * @return bool Indicates whether the DB tables have been successfully uninstalled
     *
     * @throws ReflectionException
     * @since 1.0.0
     */
    protected function uninstallSql()
    {
        try {
            if (!(MPBpostCarrierDeliverySetting::dropDatabase())) {
                $this->addError(Db::getInstance()->getMsgError());

                return false;
            }
        } catch (PrestaShopException $e) {
            $this->addError($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Add a carrier
     *
     * @param string $name Carrier name
     * @param string $key  Carrier ID
     *
     * @return bool|Carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     * @throws Adapter_Exception
     */
    protected function addCarrier($name, $key = self::BPOST_DEFAULT_CARRIER)
    {
        $carrier = Carrier::getCarrierByReference(Configuration::get($key));
        if (Validate::isLoadedObject($carrier)) {
            return false; // Already added to DB
        }

        $carrier = new Carrier();

        $carrier->name = $name;
        $carrier->delay = array();
        $carrier->is_module = true;
        $carrier->active = 0;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 1;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_handling = false;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang) {
            $idLang = (int) $lang['id_lang'];
            $carrier->delay[$idLang] = '-';
        }

        if ($carrier->add()) {
            /*
             * Use the Carrier ID as id_reference! Only the `id` prop has been set at this time and since it is
             * the first time this carrier is used the Carrier ID = `id_reference`
             */
            $this->addGroups($carrier);
            $this->addZones($carrier);
            $this->addPriceRange($carrier);
            Db::getInstance()->update(
                'delivery',
                array(
                    'price' => $key == static::BPOST_DEFAULT_CARRIER ? (4.99 / 1.21) : (3.50 / 1.21),
                ),
                '`id_carrier` = '.(int) $carrier->id
            );

            $carrier->setTaxRulesGroup((int) TaxRulesGroup::getIdByName('NL Standard Rate (21%)'), true);

            @copy(
                dirname(__FILE__).'/views/img/bpost-thumb.jpg',
                _PS_SHIP_IMG_DIR_.DIRECTORY_SEPARATOR.(int) $carrier->id.'.jpg'
            );

            Configuration::updateGlobalValue($key, (int) $carrier->id);
            $deliverySetting = new MPBpostCarrierDeliverySetting();
            $deliverySetting->id_reference = $carrier->id;

            $deliverySetting->monday_cutoff = '15:30:00';
            $deliverySetting->tuesday_cutoff = '15:30:00';
            $deliverySetting->wednesday_cutoff = '15:30:00';
            $deliverySetting->thursday_cutoff = '15:30:00';
            $deliverySetting->friday_cutoff = '15:30:00';
            $deliverySetting->saturday_cutoff = '15:30:00';
            $deliverySetting->sunday_cutoff = '15:30:00';
            $deliverySetting->saturday_delivery = false;
            $deliverySetting->signed = false;
            $deliverySetting->dropoff_delay = 0;
            $deliverySetting->id_shop = $this->context->shop->id;
            $deliverySetting->default_fee_tax_incl = 0;
            $deliverySetting->signed_fee_tax_incl = 0;
            if ($key === static::BPOST_DEFAULT_CARRIER) {
                $deliverySetting->monday_enabled = true;
                $deliverySetting->tuesday_enabled = true;
                $deliverySetting->wednesday_enabled = true;
                $deliverySetting->thursday_enabled = true;
                $deliverySetting->friday_enabled = true;
                $deliverySetting->saturday_enabled = false;

                $deliverySetting->delivery = true;
                $deliverySetting->pickup = true;
            } else {
                $deliverySetting->monday_enabled = true;
                $deliverySetting->tuesday_enabled = true;
                $deliverySetting->wednesday_enabled = true;
                $deliverySetting->thursday_enabled = true;
                $deliverySetting->friday_enabled = true;
                $deliverySetting->saturday_enabled = false;

                $deliverySetting->pickup = false;
                $deliverySetting->delivery = false;
            }
            try {
                $deliverySetting->add();
            } catch (PrestaShopException $e) {
                Logger::addLog(
                    sprintf(
                        $this->l('MyParcel: unable to save carrier settings for carrier with reference %d'),
                        $carrier->id
                    )
                );
            }

            return $carrier;
        }

        return false;
    }

    /**
     * Uninstalls the module
     *
     * @return bool Indicates whether the module has been successfully uninstalled
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function uninstall()
    {
        Configuration::deleteByName(static::API_KEY);

        foreach ($this->hooks as $hook) {
            $this->unregisterHook($hook);
        }

        Db::getInstance()->update(
            bqSQL(Carrier::$definition['table']),
            array(
                'is_module'            => false,
                'shipping_external'    => false,
                'external_module_name' => null,
            ),
            '`external_module_name` = \'myparcelbpost\'',
            0,
            true
        );

        if (parent::uninstall() === false) {
            return false;
        }

        return true;
    }

    /**
     * Diplay Order detail on Front Office
     *
     * @param array $params Hook parameters
     *
     * @return string HTML
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    public function hookDisplayOrderDetail($params)
    {
        $this->context->smarty->assign(
            array(
                'shipments'   => MPBpostOrderHistory::getShipmentHistoryByOrderId($params['order']->id),
                'languageIso' => Tools::strtoupper($this->context->language->iso_code),
            )
        );

        return $this->display(__FILE__, 'views/templates/front/orderdetail.tpl');
    }

    /**
     * Adds JavaScript files to back office
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (!Module::isEnabled($this->name)) {
            return '';
        }
        $html = '';
        if (Tools::getValue('controller') === 'AdminOrders'
            && !Tools::isSubmit('addorder')
            && !Tools::isSubmit('updateorder')
            && !Tools::isSubmit('vieworder')
        ) {
            $countries = array();
            $supportedCountries = static::getSupportedCountries();
            if (isset($supportedCountries['data']['countries'][0])) {
                $euCountries = array_map(function ($item) {
                    $values = array_values($item);

                    return Tools::strtoupper($values[0]);
                }, static::getEUCountries());
                foreach (array_keys($supportedCountries['data']['countries'][0]) as $iso) {
                    if (Tools::strtoupper($iso) === 'BE') {
                        continue;
                    }

                    if (!in_array(Tools::strtoupper($iso), $euCountries)) {
                        $supportedCountries['data']['countries'][0][$iso]['region'] = 'CD';
                    } else {
                        $supportedCountries['data']['countries'][0][$iso]['region'] = 'EU';
                    }
                }
                $countryIsos = array_keys($supportedCountries['data']['countries'][0]);
                foreach (Country::getCountries($this->context->language->id) as &$country) {
                    if (in_array(Tools::strtoupper($country['iso_code']), $countryIsos)) {
                        $countries[Tools::strtoupper($country['iso_code'])] = array(
                            'iso_code' => Tools::strtoupper($country['iso_code']),
                            'name'     => $country['name'],
                            'region'   => $supportedCountries['data']['countries'][0]
                                          [Tools::strtoupper($country['iso_code'])]['region'],
                        );
                    }
                }
            }

            $lastCheck = (int) Configuration::get(static::WEBHOOK_LAST_CHECK);
            $webHookId = trim(Configuration::get(static::WEBHOOK_ID));
            $this->context->smarty->assign(
                array(
                    'mpbProcessUrl'    => $this->baseUrlWithoutToken.'&token='.Tools::getAdminTokenLite('AdminModules').'&ajax=1',
                    'mpbModuleDir'     => __PS_BASE_URI__."modules/{$this->name}/",
                    'mpbJsCountries'   => $countries,
                    'mpbPaperSize'     => @json_decode(Configuration::get(static::PAPER_SELECTION)),
                    'mpbAskPaperSize'  => (bool) Configuration::get(static::ASK_PAPER_SELECTION),
                    'mpbCheckWebhooks' => (time() > ($lastCheck + static::WEBHOOK_CHECK_INTERVAL)) || empty($webHookId),
                    'mpbLogApi'        => (bool) Configuration::get(static::LOG_API),
                    'mpbAsync'         => (bool) Configuration::get(static::DEV_MODE_ASYNC),
                    'mpbCurrency'      => Context::getContext()->currency,
                )
            );
            $html .= $this->display(__FILE__, 'views/templates/admin/ordergrid/adminvars.tpl');

            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path.'views/js/dist/back-cbfee8e3cbc20b2b.bundle.min.js');
            $this->context->controller->addCSS($this->_path.'views/css/forms.css');
        } elseif (Tools::getValue('controller') == 'AdminModules'
            && Tools::getValue('configure') == $this->name
        ) {
            $this->context->controller->addJquery();
            $this->context->controller->addJqueryUI('datepicker-nl');
            $this->context->controller->addCSS($this->_path.'views/css/forms.css');

            $this->context->smarty->assign(
                array(
                    'current_lang_iso' => Tools::strtolower(Language::getIsoById($this->context->language->id)),
                )
            );

            $html .= $this->display(__FILE__, 'views/templates/hook/initdeliverysettings.tpl');
        }

        return $html;
    }

    /**
     * Get supported counties
     *
     * @return array|bool Supported countries as associative array
     *                    false if not found
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    public static function getSupportedCountries()
    {
        $supportedCountries = @json_decode(
            Configuration::get(static::SUPPORTED_COUNTRIES),
            true
        );
        if (!$supportedCountries) {
            if ($supportedCountries = static::retrieveSupportedCountries()) {
                $supportedCountries = @json_decode($supportedCountries, true);
            }
        }

        return $supportedCountries;
    }

    /**
     * Get EU countries
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function getEUCountries()
    {
        return MPBpostTools::getEUCountriesOffline();
    }

    /**
     * Configuration Page: get content of the form
     *
     * @return string Configuration page HTML
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 1.0.0
     */
    public function getContent()
    {
        $this->baseUrl = Context::getContext()->link->getAdminLink('AdminModules', false).'?'
            .http_build_query(array('configure' => $this->name, 'module_name' => $this->name));
        $this->baseUrlWithoutToken = $this->baseUrl.'&token='.Tools::getAdminTokenLite('AdminModules');

        if (Tools::getValue('demo')) {
            MPBpostDemo::renderDemo();
        }
        if (Tools::getValue('upgrade-myparcel') && static::isOldVersionInstalled(true)) {
            MPBpostTools::upgradeToNew();
            Tools::redirectAdmin($this->baseUrl.'&token='.Tools::getAdminTokenLite('AdminModules'));
        }

        $this->context->smarty->assign(
            array(
                'menutabs' => $this->initNavigation(),
                'ajaxUrl'  => $this->baseUrlWithoutToken,
            )
        );

        foreach ($this->basicCheck() as $error) {
            $this->context->controller->errors[] = $error;
        }

        $output = '';

        $this->postProcess();

        $output .= $this->display(__FILE__, 'views/templates/admin/navbar.tpl');

        $this->context->controller->addJquery();
        $this->context->controller->addJS($this->_path.'views/js/back.js');

        switch (Tools::getValue('menu')) {
            case static::MENU_DEFAULT_SETTINGS:
                $this->menu = static::MENU_DEFAULT_SETTINGS;
                $output .= $this->display(__FILE__, 'views/templates/admin/insuredconf.tpl');

                return $output.$this->displayDefaultSettingsForm();
            case static::MENU_DEFAULT_DELIVERY_OPTIONS:
                $this->menu = static::MENU_DEFAULT_DELIVERY_OPTIONS;

                return $output.$this->displayDeliveryOptionsPage();
            default:
                $this->menu = static::MENU_MAIN_SETTINGS;

                return $output.$this->displayMainSettingsPage();
        }
    }

    /**
     * Get the user agent string to be attached to API calls
     *
     * @return string
     */
    public static function getUserAgent()
    {
        if (defined('_TB_VERSION_')) {
            return 'thirty bees/'._TB_VERSION_;
        }

        return 'PrestaShop/'._PS_VERSION_;
    }

    /**
     * @param stdClass $response
     * @param int[]    $idOrders
     * @param array    $concepts
     *
     * @return array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.1.0
     */
    protected function processNewLabels($response, $idOrders, $concepts)
    {
        $response = mypa_dot($response);
        $processedLabels = array();

        $i = 0;
        if (is_array($response->get('data.ids'))) {
            foreach ($response->get('data.ids') as $idShipment) {
                $idShipment = (int) $idShipment['id'];
                $idOrder = (int) $idOrders[$i];

                $myparcelOrder = new MPBpostOrder();
                $myparcelOrder->id_order = $idOrder;
                $myparcelOrder->id_shipment = $idShipment;
                $myparcelOrder->mpbpost_status = '1';
                $myparcelOrder->retour = false;
                $myparcelOrder->postcode = mypa_dot($concepts)->get("$i.concept.recipient.postal_code");
                $myparcelOrder->mpbpost_final = false;
                $myparcelOrder->shipment = mypa_json_encode(mypa_dot($concepts)->get("$i.concept"));
                if (!mypa_dot($concepts)->isEmpty("$i.concept.pickup")) {
                    $myparcelOrder->type = static::TYPE_POST_OFFICE;
                } elseif (!mypa_dot($concepts)->isEmpty("$i.concept.option.delivery_type")) {
                    $myparcelOrder->type = mypa_dot($concepts)->get("$i.concept.option.delivery_type");
                } else {
                    $myparcelOrder->type = static::TYPE_PARCEL;
                }

                $myparcelOrder->add();

                $processedLabel = $myparcelOrder->getFields();
                $processedLabel['shipment'] = $concepts[$i]['concept'];
                $processedLabel[MPBpostOrder::$definition['primary']] = (int) $myparcelOrder->id;
                $processedLabels[] = $processedLabel;

                $i++;
            }
        }

        return $processedLabels;
    }

    /**
     * Retrieve order info
     *
     * @since 2.0.0
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessOrderInfo()
    {
        if (!$this->active) {
            header('Content-Type: text/plain;charset=utf-8');
            if (function_exists('http_response_code')) {
                http_response_code(404);
            } else {
                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                header("$protocol 404 Not Found");
            }
            die('MyParcel module has been disabled');
        }

        header('Content-Type: application/json;charset=utf-8');
        // @codingStandardsIgnoreStart
        $payload = @json_decode(file_get_contents('php://input'), true);
        // @codingStandardsIgnoreEnd
        $orderIds = $payload['ids'];

        // Retrieve customer preferences
        die(
            mypa_json_encode(
                array(
                    'preAlerted' => MPBpostOrder::getByOrderIds($orderIds),
                    'concepts'   => MPBpostDeliveryOption::getByOrderIds($orderIds),
                )
            )
        );
    }

    /**
     * Get delivery options (BO)
     *
     * @throws ErrorException
     * @throws PrestaShopException
     *
     * @since 2.2.0
     */
    public function ajaxProcessDeliveryOptions()
    {
        $input = file_get_contents('php://input');
        $request = @json_decode($input, true);
        if (!$request) {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }
        $request = array_merge($request, array(
            'carrier'           => 2,
            'cutoff_time'       => '23:59:00',
            'saturday_delivery' => 1,
            'dropoff_days'      => '0;1;2;3;4;5;6',
        ));

        $allowedParams = array(
            'cc',
            'postal_code',
            'number',
            'carrier',
            'cutoff_time',
            'monday_delivery',
            'dropoff_days',
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

        $curl = \MPBpostModule\MPBpostHttpClient::getInstance();
        $url = 'https://api.myparcel.nl/delivery_options?'.http_build_query($query);
        $response = $curl->get($url);
        if (!$response) {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        header('Content-Type: application/json;charset=utf-8');
        die(mypa_json_encode(array(
            'success'  => true,
            'response' => $response,
        )));
    }

    /**
     * @throws PrestaShopException
     *
     * @since 2.0.0
     * @throws Adapter_Exception
     * @throws ErrorException
     */
    public function ajaxProcessGetShipment()
    {
        $requestBody = @json_decode(file_get_contents('php://input'), true);
        $curl = \MPBpostModule\MPBpostHttpClient::getInstance();

        if ($requestBody) {
            $moduleData = new stdClass();
            if (isset($requestBody['moduleData'])) {
                $moduleData = $requestBody['moduleData'];
                unset($requestBody['moduleData']);
            }
            $requestBody = mypa_json_encode($requestBody);
        } else {
            $moduleData = null;
        }

        if (is_array($requestBody)) {
            unset($requestBody['controller']);
            unset($requestBody['token']);
            unset($requestBody['controllerUri']);
        }

        $shipments = implode(';', $moduleData['shipments']);
        $responseContent = $curl->get("https://api.myparcel.nl/shipments/{$shipments}");

        $this->getShipmentApiInterceptor($responseContent);

        $response = mypa_dot($responseContent);
        foreach ($response->get('data.shipments') as $index => $shipment) {
            $newShipment = array();
            $newShipment['id_shipment'] = (int) $shipment['id'];
            $mypaOrder = MPBpostOrder::getByShipmentId($shipment['id']);
            if (Validate::isLoadedObject($mypaOrder)) {
                $order = new Order($mypaOrder->id_order);
                $state = new OrderState($order->getCurrentState(), $this->context->language->id);

                $newShipment['id_order'] = (int) $mypaOrder->id_order;
                $newShipment['backgroundColor'] = $state->color;
                $newShipment['color'] = Tools::getBrightness($state->color) < 128 ? '#ffffff' : '#383838';
                $newShipment['state_text'] = $state->name;
                $newShipment['date_upd'] = $mypaOrder->date_upd;
            } else {
                $newShipment['id_order'] = null;
                $newShipment['backgroundColor'] = null;
                $newShipment['color'] = null;
                $newShipment['state_text'] = null;
                $newShipment['date_upd'] = date('Y-m-d H:i:s', strtotime($shipment['created']));
            }
            $newShipment['postcode'] = $shipment['recipient']['postal_code'];
            $newShipment['tracktrace'] = $shipment['barcode'];
            $newShipment['mpbpost_status'] = $shipment['status'];
            $newShipment['mpbpost_final'] = $shipment['status'] >= 7;
            $newShipment['shipment'] = $shipment;
            $response->set("data.shipments.{$index}", $newShipment);
        }

        // finally, output the content
        header('Content-Type: application/json;charset=utf-8');
        die(mypa_json_encode($response->jsonSerialize()));
    }

    /**
     * Delete shipment
     *
     * @since 2.1.0
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessDeleteShipment()
    {
        // @codingStandardsIgnoreStart
        $request = @json_decode(file_get_contents('php://input'), true);
        // @codingStandardsIgnoreEnd
        if (isset($request['idShipment'])) {
            $idShipment = (int) $request['idShipment'][0];
            header('Content-Type: application/json;charset=utf-8');
            die(mypa_json_encode(array(
                'success' => MPBpostOrder::deleteShipment($idShipment),
            )));
        }
    }

    /**
     * Intercept Get Shipment API calls
     *
     * @param string $responseContent
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     * @throws ErrorException
     */
    protected function getShipmentApiInterceptor($responseContent)
    {
        if ($responseContent) {
            if (!is_array($responseContent)) {
                $responseContent = @json_decode($responseContent, true);
            }
            if (is_array(mypa_dot($responseContent)->get('data.shipments'))) {
                foreach (mypa_dot($responseContent)->get('data.shipments') as $shipment) {
                    $myparcelOrder = MPBpostOrder::getByShipmentId($shipment['id']);
                    if (Validate::isLoadedObject($myparcelOrder)) {
                        if (mypa_dot($shipment)->get('barcode')) {
                            MPBpostOrder::updateStatus(
                                $myparcelOrder->id_shipment,
                                $shipment['barcode'],
                                $shipment['status'],
                                $shipment['modified']
                            );
                            if (!$myparcelOrder->tracktrace) {
                                MPBpostOrder::updateOrderTrackingNumber($myparcelOrder->id_order, $shipment['barcode']);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws PrestaShopException
     * @throws Adapter_Exception
     * @throws ErrorException
     */
    public function ajaxProcessCreateLabel()
    {
        $curl = \MPBpostModule\MPBpostHttpClient::getInstance();
        $curl->setHeader('Accept', 'application/json;charset=utf-8');
        $curl->setHeader('Content-Type', 'application/vnd.shipment+json;charset=utf-8');
        $request = @json_decode(file_get_contents('php://input'), true);
        $idOrders = $shipments = array();
        if (is_array(mypa_dot($request)->get('moduleData.shipments'))) {
            foreach (mypa_dot($request)->get('moduleData.shipments') as $shipment) {
                $idOrders[] = (int) $shipment['idOrder'];
                $filteredConcept = MPBpostDeliveryOption::filterConcept($shipment['concept']);
                if (!empty($filteredConcept['options']['delivery_date'])) {
                    $filteredConcept['options']['saturday_delivery'] = (int) (date('w', strtotime($filteredConcept['options']['delivery_date'])) === 6);
                    unset($filteredConcept['options']['delivery_date']);
                }
                $shipments[] = $filteredConcept;
            }
        } else {
            header('Content-Type: application/json;charset=utf-8');
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        if (is_array($request)) {
            unset($request['controller']);
            unset($request['token']);
            unset($request['controllerUri']);
        }

        $response = $curl->post('https://api.myparcel.nl/shipments', mypa_json_encode(array(
            'data' => array(
                'shipments' => $shipments,
            ),
        )));

        header('Content-Type: application/json;charset=utf-8');
        if ($response) {
            $labelData = $this->processNewLabels($response, $idOrders, mypa_dot($request)->get('moduleData.shipments'));
            if (empty($labelData)) {
                die(mypa_json_encode($response));
            }

            die(mypa_json_encode($labelData));
        }

        die(mypa_json_encode(array(
            'success' => false,
        )));
    }

    /**
     * Print label
     *
     * @return void
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     * @throws ErrorException
     */
    public function ajaxProcessPrintLabel()
    {
        header('Content-Type: application/json;charset=utf-8');
        $curl = \MPBpostModule\MPBpostHttpClient::getInstance();
        $curl->setHeader('Accept', 'application/json;charset=utf-8');
        $requestBody = file_get_contents('php://input');
        $request = @json_decode($requestBody, true);
        if (is_array($request) && array_key_exists('idShipments', $request)) {
            $idShipments = $request['idShipments'];
            $shipments = implode(';', $idShipments);
        } else {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        if (is_array($requestBody)) {
            unset($requestBody['controller']);
            unset($requestBody['token']);
            unset($requestBody['controllerUri']);
        }

        $request = @json_decode(file_get_contents('php://input'), true);
        $positions = implode(';', array(1, 2, 3, 4));
        $pageSize = 'A4';
        if (isset($request['paperSize'])) {
            $pageSize = $request['paperSize']['size'] === 'standard' ? 'A4' : 'A6';
            $positions = array();
            foreach ($request['paperSize']['labels'] as $index => $pos) {
                if ($pos) {
                    $positions[] = $index;
                }
            }
            $positions = implode(';', $positions);
        }

        $response = $curl->get("https://api.myparcel.nl/shipment_labels/{$shipments}?positions={$positions}&format={$pageSize}");
        if ($response) {
            $response['success'] = true;
            foreach ($idShipments as $idShipment) {
                $mpo = MPBpostOrder::getByShipmentId($idShipment);
                if (!Validate::isLoadedObject($mpo)) {
                    $response['success'] = false;
                } else {
                    $response['success'] &= $mpo->printed();
                }
            }

            die(mypa_json_encode($response));
        }

        die(mypa_json_encode(array(
            'success' => false,
        )));
    }

    /**
     * @return void
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws ErrorException
     */
    public function ajaxProcessCreateRelatedReturnLabel()
    {
        header('Content-Type: application/json;charset=utf-8');
        $request = @json_decode(file_get_contents('php://input'), true);
        if (isset($request['moduleData']['parent'])) {
            $parent = (int) $request['moduleData']['parent'];
        } else {
            die(mypa_json_encode(array(
                'success' => false,
            )));
        }

        $sql = new DbQuery();
        $sql->select('c.`firstname`, c.`lastname`, c.`email`, mo.`id_shipment`, mo.`postcode`, o.`id_order`');
        $sql->from(bqSQL(MPBpostOrder::$definition['table']), 'mo');
        $sql->innerJoin(bqSQL(Order::$definition['table']), 'o', 'o.`id_order` = mo.`id_order`');
        $sql->innerJoin(bqSQL(Customer::$definition['table']), 'c', 'c.`id_customer` = o.`id_customer`');
        $sql->where('`id_shipment` = '.$parent);
        try {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        } catch (PrestaShopException $e) {
            $result = false;
        }

        if (!$result) {
            die(mypa_json_encode(array(
                'success' => false,
                'error'   => 'No shipments found in db',
            )));
        }

        // @codingStandardsIgnoreStart
        $curl = \MPBpostModule\MPBpostHttpClient::getInstance();
        $curl->setHeader('Content-Type', 'application/vnd.return_shipment+json;charset=utf-8');
        $response = $curl->post('https://api.myparcel.nl/shipments', mypa_json_encode(array(
            'data' => array(
                'return_shipments' => array(
                    array(
                        'parent'  => $parent,
                        'carrier' => 1,
                        'name'    => $result['firstname'].' '.$result['lastname'],
                        'email'   => $result['email'],
                        'options' => array(
                            'package_type'   => 1,
                            'only_recipient' => 0,
                            'signature'      => 0,
                            'return'         => 0,
                            'insurance'      => array(
                                'amount'   => 50,
                                'currency' => 'EUR',
                            ),
                        ),
                    ),
                ),
            ),
        )));
        if ($response && isset($response['data'])) {
            die(mypa_json_encode(
                array(
                    'success' => true,
                )));
        }

        die(mypa_json_encode(array(
            'success' => false,
        )));
    }

    /**
     * Save concept
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function ajaxProcessSaveConcept()
    {
        // @codingStandardsIgnoreStart
        $data = @json_decode(file_get_contents('php://input'), true);
        // @codingStandardsIgnoreEnd

        header('Content-Type: application/json;charset=utf-8');
        if (isset($data['data']['concept'])) {
            die(
            mypa_json_encode(
                array(
                    'success' => (bool) MPBpostDeliveryOption::saveConcept(
                        (int) $data['data']['idOrder'],
                        mypa_json_encode($data['data']['concept'])
                    ),
                )
            )
            );
        }

        die(mypa_json_encode(array(
            'success' => false,
        )));
    }

    /**
     * Save concept
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function ajaxProcessSaveConceptData()
    {
        // @codingStandardsIgnoreStart
        $data = @json_decode(file_get_contents('php://input'), true);
        // @codingStandardsIgnoreEnd

        header('Content-Type: application/json;charset=utf-8');
        if (isset($data['idOrder'])) {
            die(mypa_json_encode(
                array(
                    'success' => (bool) MPBpostDeliveryOption::saveConceptData(
                        (int) $data['idOrder'],
                        mypa_json_encode($data['data'])
                    ),
                )
            ));
        }

        die(mypa_json_encode(array(
            'success' => false,
        )));
    }

    /**
     * Initialize navigation
     *
     * @return array Menu items
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function initNavigation()
    {
        $menu = array(
            'main'            => array(
                'short'  => $this->l('Settings'),
                'desc'   => $this->l('Module settings'),
                'href'   => $this->baseUrl.'&menu='.static::MENU_MAIN_SETTINGS.'&token='
                    .Tools::getAdminTokenLite('AdminModules'),
                'active' => false,
                'icon'   => 'icon-gears',
            ),
            'defaultsettings' => array(
                'short'  => $this->l('Shipping settings'),
                'desc'   => $this->l('Default shipping settings'),
                'href'   => $this->baseUrl.'&menu='.static::MENU_DEFAULT_SETTINGS.'&token='
                    .Tools::getAdminTokenLite('AdminModules'),
                'active' => false,
                'icon'   => 'icon-truck',
            ),
            'deliveryoptions' => array(
                'short'  => $this->l('Delivery options'),
                'desc'   => $this->l('Available delivery options'),
                'href'   => $this->baseUrl.'&menu='.static::MENU_DEFAULT_DELIVERY_OPTIONS.'&token='
                    .Tools::getAdminTokenLite('AdminModules'),
                'active' => false,
                'icon'   => 'icon-truck',
            ),
        );

        switch (Tools::getValue('menu')) {
            case static::MENU_DEFAULT_SETTINGS:
                $this->menu = static::MENU_DEFAULT_SETTINGS;
                $menu['defaultsettings']['active'] = true;
                break;
            case static::MENU_DEFAULT_DELIVERY_OPTIONS:
                $this->menu = static::MENU_DEFAULT_DELIVERY_OPTIONS;
                $menu['deliveryoptions']['active'] = true;
                break;
            default:
                $this->menu = static::MENU_MAIN_SETTINGS;
                $menu['main']['active'] = true;
                break;
        }

        return $menu;
    }

    /**
     * Process settings
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     * @throws Adapter_Exception
     */
    protected function postProcess()
    {
        switch (Tools::getValue('menu')) {
            case static::MENU_DEFAULT_SETTINGS:
                $this->postProcessDefaultSettingsPage();
                break;
            case static::MENU_DEFAULT_DELIVERY_OPTIONS:
                $this->postProcessDeliverySettingsPage();
                break;
            default:
                $this->postProcessMainSettingsPage();
                break;
        }
    }

    /**
     * Post process default settings page
     *
     * @return void
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected function postProcessDefaultSettingsPage()
    {
        $submitted = false;

        foreach (array_keys($this->getDefaultSettingsFormValues()) as $key) {
            if (Tools::isSubmit($key)) {
                $submitted = true;
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }

        if ($submitted && empty($this->context->controller->errors)) {
            $this->addConfirmation($this->l('Settings updated'));
        }
    }

    /**
     * Get shipping configuration form values
     *
     * @return array Configuration values
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected function getDefaultSettingsFormValues()
    {
        return array(
            static::LINK_EMAIL                                =>
                Configuration::get(static::LINK_EMAIL),
            static::LINK_PHONE                                =>
                Configuration::get(static::LINK_PHONE),
            static::USE_PICKUP_ADDRESS                        =>
                Configuration::get(static::USE_PICKUP_ADDRESS),
            static::DEFAULT_CONCEPT_PARCEL_TYPE               =>
                Configuration::get(static::DEFAULT_CONCEPT_PARCEL_TYPE),
            static::DEFAULT_CONCEPT_LARGE_PACKAGE             =>
                Configuration::get(static::DEFAULT_CONCEPT_LARGE_PACKAGE),
            static::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY        =>
                Configuration::get(static::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY),
            static::DEFAULT_CONCEPT_SIGNED                    =>
                Configuration::get(static::DEFAULT_CONCEPT_SIGNED),
            static::DEFAULT_CONCEPT_RETURN                    =>
                Configuration::get(static::DEFAULT_CONCEPT_RETURN),
            static::DEFAULT_CONCEPT_INSURED                   =>
                Configuration::get(static::DEFAULT_CONCEPT_INSURED),
        );
    }

    /**
     * Add confirmation message
     *
     * @param string $message Message
     * @param bool   $private Only display on module's configuration page
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function addConfirmation($message, $private = true)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->confirmations[] = '<a href="'.$this->baseUrl.'">'.$this->displayName.': '
                    .$message.'</a>';
            }
        } else {
            $this->context->controller->confirmations[] = $message;
        }
    }

    /**
     * Post process delivery settings page
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function postProcessDeliverySettingsPage()
    {
        if (Tools::isSubmit('submit'.MPBpostCarrierDeliverySetting::$definition['primary'])) {
            $this->postProcessDeliverySettingForm();
        } elseif (Tools::isSubmit('delivery'.MPBpostCarrierDeliverySetting::$definition['table'])) {
            if (MPBpostCarrierDeliverySetting::toggleDelivery(
                Tools::getValue(MPBpostCarrierDeliverySetting::$definition['primary'])
            )) {
                $this->addConfirmation($this->l('The status has been successfully toggled'));
            } else {
                $this->addError($this->l('Unable to toggle status'));
            }
        } elseif (Tools::isSubmit('pickup'.MPBpostCarrierDeliverySetting::$definition['table'])) {
            if (MPBpostCarrierDeliverySetting::togglePickup(
                Tools::getValue(MPBpostCarrierDeliverySetting::$definition['primary'])
            )) {
                $this->addConfirmation($this->l('The status has been successfully toggled'));
            } else {
                $this->addError($this->l('Unable to toggle status'));
            }
        }
    }

    /**
     * Process form
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function postProcessDeliverySettingForm()
    {
        $mss = new MPBpostCarrierDeliverySetting(
            (int) Tools::getValue(
                MPBpostCarrierDeliverySetting::$definition['primary']
            )
        );
        if (!Validate::isLoadedObject($mss)) {
            $this->addError($this->l('Could not process delivery setting'));

            return;
        }

        $mss->{MPBpostCarrierDeliverySetting::DELIVERY} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::DELIVERY);
        $mss->{MPBpostCarrierDeliverySetting::DROPOFF_DELAY} =
            (int) Tools::getValue(MPBpostCarrierDeliverySetting::DROPOFF_DELAY);
        $mss->{MPBpostCarrierDeliverySetting::DELIVERY} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::DELIVERY);
        $mss->{MPBpostCarrierDeliverySetting::PICKUP} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::PICKUP);
        $mss->{MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY);
        $mss->{MPBpostCarrierDeliverySetting::SIGNED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::SIGNED);

        if ($mss->{MPBpostCarrierDeliverySetting::DROPOFF_DELAY} > 14
        ) {
            $this->addError(
                $this->l('Total of `Drop off delay` and `Amount of days to show ahead` cannot be more than 14')
            );

            return;
        }

        $mss->{MPBpostCarrierDeliverySetting::MONDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::MONDAY_ENABLED);
        $mondayTime = Tools::getValue(MPBpostCarrierDeliverySetting::MONDAY_CUTOFF);
        if ($this->isTime($mondayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::MONDAY_CUTOFF} = pSQL($mondayTime);
        }
        $mss->{MPBpostCarrierDeliverySetting::TUESDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::TUESDAY_ENABLED);
        $tuesdayTime = Tools::getValue(MPBpostCarrierDeliverySetting::TUESDAY_CUTOFF);
        if ($this->isTime($tuesdayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::TUESDAY_CUTOFF} = pSQL($tuesdayTime);
        }
        $mss->{MPBpostCarrierDeliverySetting::WEDNESDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::WEDNESDAY_ENABLED);
        $wednesdayTime = Tools::getValue(MPBpostCarrierDeliverySetting::WEDNESDAY_CUTOFF);
        if ($this->isTime($wednesdayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::WEDNESDAY_CUTOFF} = pSQL($wednesdayTime);
        }
        $mss->{MPBpostCarrierDeliverySetting::THURSDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::THURSDAY_ENABLED);
        $thursdayTime = Tools::getValue(MPBpostCarrierDeliverySetting::THURSDAY_CUTOFF);
        if ($this->isTime($thursdayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::THURSDAY_CUTOFF} = pSQL($thursdayTime);
        }
        $mss->{MPBpostCarrierDeliverySetting::FRIDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::FRIDAY_ENABLED);
        $fridayTime = Tools::getValue(MPBpostCarrierDeliverySetting::FRIDAY_CUTOFF);
        if ($this->isTime($fridayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::FRIDAY_CUTOFF} = pSQL($fridayTime);
        }
        $mss->{MPBpostCarrierDeliverySetting::SATURDAY_ENABLED} =
            (bool) Tools::getValue(MPBpostCarrierDeliverySetting::SATURDAY_ENABLED);
        $saturdayTime = Tools::getValue(MPBpostCarrierDeliverySetting::SATURDAY_CUTOFF);
        if ($this->isTime($saturdayTime)) {
            $mss->{MPBpostCarrierDeliverySetting::SATURDAY_CUTOFF} = pSQL($saturdayTime);
        }

        if (Tools::isSubmit(MPBpostCarrierDeliverySetting::CUTOFF_EXCEPTIONS)) {
            $mss->{MPBpostCarrierDeliverySetting::CUTOFF_EXCEPTIONS} =
                Tools::getValue(MPBpostCarrierDeliverySetting::CUTOFF_EXCEPTIONS);
        }

        $mss->{MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY_FEE} =
            (float) str_replace(',', '.', Tools::getValue(MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY_FEE));
        $mss->{MPBpostCarrierDeliverySetting::SIGNED_FEE} =
            (float) str_replace(',', '.', Tools::getValue(MPBpostCarrierDeliverySetting::SIGNED_FEE));
        $mss->{MPBpostCarrierDeliverySetting::PICKUP_FEE} =
            (float) str_replace(',', '.', Tools::getValue(MPBpostCarrierDeliverySetting::PICKUP_FEE));

        $mss->save();
    }

    /**
     * Check if time input is correct
     *
     * @param string $input Input
     *
     * @return bool Time format is correct
     *
     * @since 2.0.0
     */
    protected static function isTime($input)
    {
        return preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $input);
    }

    /**
     * Post process main settings page
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function postProcessMainSettingsPage()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            $validUser = false;
            $validApi = false;

            // api key
            $apiKey = (string) Tools::getValue(static::API_KEY);
            if (!$apiKey
                || empty($apiKey)
                || !Validate::isGenericName($apiKey)
            ) {
                $this->addError($this->l('Invalid Api Key'));
            } else {
                $validApi = true;
                $previousApiKey = Configuration::get(static::API_KEY);
                if ($apiKey !== $previousApiKey) {
                    Configuration::deleteByName(static::WEBHOOK_ID);
                    Configuration::deleteByName(static::WEBHOOK_LAST_CHECK);
                }

                Configuration::updateValue(static::API_KEY, $apiKey);
            }

            if ($validUser && $validApi) {
                $this->addConfirmation($this->l('Settings updated'));
            }

            Configuration::updateValue(static::CHECKOUT_FG_COLOR1, Tools::getValue(static::CHECKOUT_FG_COLOR1));
            Configuration::updateValue(static::CHECKOUT_FG_COLOR2, Tools::getValue(static::CHECKOUT_FG_COLOR2));
            Configuration::updateValue(static::CHECKOUT_FG_COLOR3, Tools::getValue(static::CHECKOUT_FG_COLOR3));
            Configuration::updateValue(static::CHECKOUT_BG_COLOR1, Tools::getValue(static::CHECKOUT_BG_COLOR1));
            Configuration::updateValue(static::CHECKOUT_BG_COLOR2, Tools::getValue(static::CHECKOUT_BG_COLOR2));
            Configuration::updateValue(static::CHECKOUT_HL_COLOR, Tools::getValue(static::CHECKOUT_HL_COLOR));
            Configuration::updateValue(static::CHECKOUT_INACTIVE_COLOR, Tools::getValue(static::CHECKOUT_INACTIVE_COLOR));
            Configuration::updateValue(static::CHECKOUT_FONT, Tools::getValue(static::CHECKOUT_FONT));
            Configuration::updateValue(
                static::CHECKOUT_FONT_SIZE,
                (int) Tools::getValue(static::CHECKOUT_FONT_SIZE)
                    ? (int) Tools::getValue(static::CHECKOUT_FONT_SIZE)
                    : 14
            );
            Configuration::updateValue(
                static::UPDATE_ORDER_STATUSES,
                (bool) Tools::getValue(static::UPDATE_ORDER_STATUSES)
            );
            Configuration::updateValue(static::LOG_API, (bool) Tools::getValue(static::LOG_API));
            Configuration::updateValue(static::DEV_MODE_ASYNC, (bool) Tools::getValue(static::DEV_MODE_ASYNC));
            Configuration::updateValue(static::DEV_MODE_HIDE_PREFERRED, (bool) Tools::getValue(static::DEV_MODE_HIDE_PREFERRED));
            Configuration::updateValue(static::PRINTED_STATUS, (int) Tools::getValue(static::PRINTED_STATUS));
            Configuration::updateValue(static::SHIPPED_STATUS, (int) Tools::getValue(static::SHIPPED_STATUS));
            Configuration::updateValue(static::RECEIVED_STATUS, (int) Tools::getValue(static::RECEIVED_STATUS));
            Configuration::updateValue(static::NOTIFICATIONS, (bool) Tools::getValue(static::NOTIFICATIONS));
            Configuration::updateValue(static::NOTIFICATION_MOMENT, Tools::getValue(static::NOTIFICATION_MOMENT) ? 1 : 0);
            Configuration::updateValue(static::LABEL_DESCRIPTION, Tools::getValue(static::LABEL_DESCRIPTION));
            Configuration::updateValue(static::PAPER_SELECTION, Tools::getValue(static::PAPER_SELECTION));
            Configuration::updateValue(static::ASK_PAPER_SELECTION, Tools::getValue(static::ASK_PAPER_SELECTION));
        }
    }

    /**
     * Everything necessary to display the whole form.
     *
     * @return string HTML for the bo page
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function displayDefaultSettingsForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name.'status';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&menu='
            .static::MENU_DEFAULT_SETTINGS;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getDefaultSettingsFormValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        $forms = array(
            $this->getDefaultConceptsForm(),
            $this->getDefaultSettingsForm(),
        );

        return $helper->generateForm($forms);
    }

    /**
     * Create the structure of the config form
     *
     * @return array
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected function getDefaultConceptsForm()
    {
        return array(
            'form' => array(
                'legend'      => array(
                    'title' => $this->l('Default concept'),
                    'icon'  => 'icon-files-o',
                ),
                'description' => $this->l('These are the default concept settings'),
                'input'       => array(
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Signature'),
                        'name'    => static::DEFAULT_CONCEPT_SIGNED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Insured up to €500'),
                        'name'    => static::DEFAULT_CONCEPT_INSURED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                ),
                'submit'      => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of the config form
     *
     * @return array
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function getDefaultSettingsForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Data'),
                    'icon'  => 'icon-shield',
                ),
                'input'  => array(
                    array(
                        'type'    => 'switch',
                        'label'   => $this->display(__FILE__, 'views/templates/admin/gdpr/badge-email.tpl')
                            .' '
                            .$this->l('Share customer\'s email address with MyParcel BE'),
                        'desc'    =>
                            $this->l('Sharing the customer\'s email address with MyParcel BE makes sure that')
                            .' '
                            .$this->l('MyParcel BE can send a Track and Trace email. You can configure the')
                            .' '
                            .sprintf($this->l('email settings in the MyParcel BE %sback office%s.'), '<a href="https://backoffice.sendmyparcel.be/ttsettingstable" target="_blank">', '</a>'),
                        'name'    => static::LINK_EMAIL,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->display(__FILE__, 'views/templates/admin/gdpr/badge-phone.tpl')
                            .' '
                            .$this->l('Share customer\'s phone number with MyParcel BE'),
                        'desc'    =>
                            $this->l('When sharing the customer\'s phone number with MyParcel BE the')
                            .' '
                            .$this->l('carrier can use this phone number for delivery.')
                            .' '
                            .$this->l('This greatly increases the chance of a successful delivery')
                            .' '
                            .$this->l('when sending shipments abroad.'),
                        'name'    => static::LINK_PHONE,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Use the pickup location address'),
                        'desc'    =>
                            $this->l('When enabled, the pickup location\'s address will be set as')
                            .' '
                            .$this->l('the customer\'s delivery address.'),
                        'name'    => static::USE_PICKUP_ADDRESS,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function displayDeliveryOptionsPage()
    {
        $output = '';

        $this->updateCarriers();

        $this->context->controller->addJS($this->_path.'views/js/forms.js');
        $this->context->controller->addCSS($this->_path.'views/css/forms.css');

        if (Tools::isSubmit('delivery'.MPBpostCarrierDeliverySetting::$definition['table'])) {
            $this->removeOldExceptions(Tools::getValue(MPBpostCarrierDeliverySetting::$definition['primary']));
        }

        if (Tools::isSubmit(MPBpostCarrierDeliverySetting::$definition['primary'])
            && Tools::isSubmit('add'.MPBpostCarrierDeliverySetting::$definition['table'])
            || Tools::isSubmit('update'.MPBpostCarrierDeliverySetting::$definition['table'])
        ) {
            $output .= $this->renderDeliveryOptionForm();
        } else {
            try {
                $output .= $this->renderDeliveryOptionList();
            } catch (PrestaShopException $e) {
                $this->context->controller->errors[] = $e->getMessage();
            }
        }

        return $output;
    }

    /**
     * Update carriers
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function updateCarriers()
    {
        $carriers = Carrier::getCarriers(
            Context::getContext()->language->id,
            false,
            false,
            false,
            null,
            Carrier::ALL_CARRIERS
        );
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(MPBpostCarrierDeliverySetting::$definition['table']));
        try {
            $currentList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        } catch (PrestaShopException $e) {
            $currentList = array();
        }

        foreach ($carriers as $carrier) {
            $found = false;
            foreach ($currentList as $current) {
                if ($carrier['id_reference'] == $current['id_reference']) {
                    $found = true;
                    break;
                }
            }
            if (!$found && !empty($carrier['id_reference'])) {
                try {
                    Db::getInstance()->insert(
                        bqSQL(MPBpostCarrierDeliverySetting::$definition['table']),
                        array(
                            'id_reference'                           => (int) $carrier['id_reference'],
                            MPBpostCarrierDeliverySetting::DELIVERY => false,
                            MPBpostCarrierDeliverySetting::PICKUP   => false,
                            'id_shop'                                => $this->getShopId(),
                        )
                    );
                } catch (PrestaShopException $e) {
                    Logger::AddLog("MyParcel module - unable to add carrier setting: {$e->getMessage()}");
                }
            }
        }
    }

    /**
     * Get the Shop ID of the current context
     * Retrieves the Shop ID from the cookie
     *
     * @return int Shop ID
     *
     * @since 2.0.0
     */
    protected function getShopId()
    {
        $context = Context::getContext();
        if (isset($context->employee->id)
            && $context->employee->id && Shop::getContext() == Shop::CONTEXT_SHOP
            && version_compare(_PS_VERSION_, '1.7.0.0', '<')
        ) {
            $cookie = $context->cookie->getFamily('shopContext');

            return (int) Tools::substr($cookie['shopContext'], 2, count($cookie['shopContext']));
        }

        return (int) $context->shop->id;
    }

    /**
     * Clean up old dates from exception schemes
     *
     * @param int $idMPBpostDeliveryOption MyParcel Delivery Option ID
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function removeOldExceptions($idMPBpostDeliveryOption)
    {
        $samedayDeliveryOption = new MPBpostCarrierDeliverySetting($idMPBpostDeliveryOption);
        if (Validate::isLoadedObject($samedayDeliveryOption)) {
            $exceptions = @json_decode($samedayDeliveryOption->cutoff_exceptions, true);
            if (is_array($exceptions)) {
                $exceptionDates = array_keys($exceptions);
                for ($i = 0; $i < count($exceptionDates); $i++) {
                    if (strtotime($exceptionDates[$i]) < time()) {
                        $dateToRemove = $exceptionDates[$i];
                        unset($exceptions[$dateToRemove]);
                    }
                }
                if (empty($exceptions)) {
                    $samedayDeliveryOption->cutoff_exceptions = '{}';
                } else {
                    $samedayDeliveryOption->cutoff_exceptions = mypa_json_encode($exceptions);
                }
            }

            $samedayDeliveryOption->save();
        }
    }

    /**
     * Display forms
     *
     * @return string Forms HTML
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function renderDeliveryOptionForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.MPBpostCarrierDeliverySetting::$definition['primary'];
        $helper->currentIndex = $this->baseUrl.'&menu='.static::MENU_DEFAULT_DELIVERY_OPTIONS;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getDeliveryOptionsFormValues(
                (int) Tools::getValue(MPBpostCarrierDeliverySetting::$definition['primary'])
            ),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getDeliverySettingForm(), $this->getCutoffForm()));
    }

    /**
     * Set values for the inputs of the configuration form
     *
     * @param int $idMPBpostCarrierDeliverySetting MyParcel Delivery Option ID
     *
     * @return array Array with current values
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function getDeliveryOptionsFormValues($idMPBpostCarrierDeliverySetting)
    {
        $mcds = new MPBpostCarrierDeliverySetting($idMPBpostCarrierDeliverySetting);
        $mcds->{MPBpostCarrierDeliverySetting::$definition['primary']} = $mcds->id;

        $mcds->{MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY_FEE} = str_replace('.', ',', $mcds->{MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY_FEE});
        $mcds->{MPBpostCarrierDeliverySetting::PICKUP_FEE} = str_replace('.', ',', $mcds->{MPBpostCarrierDeliverySetting::PICKUP_FEE});
        $mcds->{MPBpostCarrierDeliverySetting::SIGNED_FEE} = str_replace('.', ',', $mcds->{MPBpostCarrierDeliverySetting::SIGNED_FEE});

        return (array) $mcds;
    }

    /**
     * Create the structure of the extra form
     *
     * @return array Form array
     * @throws PrestaShopException
     */
    protected function getDeliverySettingForm()
    {
        $deliveryDaysOptions = array(
            array(
                'id'   => -1,
                'name' => $this->l('Hide days'),
            ),
        );
        for ($i = 1; $i < 15; $i++) {
            $deliveryDaysOptions[] = array(
                'id'   => $i,
                'name' => sprintf($this->l('%d days'), $i),
            );
        }

        $dropoffDelayOptions = array(
            array(
                'id'   => 0,
                'name' => $this->l('No delay'),
            ),
            array(
                'id'   => 1,
                'name' => $this->l('1 day'),
            ),
        );
        for ($i = 2; $i < 15; $i++) {
            $dropoffDelayOptions[] = array(
                'id'   => $i,
                'name' => sprintf($this->l('%d days'), $i),
            );
        }

        $currency = Currency::getDefaultCurrency();

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Delivery options'),
                    'icon'  => 'icon-truck',
                ),
                'input'  => array(
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Timeframes'),
                        'desc'    => $this->l('Show available timeframes'),
                        'name'    => MPBpostCarrierDeliverySetting::DELIVERY,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Drop off delay'),
                        'name'     => MPBpostCarrierDeliverySetting::DROPOFF_DELAY,
                        'required' => true,
                        'options'  => array(
                            'query' => $dropoffDelayOptions,
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Signed'),
                        'name'    => MPBpostCarrierDeliverySetting::SIGNED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'text',
                        'price'    => true,
                        'label'    => $this->l('Signed fee'),
                        'desc'     => $this->l('Extra fee for signed'),
                        'name'     => MPBpostCarrierDeliverySetting::SIGNED_FEE,
                        'prefix'   => $currency->sign,
                        'suffix'   => $this->l('incl. 21% VAT'),
                        'required' => true,
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Saturday delivery'),
                        'name'    => MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'text',
                        'price'    => true,
                        'label'    => $this->l('Saturday delivery fee'),
                        'name'     => MPBpostCarrierDeliverySetting::SATURDAY_DELIVERY_FEE,
                        'prefix'   => $currency->sign,
                        'suffix'   => $this->l('incl. 21% VAT'),
                        'required' => true,
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Post Offices'),
                        'desc'    => $this->l('Show available post offices'),
                        'name'    => MPBpostCarrierDeliverySetting::PICKUP,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'text',
                        'price'    => true,
                        'label'    => $this->l('Post Office Fee'),
                        'desc'    => $this->l('Extra fee for collecting at post offices'),
                        'name'     => MPBpostCarrierDeliverySetting::PICKUP_FEE,
                        'prefix'   => $currency->sign,
                        'suffix'   => $this->l('incl. 21% VAT'),
                        'required' => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of the cut off form
     *
     * @return array Form array
     */
    protected function getCutoffForm()
    {
        return array(
            'form' => array(
                'legend'      => array(
                    'title' => $this->l('Next day delivery'),
                    'icon'  => 'icon-clock-o',
                ),
                'description' => (date_default_timezone_get() === 'Europe/Amsterdam')
                    ? '' :
                    sprintf(
                        $this->l('The module assumes that you are using the following timezone: %s'),
                        ini_get('date.timezone')
                    ),
                'input'       => array(
                    array(
                        'type' => 'hidden',
                        'name' => MPBpostCarrierDeliverySetting::$definition['primary'],
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Monday'),
                        'name'    => MPBpostCarrierDeliverySetting::MONDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Monday'),
                        'name'  => MPBpostCarrierDeliverySetting::MONDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Tuesday'),
                        'name'    => MPBpostCarrierDeliverySetting::TUESDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Tuesday'),
                        'name'  => MPBpostCarrierDeliverySetting::TUESDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Wednesday'),
                        'name'    => MPBpostCarrierDeliverySetting::WEDNESDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Wednesday'),
                        'name'  => MPBpostCarrierDeliverySetting::WEDNESDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Thursday'),
                        'name'    => MPBpostCarrierDeliverySetting::THURSDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Thursday'),
                        'name'  => MPBpostCarrierDeliverySetting::THURSDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Friday'),
                        'name'    => MPBpostCarrierDeliverySetting::FRIDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Friday'),
                        'name'  => MPBpostCarrierDeliverySetting::FRIDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Dispatch on Saturday'),
                        'name'    => MPBpostCarrierDeliverySetting::SATURDAY_ENABLED,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'  => 'time',
                        'label' => $this->l('Cut-off time on Saturday'),
                        'name'  => MPBpostCarrierDeliverySetting::SATURDAY_CUTOFF,
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type' => 'hr',
                        'name' => '',
                    ),
                    array(
                        'type'  => 'cutoffexceptions',
                        'label' => $this->l('Exception schedule'),
                        'name'  => MPBpostCarrierDeliverySetting::CUTOFF_EXCEPTIONS,
                    ),
                ),
                'submit'      => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Function used to render the list to display for this controller
     *
     * @return string|false
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function renderDeliveryOptionList()
    {
        $fieldsList = array(
            bqSQL(MPBpostCarrierDeliverySetting::$definition['primary']) => array('title' => $this->l('ID')),
            'name'                                                        => array('title' => $this->l('Name')),
            MPBpostCarrierDeliverySetting::DELIVERY                      => array(
                'title'  => $this->l('Timeframes enabled'),
                'type'   => 'bool',
                'active' => MPBpostCarrierDeliverySetting::DELIVERY,
                'ajax'   => false,
                'align'  => 'center',

            ),
            MPBpostCarrierDeliverySetting::PICKUP                        => array(
                'title'  => $this->l('Post offices enabled'),
                'type'   => 'bool',
                'active' => MPBpostCarrierDeliverySetting::PICKUP,
                'ajax'   => false,
                'align'  => 'center',
            ),
            'cutoff_times'                                                => array(
                'title'           => $this->l('Cut off times'),
                'type'            => 'cutoff_times',
                'align'           => 'center',
                'orderby'         => false,
                'search'          => false,
                'class'           => 'sameday-cutoff-labels',
                'callback'        => 'printCutOffItems',
                'callback_object' => 'MPBpostTools',
            ),
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = array('edit');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->identifier = bqSQL(MPBpostCarrierDeliverySetting::$definition['primary']);
        $helper->title = $this->l('Cutoff times');
        $helper->table = MPBpostCarrierDeliverySetting::$definition['table'];
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->baseUrl.'&menu='.static::MENU_DEFAULT_DELIVERY_OPTIONS;
        $helper->colorOnBackground = true;
        $helper->no_link = true;
        $list = $this->getDeliveryOptionsList($helper);
        $helper->listTotal = count($list);

        foreach ($list as $carrier) {
            if ($carrier['external_module_name'] && $carrier['external_module_name'] !== $this->name) {
                $this->context->controller->warnings[] =
                    $this->l('Some carriers are managed by external modules.')
                    .' '.
                    $this->l('Delivery options will not be available for these carriers.');
                break;
            }
        }

        return $helper->generateList($list, $fieldsList);
    }

    /**
     * Get the current objects' list form the database
     *
     * @param HelperList $helper
     *
     * @throws PrestaShopException
     *
     * @return array
     *
     * @since 2.0.0
     */
    protected function getDeliveryOptionsList(HelperList $helper)
    {
        $sql = new DbQuery();
        $sql->select('mcds.*, c.`name`, c.`external_module_name`');
        $sql->from(bqSQL(MPBpostCarrierDeliverySetting::$definition['table']), 'mcds');
        $sql->innerJoin('carrier', 'c', 'mcds.`id_reference` = c.`id_reference` AND c.`deleted` = 0');
        $sql->where('mcds.`id_shop` = '.(int) $this->context->shop->id);

        $list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $skipList = array();

        foreach ($list as &$samedaySetting) {
            $cutoffExceptions = @json_decode(
                $samedaySetting[MPBpostCarrierDeliverySetting::CUTOFF_EXCEPTIONS],
                true
            );
            if (!is_array($cutoffExceptions)) {
                $cutoffExceptions = array();
            }

            $cutoffTimes = array();
            $date = new DateTime('NOW');
            if (!$date->format('w')) {
                $date->modify('+1 day');
            }
            for ($i = 0; $i < 6; $i++) {
                if (array_key_exists($date->format('d-m-Y'), $cutoffExceptions)) {
                    $exceptionInfo = $cutoffExceptions[$date->format('d-m-Y')];

                    if ((array_key_exists('nodispatch', $exceptionInfo) && $exceptionInfo['nodispatch'])
                        && (array_key_exists('cutoff', $exceptionInfo))
                    ) {
                        $nodispatch = false;
                    } else {
                        $nodispatch = true;
                    }

                    $cutoffTimes[$i] = array(
                        'name'       => $this->l($date->format('D')),
                        'time'       => (array_key_exists('cutoff', $exceptionInfo) ? $exceptionInfo['cutoff'] : ''),
                        'exception'  => true,
                        'nodispatch' => $nodispatch,
                    );
                } elseif ((bool) $samedaySetting[Tools::strtolower($date->format('l')).'_enabled']) {
                    $cutoffTimes[$i] = array(
                        'name'       => $this->l($date->format('D')),
                        'time'       => $samedaySetting[Tools::strtolower($date->format('l')).'_cutoff'],
                        'exception'  => false,
                        'nodispatch' => false,
                    );
                } else {
                    $cutoffTimes[$i] = array(
                        'name'       => $this->l($date->format('D')),
                        'time'       => '',
                        'exception'  => false,
                        'nodispatch' => true,
                    );
                }
                $date->modify('+1 day');
                if (!$date->format('w')) {
                    $date->modify('+1 day');
                }
            }

            $samedaySetting['cutoff_times'] = $cutoffTimes;
            if ($samedaySetting['external_module_name'] && $samedaySetting['external_module_name'] != $this->name) {
                $samedaySetting['color'] = '#E08F95';
                $samedaySetting[MPBpostCarrierDeliverySetting::PICKUP] = null;
                $samedaySetting[MPBpostCarrierDeliverySetting::DELIVERY] = null;
                $samedaySetting['cutoff_times'] = null;
                $skipList[] = $samedaySetting[MPBpostCarrierDeliverySetting::$definition['primary']];
            }
        }
        $helper->list_skip_actions['edit'] = $skipList;

        return $list;
    }

    /**
     * Display main settings page
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function displayMainSettingsPage()
    {
        $this->context->controller->addJquery();
        $this->context->controller->addCSS($this->_path.'views/css/fontselect.css', 'all');
        $this->context->controller->addJS($this->_path.'views/js/fontselect.js');

        $html = '';
        if (static::isOldVersionInstalled(true)) {
            $html .= $this->display(__FILE__, 'views/templates/admin/updatemodule.tpl');
        }
        $html .= $this->displayMainForm();


        return $html;

    }

    /**
     * Configuration Page: display form
     *
     * @return string Main page form HTML
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    protected function displayMainForm()
    {
        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='
                    .Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ),
        );
        $helper->fields_value = $this->getMainFormValues();

        $this->context->controller->addJS($this->_path.'views/js/dist/front-cbfee8e3cbc20b2b.bundle.min.js');
        $this->context->controller->addJS($this->_path.'views/js/dist/back-cbfee8e3cbc20b2b.bundle.min.js');

        return $helper->generateForm(array(
            $this->getApiForm(),
            $this->getCheckoutForm(),
            $this->getLabelForm(),
            $this->getNotificationForm(),
            $this->getAdvancedForm(),
        ));
    }

    /**
     * Get Main form configuration values
     *
     * @return array Configuration values
     *
     * @since 2.0.0
     * @throws PrestaShopException
     */
    protected function getMainFormValues()
    {
        return array(
            static::API_KEY                 => Configuration::get(static::API_KEY),
            static::CHECKOUT_FG_COLOR1      => Configuration::get(static::CHECKOUT_FG_COLOR1) ?: '#FFFFFF',
            static::CHECKOUT_FG_COLOR2      => Configuration::get(static::CHECKOUT_FG_COLOR2 ?: '#000000'),
            static::CHECKOUT_FG_COLOR3      => Configuration::get(static::CHECKOUT_FG_COLOR3 ?: '#000000'),
            static::CHECKOUT_BG_COLOR1      => Configuration::get(static::CHECKOUT_BG_COLOR1 ?: '#FBFBFB'),
            static::CHECKOUT_BG_COLOR2      => Configuration::get(static::CHECKOUT_BG_COLOR2) ?: '#01BBC5',
            static::CHECKOUT_HL_COLOR       => Configuration::get(static::CHECKOUT_HL_COLOR) ?: '#FF8C00',
            static::CHECKOUT_INACTIVE_COLOR => Configuration::get(static::CHECKOUT_INACTIVE_COLOR) ?: '#848484',
            static::CHECKOUT_FONT           => Configuration::get(static::CHECKOUT_FONT) ?: 'Exo',
            static::CHECKOUT_FONT_SIZE      => Configuration::get(static::CHECKOUT_FONT_SIZE) ?: 2,
            static::UPDATE_ORDER_STATUSES   => Configuration::get(static::UPDATE_ORDER_STATUSES),
            static::LOG_API                 => Configuration::get(static::LOG_API),
            static::DEV_MODE_ASYNC          => Configuration::get(static::DEV_MODE_ASYNC),
            static::DEV_MODE_HIDE_PREFERRED => Configuration::get(static::DEV_MODE_HIDE_PREFERRED),
            static::PRINTED_STATUS          => Configuration::get(static::PRINTED_STATUS),
            static::SHIPPED_STATUS          => Configuration::get(static::SHIPPED_STATUS),
            static::RECEIVED_STATUS         => Configuration::get(static::RECEIVED_STATUS),
            static::NOTIFICATIONS           => Configuration::get(static::NOTIFICATIONS),
            static::NOTIFICATION_MOMENT     => Configuration::get(static::NOTIFICATION_MOMENT),
            static::LABEL_DESCRIPTION       => Configuration::get(static::LABEL_DESCRIPTION),
            static::PAPER_SELECTION         => Configuration::get(static::PAPER_SELECTION),
            static::ASK_PAPER_SELECTION     => Configuration::get(static::ASK_PAPER_SELECTION),
            static::DEV_MODE_SET_VERSION    => null,
        );
    }

    /**
     * Get the API form
     *
     * @return array Form
     */
    protected function getApiForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => '<img width="128" height="128" style="width: 16px; height: 16px" src="'.Media::getMediaPath($this->_path.'views/img/myparcelnl-grayscale.png').'"> '.$this->l('MyParcel API'),
                ),
                'description' =>  MPBpostTools::ppTags(
                    $this->l('Please enter your API key. You can find this on the general settings page of the MyParcel BE [1]back office[/1].'),
                    array('<a href="https://backoffice.sendmyparcel.be/settings" target="_blank" rel="noopener noreferrer">')
                ),
                'input'  => array(
                    array(
                        'type'      => 'text',
                        'label'     => $this->l('MyParcel API Key'),
                        'name'      => static::API_KEY,
                        'size'      => 50,
                        'maxlength' => 50,
                        'prefix'    => '<i class="icon icon-key"></i>',
                        'required'  => true,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Get the notification form
     *
     * @return array Form
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Adapter_Exception
     * @throws SmartyException
     */
    protected function getNotificationForm()
    {
        $shippedStatus = new OrderState(Configuration::get('PS_OS_SHIPPING'), $this->context->language->id);
        $deliveredStatus = new OrderState(Configuration::get('PS_OS_DELIVERED'), $this->context->language->id);
        if (!Validate::isLoadedObject($shippedStatus)) {
            $shippedStatus = array(
                'name' => $this->l('Sent'),
            );
        }
        if (!Validate::isLoadedObject($deliveredStatus)) {
            $deliveredStatus = array(
                'name' => $this->l('Delivered'),
            );
        }
        $orderStatuses = array(
            array(
                'name'           => $this->l('Disable this status'),
                'id_order_state' => '0',
            ),
        );
        $orderStatuses = array_merge($orderStatuses, OrderState::getOrderStates($this->context->language->id));

        for ($i = 0; $i < count($orderStatuses); $i++) {
            $orderStatuses[$i]['name'] = $orderStatuses[$i]['id_order_state'].' - '.$orderStatuses[$i]['name'];
        }

        $this->aasort($orderStatuses, 'id_order_state');

        $this->context->smarty->assign(array(
            'shippedStatusName'   => $shippedStatus->name,
            'deliveredStatusName' => $deliveredStatus->name,
        ));
        try {
            $orderStatusDescription = $this->display(__FILE__, 'views/templates/hook/orderstatusinfo.tpl');
        } catch (Exception $e) {
            Logger::addLog("MyParcel module error: {$e->getMessage()}");
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Notifications'),
                    'icon'  => 'icon-bell',
                ),
                'input'  => array(
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Automate order statuses'),
                        'desc'    => $orderStatusDescription,
                        'name'    => static::UPDATE_ORDER_STATUSES,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Printed status'),
                        'desc'     => $this->l('Apply this status when the label has been printed'),
                        'name'     => static::PRINTED_STATUS,
                        'options'  => array(
                            'query'   => $orderStatuses,
                            'id'      => 'id_order_state',
                            'name'    => 'name',
                            'orderby' => 'id_order_state',
                        ),
                        'class'    => 'fixed-width-xxl',
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Shipped status'),
                        'desc'     => $this->l('Apply this status when the order has been received by bpost'),
                        'name'     => static::SHIPPED_STATUS,
                        'options'  => array(
                            'query'   => $orderStatuses,
                            'id'      => 'id_order_state',
                            'name'    => 'name',
                            'orderby' => 'id_order_state',
                        ),
                        'class'    => 'fixed-width-xxl',
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Received'),
                        'desc'     => $this->l('Apply this status when the order has been received by your customer'),
                        'name'     => static::RECEIVED_STATUS,
                        'options'  => array(
                            'query' => $orderStatuses,
                            'id'    => 'id_order_state',
                            'name'  => 'name',
                        ),
                        'class'    => 'fixed-width-xxl',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->display(__FILE__, 'views/templates/admin/gdpr/badge-notifications.tpl')
                            .' '
                            .sprintf(
                            $this->l('Send notification emails via %s'),
                            defined('_TB_VERSION_') ? 'thirty bees' : 'PrestaShop'
                        ),
                        'name'    => static::NOTIFICATIONS,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Send a notification when'),
                        'desc'     => $this->l('NOTE: sending a notification while printing may slow it down'),
                        'name'     => static::NOTIFICATION_MOMENT,
                        'options'  => array(
                            'query' => array(
                                array(
                                    'id_moment' => static::MOMENT_PRINTED,
                                    'name' => $this->l('the label has been printed'),
                                ),
                                array(
                                    'id_moment' => static::MOMENT_SCANNED,
                                    'name' => $this->l('the parcel has been scanned by bpost'),
                                ),
                            ),
                            'id'    => 'id_moment',
                            'name'  => 'name',
                        ),
                        'class'    => 'fixed-width-xxl',
                    ),
                ),
                'cancel' => array(
                    'title' => 'cancel',
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Get the checkout form
     *
     * @return array Form
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getCheckoutForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Checkout'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Header text color'),
                        'name'     => static::CHECKOUT_FG_COLOR1,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Body text color'),
                        'name'     => static::CHECKOUT_FG_COLOR2,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Title color'),
                        'name'     => static::CHECKOUT_FG_COLOR3,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Body background color'),
                        'name'     => static::CHECKOUT_BG_COLOR1,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Selected tab color'),
                        'name'     => static::CHECKOUT_BG_COLOR2,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Highlight color'),
                        'name'     => static::CHECKOUT_HL_COLOR,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'     => 'color',
                        'label'    => $this->l('Inactive color'),
                        'name'     => static::CHECKOUT_INACTIVE_COLOR,
                        'lang'     => false,
                        'data-hex' => true,
                        'class'    => 'mColorPicker',
                    ),
                    array(
                        'type'  => 'fontselect',
                        'label' => "{$this->display(__FILE__, 'views/templates/admin/gdpr/badge-fonts.tpl')} {$this->l('Font family')}",
                        'name'  => static::CHECKOUT_FONT,
                    ),
                    array(
                        'type'     => 'select',
                        'label'    => $this->l('Font size'),
                        'name'     => static::CHECKOUT_FONT_SIZE,
                        'options'  => array(
                            'query' => array(
                                array('id' => static::FONT_SMALL, 'name' => $this->l('Small')),
                                array('id' => static::FONT_MEDIUM, 'name' => $this->l('Medium')),
                                array('id' => static::FONT_LARGE, 'name' => $this->l('Large')),
                            ),
                            'id'    => 'id',
                            'name'  => 'name',
                        ),
                        'class'    => 'fixed-width-xxl',
                    ),
                    array(
                        'label'    => $this->l('Preview'),
                        'name'     => '',
                        'type'     => 'checkout',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Get the label form
     *
     * @return array Form
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getLabelForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Labels'),
                    'icon'  => 'icon-file-text',
                ),
                'input'  => array(
                    array(
                        'type'     => 'text',
                        'label'    => $this->l('Label description'),
                        'name'     => static::LABEL_DESCRIPTION,
                        'size'     => 50,
                        'desc'     => $this->display(__FILE__, 'views/templates/admin/labeldesc.tpl'),
                    ),
                    array(
                        'label' => $this->l('Default page size'),
                        'name' => static::PAPER_SELECTION,
                        'type' => 'paperselector',
                    ),
                    array(
                        'type'    => 'switch',
                        'label'   => $this->l('Always ask the paper size'),
                        'name'    => static::ASK_PAPER_SELECTION,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Get the advanced form
     *
     * @return array Form
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getAdvancedForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Advanced'),
                    'icon'  => 'icon-cogs',
                ),
                'input'  => array(
                    array(
                        'type'    => 'switch',
                        'label'   => "{$this->display(__FILE__, 'views/templates/admin/gdpr/badge-logging.tpl')} {$this->l('API logger')}",
                        'desc'    => $this->l('By enabling this option, API calls are being logged.')
                            .' '
                            .$this->l('They can be found on the page `Advanced Parameters > Logs`.'),
                        'name'    => static::LOG_API,
                        'is_bool' => true,
                        'values'  => array(
                            array(
                                'id'    => 'active_on',
                                'value' => true,
                                'label' => Translate::getAdminTranslation('Enabled', 'AdminCarriers'),
                            ),
                            array(
                                'id'    => 'active_off',
                                'value' => false,
                                'label' => Translate::getAdminTranslation('Disabled', 'AdminCarriers'),
                            ),
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Display before carrier
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    public function hookDisplayBeforeCarrier()
    {
        $smartyVars = $this->context->smarty->getTemplateVars();
        if (!isset($smartyVars['widgetHook'])) {
            $this->context->smarty->assign('widgetHook', 'beforeCarrier');
        }

        $this->context->smarty->assign(
            array(
                'mpbpost_checkout_link'        =>
                    $this->context->link->getModuleLink(
                        $this->name,
                        'myparcelcheckout',
                        array(),
                        Tools::usingSecureMode()
                    ),
                'mpbpost_deliveryoptions_link' =>
                    $this->context->link->getModuleLink(
                        $this->name,
                        'deliveryoptions',
                        array(),
                        Tools::usingSecureMode()
                    ),
                'link'                         => $this->context->link,
            )
        );

        /** @var Cart $cart */
        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            if (Configuration::get(static::LOG_API)) {
                Logger::addLog("{$this->displayName}: No valid cart found");
            }

            return '';
        }

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return $this->display(__FILE__, 'views/templates/hook/beforecarrier17.tpl');
        }

        $address = new Address((int) $cart->id_address_delivery);
        if (!preg_match(static::SPLIT_STREET_REGEX, MPBpostTools::getAddressLine($address))) {
            // No house number
            if (Configuration::get(static::LOG_API)) {
                Logger::addLog("{$this->displayName}: No housenumber for Cart {$cart->id}");
            }

            return '';
        }

        $carrier = new Carrier($cart->id_carrier);
        if (!Validate::isLoadedObject($carrier)) {
            $idZone = (int) Country::getIdZone($address->id_country);
            $availableCarriers = Carrier::getCarriersForOrder($idZone, null, $cart);
            if (isset($availableCarriers[0])) {
                $this->carrier = new Carrier((int) $availableCarriers[0]['id_carrier']);
            }
        }

        $mcds = MPBpostCarrierDeliverySetting::getByCarrierReference($carrier->id_reference);
        if (!Validate::isLoadedObject($mcds) || $carrier->external_module_name !== $this->name) {
            return '';
        }


        if ($mcds->delivery || $mcds->pickup) {
            return $this->display(__FILE__, 'views/templates/hook/beforecarrier.tpl');
        }

        return '';
    }

    /**
     * Display before carrier hook
     *
     * @return string Hook HTML
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    public function hookDisplayCarrierList()
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            // Deprecated on 1.7, hook to displayBeforeCarrier instead
            return '';
        }

        // Do not display if already hooked to `displayBeforeCarrier`
        if ($moduleList = Hook::getModulesFromHook(Hook::getIdByName('displayBeforeCarrier'))) {
            foreach ($moduleList as $module) {
                if ($module['name'] === $this->name) {
                    return '';
                }
            }
        }

        $this->context->smarty->assign('widgetHook', 'extraCarrier');

        return $this->hookDisplayBeforeCarrier();
    }

    /**
     * Hook on admin order page
     *
     * @param array $params Hook parameters
     *
     * @return string Hook HTML
     *
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    public function hookAdminOrder($params)
    {
        $countries = array();
        $supportedCountries = static::getSupportedCountries();
        if (isset($supportedCountries['data']['countries'][0])) {
            $euCountries = array_map(function ($item) {
                $values = array_values($item);

                return Tools::strtoupper($values[0]);
            }, static::getEUCountries());
            foreach (array_keys($supportedCountries['data']['countries'][0]) as $iso) {
                if (Tools::strtoupper($iso) === 'BE') {
                    continue;
                }

                if (!in_array(Tools::strtoupper($iso), $euCountries)) {
                    $supportedCountries['data']['countries'][0][$iso]['region'] = 'CD';
                } else {
                    $supportedCountries['data']['countries'][0][$iso]['region'] = 'EU';
                }
            }
            $countryIsos = array_keys($supportedCountries['data']['countries'][0]);
            foreach (Country::getCountries($this->context->language->id) as $country) {
                if (in_array(Tools::strtoupper($country['iso_code']), $countryIsos)) {
                    $countries[Tools::strtoupper($country['iso_code'])] = array(
                        'iso_code' => Tools::strtoupper($country['iso_code']),
                        'name'     => $country['name'],
                        'region'   => $supportedCountries['data']['countries'][0]
                                      [Tools::strtoupper($country['iso_code'])]['region'],
                    );
                }
            }
        }

        $order = new Order($params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $this->context->smarty->assign(
            array(
                'mpbIdOrder'           => (int) $params['id_order'],
                'mpbConcept'           => MPBpostDeliveryOption::getByOrder((int) $params['id_order']),
                'mpbPreAlerted'        => mypa_json_encode(MPBpostOrder::getByOrderIds(array((int) $params['id_order']))),
                'mpbProcessUrl'        => $this->baseUrlWithoutToken.'&token='.Tools::getAdminTokenLite('AdminModules').'&ajax=1',
                'mpbModuleDir'         => __PS_BASE_URI__."modules/{$this->name}/",
                'mpbJsCountries'       => $countries,
                'mpbLogApi'            => (bool) Configuration::get(static::LOG_API),
                'mpbAsync'             => (bool) Configuration::get(static::DEV_MODE_ASYNC),
                'mpbInvoiceSuggestion' => MPBpostTools::getInvoiceSuggestion($order),
                'mpbWeightSuggestion'  => MPBpostTools::getWeightSuggestion($order),
                'mpbPaperSize'         => @json_decode(Configuration::get(static::PAPER_SELECTION)),
                'mpbAskPapersize'      => Configuration::get(static::ASK_PAPER_SELECTION),
                'mpbCurrency'          => Context::getContext()->currency,
            )
        );

        return $this->display(__FILE__, 'views/templates/admin/orderpage/adminorderdetail.tpl');
    }

    /**
     * Validate order hook
     *
     * @param array $params
     *
     * @return void
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public function hookActionValidateOrder($params)
    {
        /** @var Order $order */
        $order = $params['order'];

        /** @var Cart $cart */
        $cart = $params['cart'];

        $carrier = new Carrier($order->id_carrier);
        $address = new Address($order->id_address_delivery);
        $country = new Country($address->id_country);
        $customer = new Customer($order->id_customer);

        $address->email = $customer->email;
        $deliveryOption = MPBpostDeliveryOption::getRawByCartId($cart->id);
        $mpcs = MPBpostCarrierDeliverySetting::getByCarrierReference($carrier->id_reference);
        // Check if the chosen carrier supports the MyParcel pickup or delivery options
        if (!$mpcs
            || !Validate::isLoadedObject($mpcs)
            || !isset($deliveryOption->type)
            || !$mpcs->{$deliveryOption->type}
            || !in_array($deliveryOption->type, array('delivery', 'pickup'))
            || !in_array(Tools::strtoupper($country->iso_code), array('NL', 'BE'))
        ) {
            MPBpostDeliveryOption::removeDeliveryOption($cart->id);

            return;
        }

        $concept = MPBpostDeliveryOption::createConcept($order, $deliveryOption, $address);

        try {
            // Convert the pickup address to a PrestaShop address when enabled
            if ($deliveryOption->type === 'pickup' && Configuration::get(static::USE_PICKUP_ADDRESS)) {
                $newAddress = MPBpostTools::getCustomerAddress($customer->id, $deliveryOption->data->location_code);
                if (!Validate::isLoadedObject($newAddress)) {
                    $newAddress->id_customer = $customer->id;
                    $newAddress->alias = "myparcel-{$deliveryOption->data->location_code}";
                    $newAddress->company = $deliveryOption->data->location;
                    $newAddress->firstname = $address->firstname;
                    $newAddress->lastname = $address->lastname;
                    $newAddress->postcode = $deliveryOption->data->postal_code;
                    $newAddress->city = $deliveryOption->data->city;
                    $newAddress->id_country = $address->id_country;
                    $newAddress->phone = $deliveryOption->data->phone_number;

                    // Figure out which address fields are active and parse the MyParcel formatted address
                    list (, $housenumberField, $extensionField) = $addressFields = MPBpostTools::getAddressLineFields($newAddress->id_country);
                    $addressLine = "{$deliveryOption->data->street} {$deliveryOption->data->number}";
                    $addressFields = array_filter($addressFields, function ($item) {
                        return (bool) $item;
                    });

                    // Convert to a PrestaShop address
                    switch (array_sum($addressFields)) {
                        case 2:
                            if (preg_match(static::SPLIT_STREET_REGEX, $addressLine, $m)) {
                                $newAddress->address1 = $deliveryOption->data->street;
                                $newAddress->{$housenumberField} = isset($m['street_suffix']) ? $m['street_suffix'] : '';
                            } else {
                                $newAddress->address1 = $addressLine;
                            }
                            break;
                        case 3:
                            if (preg_match(static::SPLIT_STREET_REGEX, $addressLine, $m)) {
                                $newAddress->address1 = $deliveryOption->data->street;
                                $newAddress->{$housenumberField} = isset($m['number']) ? $m['number'] : '';
                                $newAddress->{$extensionField} = isset($m['box_number']) ? $m['box_number'] : '';
                            } else {
                                $newAddress->address1 = $addressLine;
                            }
                            break;
                        default:
                            $newAddress->address1 = $addressLine;
                            break;
                    }

                    $newAddress->save();
                }

                $order->id_address_delivery = $newAddress->id;
                $order->update();
            }
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel module error while saving pickup address: {$e->getMessage()}");
        }

        if (isset($deliveryOption->data)) {
            $deliveryOption = array(
                'data'         => $deliveryOption->data,
                'type'         => (isset($deliveryOption->type) ? (string) $deliveryOption->type : 'delivery'),
                'extraOptions' => (isset($deliveryOption->extraOptions) ? $deliveryOption->extraOptions : array()),
                'concept'      => $concept,
            );
        } else {
            $deliveryOption = array(
                'concept' => $concept,
            );
        }
        MPBpostDeliveryOption::saveRawDeliveryOption(mypa_json_encode($deliveryOption), $cart->id);
    }

    /**
     * Edit order grid display
     *
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        if (!Tools::isSubmit('exportorder')) {
            if (isset($params['select'])) {
                $params['select'] .= ",\n\t\tmpbdo.`mpbpost_delivery_option`, IFNULL(mpbdo.`date_delivery`, '1970-01-01 00:".
                    "00:00') as `mpbpost_date_delivery`, mpbdo.`pickup` AS `mpbpost_pickup`, UPPER(country.`iso_code`) AS `mpbpost_country_is".
                    "o`, 1 as `mpbpost_void_1`, 1 as `mpbpost_void_2`";
            }
            if (isset($params['join'])) {
                $params['join'] .= "\n\t\tLEFT JOIN `"._DB_PREFIX_.bqSQL(MPBpostDeliveryOption::$definition['table'])."` ".
                    "mpbdo ON (mpbdo.`id_cart` = a.`id_cart`)";
            }
            if (isset($params['fields'])) {
                $supportedCarrierModules = array_filter(Hook::getHookModuleExecList(Tools::substr(lcfirst(__FUNCTION__), 4, Tools::strlen(__FUNCTION__))), function ($item) {
                    $module = Module::getInstanceByName($item['module']);
                    if (!Validate::isLoadedObject($module)) {
                        return false;
                    }

                    return in_array($item['module'], array('myparcel', 'myparcelbpost', 'postnl'))
                        && version_compare($module->version, '2.2.0', '>=');
                });
                $lastSupportedCarrierModule = end($supportedCarrierModules);
                reset($supportedCarrierModules); // Reset array pointer
                if (!empty($supportedCarrierModules) && $lastSupportedCarrierModule['module'] !== $this->name) {
                    return;
                }
                $carrierNames = array();
                foreach ($supportedCarrierModules as $supportedCarrierModule) {
                    $name = '';
                    switch ($supportedCarrierModule['module']) {
                        case 'myparcel':
                            $name = 'MyParcel';
                            break;
                        case 'myparcelbpost':
                            $name = 'bpost';
                            break;
                        case 'bpost':
                            $name = 'bpost';
                            break;
                    }
                    if ($name) {
                        $carrierNames[$supportedCarrierModule['module']] = $name;
                    }
                }

                if (!Configuration::get(static::DEV_MODE_HIDE_PREFERRED)) {
                    $params['fields']['mpbpost_date_delivery'] = array(
                        'title'           => $this->l('Preferred delivery date'),
                        'class'           => 'fixed-width-lg',
                        'callback'        => 'printOrderGridPreference',
                        'callback_object' => 'MPBpostTools',
                        'filter_key'      => 'mpbdo!date_delivery',
                        'type'            => 'date',
                    );
                }
                $params['fields']['mpbpost_void_1'] = array(
                    'title'           => implode(' / ', array_values($carrierNames)),
                    'class'           => 'fixed-width-lg',
                    'callback'        => 'printMyParcelTrackTrace',
                    'callback_object' => 'MPBpostTools',
                    'search'          => false,
                    'orderby'         => false,
                    'remove_onclick'  => true,
                );
                $params['fields']['mpbpost_void_2'] = array(
                    'title'           => '',
                    'class'           => 'text-nowrap',
                    'callback'        => 'printMyParcelIcon',
                    'callback_object' => 'MPBpostTools',
                    'search'          => false,
                    'orderby'         => false,
                    'remove_onclick'  => true,
                );
            }
        }
    }

    /**
     * Admin logs display
     *
     * @param array $params
     *
     * @since 2.2.0
     */
    public function hookActionAdminLogsListingFieldsModifier($params)
    {
        if (isset($params['fields'])) {
            $params['fields']['message'] = array(
                'title'           => $this->l('Message'),
                'callback'        => 'printLogMessage',
                'callback_object' => 'MPBpostTools',
            );
        }
    }

    /**
     * Delete log files for the Customer
     *
     * @param array $email
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 2.2.0
     * @throws Adapter_Exception
     */
    public function hookActionDeleteGDPRCustomer($email)
    {
        if (empty($email['id_customer'])) {
            $customer = new Customer();
            $customer->getByEmail($email['email']);
            if (!Validate::isLoadedObject($customer)) {
                return json_encode($this->l('MyParcel BE: Unable to delete customer using email.'));
            }
        } else {
            $customer = new Customer($email['id_customer']);
        }

        $success = true;

        $success &= Db::getInstance()->delete('log', '`object_type` = \'Customer\' AND `object_id` = '.(int) $customer->id);

        $sql = new DbQuery();
        $sql->select('mdo.`id_mpbpost_delivery_option`');
        $sql->from(bqSQL(Cart::$definition['table']), 'ca');
        $sql->innerJoin(bqSQL(MPBpostDeliveryOption::$definition['table']), 'mdo', 'mdo.`id_cart` = ca.`id_cart`');
        $sql->innerJoin(bqSQL(Customer::$definition['table']), 'cu', 'ca.`id_customer` = cu.`id_customer`');
        $sql->where('cu.`email` = \''.pSQL($customer->email).'\'');

        $idOptions = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (is_array($idOptions)) {
            $idOptions = array_column($idOptions, 'id_mpbpost_delivery_option');
            foreach ($idOptions as $idOption) {
                $deliveryOption = new MPBpostDeliveryOption($idOption);
                $concept = mypa_dot(@json_decode($deliveryOption->myparcel_delivery_option, true));
                $concept->set('concept.recipient.person', '');
                $concept->set('concept.recipient.street', '');
                $concept->set('concept.recipient.street_additional_info', '');
                $concept->set('concept.recipient.number', '');
                $concept->set('concept.recipient.number_suffix', '');
                $concept->set('concept.recipient.postal_code', '');
                $concept->set('concept.recipient.city', '');
                $concept->set('concept.recipient.region', '');
                $concept->set('concept.recipient.phone', '');
                $concept->set('concept.recipient.email', '');
                $concept->set('extraOptions.gdpr', true);
                $concept->delete('pickup');
                $deliveryOption->myparcel_delivery_option = mypa_json_encode($concept);
                try {
                    $deliveryOption->save();
                } catch (PrestaShopException $e) {
                    Logger::addLog("MyParcel BE GDPR error: {$e->getMessage()}");
                }
            }
        }

        $sql = new DbQuery();
        $sql->select('`id_order`');
        $sql->from(bqSQL(Order::$definition['table']), 'o');
        $sql->innerJoin(bqSQL(Customer::$definition['table']), 'cu', 'o.`id_customer` = cu.`id_customer`');
        $sql->where('cu.`email` = \''.pSQL($customer->email).'\'');
        $idOrders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (is_array($idOrders)) {
            $idOrders = array_column($idOrders, 'id_order');
            if (!empty($idOrders)) {
                Db::getInstance()->update(
                    bqSQL(MPBpostOrder::$definition['table']),
                    array(
                        'shipment' => '',
                    ),
                    '`id_order` IN ('.implode(',', array_map('intval', $idOrders)).')'
                );
            }

        }

        if ($success) {
            return json_encode(true);
        }

        return json_encode($this->l('MyParcel BE: Unable to delete customer using email.'));
    }

    /**
     * @param array $email
     *
     * @return null|string|string[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 2.2.0
     */
    public function hookActionExportGDPRData($email)
    {
        if (empty($email['id_customer'])) {
            $customer = new Customer();
            $customer->getByEmail($email['email']);
            if (!Validate::isLoadedObject($customer)) {
                return mypa_json_encode($this->l('No information found for this customer'));
            }
        } else {
            $customer = new Customer($email['id_customer']);
        }
        /** @var Customer $customer */
        $orderSql = new DbQuery();
        $orderSql->select('o.`reference`, mdo.`date_delivery`, mdo.`pickup`, mdo.`mpbpost_delivery_option`');
        $orderSql->from(bqSQL(Cart::$definition['table']), 'ca');
        $orderSql->innerJoin(bqSQL(MPBpostDeliveryOption::$definition['table']), 'mdo', 'mdo.`id_cart` = ca.`id_cart`');
        $orderSql->innerJoin(bqSQL(Order::$definition['table']), 'o', 'o.`id_cart` = ca.`id_cart`');
        $orderSql->where('o.`id_customer` = '.(int) $customer->id);
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($orderSql);
        if (!is_array($results) || empty($results)) {
            return mypa_json_encode($this->l('No information found for this customer'));
        }

        foreach ($results as &$result) {
            $deliveryOption = mypa_dot(@json_decode($result['myparcel_delivery_option'], true));
            $result = array(
                'order'                    => $result['reference'],
                'date_delivery'            => $result['date_delivery'],
                'pickup'                   => $result['pickup'],
                'signature_preferred'      => ($deliveryOption->get('extraOptions.signed') ? 'true' : 'false'),
            );
        }

        return mypa_json_encode($results);
    }

    /**
     * Get MyParcel locale
     *
     * @return string
     *
     * @since 2.0.9
     */
    public static function getLocale()
    {
        $language = Context::getContext()->language;

        return (Tools::strlen($language->language_code) >= 5)
            ? Tools::strtolower(Tools::substr($language->language_code, 0, 2)).'-'.Tools::strtoupper(
                Tools::substr($language->language_code, 3, 2)
            )
            : Tools::strtolower(Tools::substr($language->language_code, 0, 2)).'-'.Tools::strtoupper(
                Tools::substr($language->language_code, 0, 2)
            );
    }

    /**
     * Get columns to display on the back office ordergrid
     *
     * @return array
     */
    public function getColumns()
    {
        return array(
            'delivery_date' => array('MPBpostTools', 'printOrderGridPreference'),
            'status'        => array('MPBpostTools', 'printMyParcelTrackTrace'),
            'concept'       => array('MPBpostTools', 'printMyParcelIcon'),
        );
    }

    /**
     * Get order shipping costs external
     *
     * @param array $params Hook parameters
     *
     * @return bool|float
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public function getOrderShippingCostExternal($params)
    {
        return $this->getOrderShippingCost($params, 0);
    }

    /**
     * Get shipping costs for order
     *
     * @param array $params       Hook parameters
     * @param float $shippingCost Shipping costs before calling this method
     *
     * @return bool|float Processed shipping costs
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public function getOrderShippingCost($params, $shippingCost)
    {
        if (!Module::isEnabled($this->name) || $shippingCost === false) {
            return false;
        }

        if (get_class($params) === __CLASS__) {
            $cart = $this->context->cart;
        } else {
            /** @var Cart $cart */
            $cart = $params;
        }

        // Detect carrier settings
        $carrier = new Carrier((int) $this->id_carrier);
        $deliveryOption = MPBpostDeliveryOption::getRawByCartId($cart->id, false);
        $deliverySetting = MPBpostCarrierDeliverySetting::getByCarrierReference($carrier->id_reference);
        if (!Validate::isLoadedObject($deliverySetting)) {
            // External module name has been set to `myparcelbpost`, but not a single delivery setting is available
            return false;
        }
        $address = new Address($cart->id_address_delivery);
        $countryIso = (string) Country::getIsoById($address->id_country);
        if (!$countryIso) {
            $countryIso = Context::getContext()->country->iso_code;
        }
        $countryIso = Tools::strtoupper($countryIso);

        $extraCosts = 0;
        if (isset($deliveryOption->type) && $deliveryOption->type === 'pickup') {
            $extraCosts += (float) $deliverySetting->pickup_fee_tax_incl;
        } elseif (isset($deliveryOption->extraOptions)) {
            $selectedOptions = $deliveryOption->extraOptions;
            if (in_array($countryIso, array('BE'))
                && isset($deliveryOption->type)
            ) {
                if ($deliveryOption->type === 'delivery') {
                    if ($selectedOptions->signed) {
                        $extraCosts += (float) $deliverySetting->signed_fee_tax_incl;
                    }
                }
                if (date('w', strtotime($deliveryOption->data->date)) == 6) {
                    $extraCosts += (float) $deliverySetting->saturday_delivery_fee_tax_incl;
                }
            }
        }
        // Calculate the conversion to make before displaying prices
        // It is comprised of taxes and currency conversions
        /** @var Currency $defaultCurrency */
        $defaultCurrency = Currency::getCurrencyInstance(Configuration::get(' PS_CURRENCY_DEFAULT'));
        /** @var Currency $currentCurrency */
        $currentCurrency = $this->context->currency;
        $conversion = $defaultCurrency->conversion_rate * $currentCurrency->conversion_rate;
        // Extra costs are entered with 21% VAT
        $taxRate = 1 / 1.21;

        $shippingCost = (float) $this->calcPackageShippingCost(
            $cart,
            $carrier->id,
            false,
            null,
            null,
            null,
            false
        );

        return $extraCosts * $conversion * $taxRate + $shippingCost;
    }

    /**
     * Return package shipping cost
     *
     * @param Cart         $cart           Cart object
     * @param int          $idCarrier      Carrier ID (default : current carrier)
     * @param bool         $useTax         Apply taxes
     * @param Country|null $defaultCountry Default country
     * @param array|null   $productList    List of product concerned by the shipping.
     *                                     If null, all the product of the cart are used
     *                                     to calculate the shipping cost
     * @param int|null     $idZone         Zone ID
     * @param bool         $recursion      Enable module recursion?
     *
     * @return float|false Shipping total, false if not possible
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public function calcPackageShippingCost(
        $cart,
        $idCarrier,
        $useTax = true,
        $defaultCountry = null,
        $productList = null,
        $idZone = null,
        $recursion = true
    ) {
        if ($cart->isVirtualCart()) {
            return 0;
        }

        if (!$defaultCountry) {
            $defaultCountry = Context::getContext()->country;
        }

        if (!is_null($productList)) {
            foreach ($productList as $key => $value) {
                if ($value['is_virtual'] == 1) {
                    unset($productList[$key]);
                }
            }
        }

        if (is_null($productList)) {
            $products = $cart->getProducts();
        } else {
            $products = $productList;
        }

        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $addressId = (int) $cart->id_address_invoice;
        } elseif (is_array($productList) && count($productList)) {
            $prod = array_values($productList);
            $prod = $prod[0];
            $addressId = (int) $prod['id_address_delivery'];
        } else {
            $addressId = null;
        }
        if (!Address::addressExists($addressId)) {
            $addressId = null;
        }

        if (is_null($idCarrier) && !empty($cart->id_carrier)) {
            $idCarrier = (int) $cart->id_carrier;
        }

        $cacheId = $this->name.'MyParcelBpostconfPackageShippingCost_'.(int) $cart->id.'_'.(int) $addressId.'_'
            .(int) $idCarrier.'_'.(int) $useTax.'_'.(int) $defaultCountry->id;
        if ($products) {
            foreach ($products as $product) {
                $cacheId .= '_'.(int) $product['id_product'].'_'.(int) $product['id_product_attribute'];
            }
        }

        if (Cache::isStored($cacheId)) {
            return Cache::retrieve($cacheId);
        }

        // Order total in default currency without fees
        $orderTotal = $cart->getOrderTotal(true, Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $productList);

        // Start with shipping cost at 0
        $shippingCost = 0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        if (!isset($idZone)) {
            // Get id zone
            if (!$cart->isMultiAddressDelivery()
                && isset($cart->id_address_delivery) // Be careful, id_address_delivery is not useful on 1.5
                && $cart->id_address_delivery
                && Customer::customerHasAddress($cart->id_customer, $cart->id_address_delivery)
            ) {
                $idZone = Address::getZoneById((int) $cart->id_address_delivery);
            } else {
                if (!Validate::isLoadedObject($defaultCountry)) {
                    $defaultCountry = new Country(
                        Configuration::get('PS_COUNTRY_DEFAULT'),
                        Configuration::get('PS_LANG_DEFAULT')
                    );
                }

                $idZone = (int) $defaultCountry->id_zone;
            }
        }

        if ($idCarrier && !$cart->isCarrierInRange((int) $idCarrier, (int) $idZone)) {
            $idCarrier = '';
        }

        if (empty($idCarrier)
            && $cart->isCarrierInRange((int) Configuration::get('PS_CARRIER_DEFAULT'), (int) $idZone)
        ) {
            $idCarrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        }

        $totalPackageWithoutShippingTaxInc = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, $productList);

        if (!isset(static::$cachedCarriers[$idCarrier])) {
            static::$cachedCarriers[$idCarrier] = new Carrier((int) $idCarrier);
        }

        /** @var Carrier $carrier */
        $carrier = static::$cachedCarriers[$idCarrier];

        $shippingMethod = $carrier->getShippingMethod();
        // Get only carriers that are compliant with shipping method
        if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT
                && $carrier->getMaxDeliveryPriceByWeight((int) $idZone) === false)
            || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE
                && $carrier->getMaxDeliveryPriceByPrice((int) $idZone) === false)
        ) {
            return false;
        }

        // If out-of-range behavior carrier is set on "Deactivate carrier"
        if ($carrier->range_behavior) {
            $checkDeliveryPriceByWeight = Carrier::checkDeliveryPriceByWeight(
                $idCarrier,
                $cart->getTotalWeight(),
                (int) $idZone
            );

            $totalOrder = $totalPackageWithoutShippingTaxInc;
            $checkDeliveryPriceByPrice = Carrier::checkDeliveryPriceByPrice(
                $idCarrier,
                $totalOrder,
                (int) $idZone,
                (int) $cart->id_currency
            );

            // Get only carriers that have a range compatible with cart
            if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT && !$checkDeliveryPriceByWeight)
                || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE && !$checkDeliveryPriceByPrice)
            ) {
                return false;
            }
        }

        if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
            $shipping = $carrier->getDeliveryPriceByWeight($cart->getTotalWeight($productList), (int) $idZone);
        } else {
            $shipping = $carrier->getDeliveryPriceByPrice($orderTotal, (int) $idZone, (int) $cart->id_currency);
        }

        /**
         * @global float $minShippingPrice -- Could be global
         *
         * @codingStandardsIgnoreStart
         */
        if (!isset($minShippingPrice)) {
            $minShippingPrice = $shipping;
        }
        /**
         * @codingStandardsIgnoreEnd
         */

        if ($shipping <= $minShippingPrice) {
            $idCarrier = (int) $idCarrier;
            $minShippingPrice = $shipping;
        }

        if (empty($idCarrier)) {
            $idCarrier = '';
        }

        if (!isset(static::$cachedCarriers[$idCarrier])) {
            static::$cachedCarriers[$idCarrier] = new Carrier(
                (int) $idCarrier,
                Configuration::get('PS_LANG_DEFAULT')
            );
        }

        $carrier = static::$cachedCarriers[$idCarrier];

        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::isLoadedObject($carrier)) {
            Cache::store($cacheId, 0);

            return 0;
        }
        $shippingMethod = $carrier->getShippingMethod();

        if (!$carrier->active) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cacheId, 0);

            return 0;
        }

        // Select carrier tax
        if ($useTax && !Tax::excludeTaxeOption()) {
            try {
                $address = Address::initialize((int) $addressId);
            } catch (PrestaShopException $e) {
                $address = new Address();
            }

            if (Configuration::get('PS_ATCP_SHIPWRAP')) {
                $carrierTax = 0;
            } else {
                $carrierTax = $carrier->getTaxesRate($address);
            }
        }

        try {
            $configuration = Configuration::getMultiple(
                array(
                    'PS_SHIPPING_FREE_PRICE',
                    'PS_SHIPPING_HANDLING',
                    'PS_SHIPPING_METHOD',
                    'PS_SHIPPING_FREE_WEIGHT',
                )
            );
        } catch (PrestaShopException $e) {
            return false;
        }

        // Free fees
        $freeFeesPrice = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $freeFeesPrice = Tools::convertPrice(
                (float) $configuration['PS_SHIPPING_FREE_PRICE'],
                Currency::getCurrencyInstance((int) $cart->id_currency)
            );
        }
        $orderTotalwithDiscounts = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($orderTotalwithDiscounts >= (float) ($freeFeesPrice) && (float) ($freeFeesPrice) > 0) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT'])
            && $cart->getTotalWeight() >= (float) $configuration['PS_SHIPPING_FREE_WEIGHT']
            && (float) $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0
        ) {
            Cache::store($cacheId, $shippingCost);

            return $shippingCost;
        }

        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($idZone)) {
                // Get id zone
                if (isset($cart->id_address_delivery)
                    && $cart->id_address_delivery
                    && Customer::customerHasAddress($cart->id_customer, $cart->id_address_delivery)
                ) {
                    $idZone = Address::getZoneById((int) $cart->id_address_delivery);
                } else {
                    $idZone = (int) $defaultCountry->id_zone;
                }
            }

            if (($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT
                    && !Carrier::checkDeliveryPriceByWeight($carrier->id, $cart->getTotalWeight(), (int) $idZone))
                || ($shippingMethod == Carrier::SHIPPING_METHOD_PRICE
                    && !Carrier::checkDeliveryPriceByPrice(
                        $carrier->id,
                        $totalPackageWithoutShippingTaxInc,
                        $idZone,
                        (int) $cart->id_currency
                    )
                )
            ) {
                $shippingCost += 0;
            } else {
                if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shippingCost += $carrier->getDeliveryPriceByWeight($cart->getTotalWeight($productList), $idZone);
                } else { // by price
                    $shippingCost += $carrier->getDeliveryPriceByPrice($orderTotal, $idZone, (int) $cart->id_currency);
                }
            }
        } else {
            if ($shippingMethod == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shippingCost += $carrier->getDeliveryPriceByWeight($cart->getTotalWeight($productList), $idZone);
            } else {
                $shippingCost += $carrier->getDeliveryPriceByPrice($orderTotal, $idZone, (int) $cart->id_currency);
            }
        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shippingCost += (float) $configuration['PS_SHIPPING_HANDLING'];
        }

        // Additional Shipping Cost per product
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shippingCost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }

        $shippingCost = Tools::convertPrice($shippingCost, Currency::getCurrencyInstance((int) $cart->id_currency));

        if ($carrier->shipping_external) {
            $moduleName = $carrier->external_module_name;
            /** @var CarrierModule $module */
            $module = Module::getInstanceByName($moduleName);
            if (Validate::isLoadedObject($module)) {
                if (property_exists($module, 'id_carrier')) {
                    $module->id_carrier = $carrier->id;
                }
                if ($recursion) {
                    if ($carrier->need_range) {
                        if (method_exists($module, 'getPackageShippingCost')) {
                            $shippingCost = $module->getPackageShippingCost($this, $shippingCost, $products);
                        } else {
                            $shippingCost = $module->getOrderShippingCost($this, $shippingCost);
                        }
                    } else {
                        $shippingCost = $module->getOrderShippingCostExternal($this);
                    }
                }
                // Check if carrier is available
                if ($shippingCost === false) {
                    Cache::store($cacheId, false);

                    return false;
                }
            } else {
                Cache::store($cacheId, false);

                return false;
            }
        }

        if (Configuration::get('PS_ATCP_SHIPWRAP')) {
            if ($useTax) {
                // With PS_ATCP_SHIPWRAP, we apply the proportionate tax rate to the shipping
                // costs. This is on purpose and required in many countries in the European Union.
                $shippingCost *= (1 + $cart->getAverageProductsTaxRate());
            }
        } else {
            // Apply tax
            if ($useTax && isset($carrierTax)) {
                $shippingCost *= 1 + ($carrierTax / 100);
            }
        }

        $mdo = MPBpostDeliveryOption::getRawByCartId($cart->id);

        if (isset($mdo->type) && $mdo->type == 'timeframe') {
            if (isset($mdo->data->time->price_comment)) {
                switch ($mdo->data->time->price_comment) {
                    case 'morning':
                        return 4;
                    case 'night':
                        return 5;
                }
            }
        }

        $shippingCost = (float) Tools::ps_round((float) $shippingCost, 2);
        Cache::store($cacheId, $shippingCost);

        return $shippingCost;
    }

    /**
     * Get Carrier IDs by references
     *
     * @param array $references Array with reference IDs
     *
     * @return array|bool Carrier IDs
     *
     * @since 2.0.0
     */
    protected function getCarriersByReferences($references)
    {
        if (empty($references) && !is_array($references)) {
            return false;
        }
        $sql = new DbQuery();
        $sql->select('`id_carrier`');
        $sql->from('carrier');
        $where = '`id_reference` = '.(int) $references[0];
        for ($i = 1; $i < count($references); $i++) {
            $where .= ' OR `id_reference` = '.(int) $references[$i];
        }
        $sql->where($where);
        try {
            $carriersDb = Db::getInstance()->executeS($sql);
        } catch (PrestaShopException $e) {
            $carriersDb = array();
        }

        $carrierIds = array();
        foreach ($carriersDb as $carrier) {
            $carrierIds[] = (int) $carrier['id_carrier'];
        }

        return $carrierIds;
    }

    /**
     * Detect whether the order has a shipping number.
     *
     * @param $order Order The order to check
     *
     * @return bool True if the order has a shipping number
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    protected function orderHasShippingNumber($order)
    {
        if (isset($order->shipping_number) && $order->shipping_number) {
            return true;
        }
        $orderCarrier = new OrderCarrier($order->getIdOrderCarrier());
        if ($orderCarrier->tracking_number) {
            return true;
        }

        return false;
    }

    /**
     * 2D array sort by key
     *
     * @param $array
     * @param $key
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function aasort(&$array, $key)
    {
        $sorter = array();
        $ret = array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }

    /**
     * Get carrier reference by delivery option ID
     *
     * @param int $idMPBpostCarrierDeliverySetting Delivery option ID
     *
     * @return int Carrier reference
     *
     * @since 2.0.0
     */
    protected function getCarrierReferenceByOptionId($idMPBpostCarrierDeliverySetting)
    {
        $sql = new DbQuery();
        $sql->select('`id_reference`');
        $sql->from(bqSQL(MPBpostCarrierDeliverySetting::$definition['table']));
        $sql->where('`'.bqSQL(MPBpostCarrierDeliverySetting::$definition['primary']).'` = '
            .(int) $idMPBpostCarrierDeliverySetting);

        try {
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        } catch (PrestaShopException $e) {
            return 0;
        }
    }

    /**
     * Add information message
     *
     * @param string $message Message
     * @param bool   $private Only display on module's configuration page
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function addInformation($message, $private = true)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->informations[] = '<a href="'.$this->baseUrl.'">'
                    .$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->informations[] = $message;
        }
    }

    /**
     * Add warning message
     *
     * @param string $message Message
     * @param bool   $private Only display on module's configuration page
     *
     * @return void
     *
     * @since 2.0.0
     */
    protected function addWarning($message, $private = true)
    {
        if (!Tools::isSubmit('configure')) {
            if (!$private) {
                $this->context->controller->warnings[] = '<a href="'.$this->baseUrl.'">'
                    .$this->displayName.': '.$message.'</a>';
            }
        } else {
            $this->context->controller->warnings[] = $message;
        }
    }

    /**
     * @param Carrier $carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function addGroups($carrier)
    {
        $groupsIds = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group) {
            $groupsIds[] = $group['id_group'];
        }

        $carrier->setGroups($groupsIds);
    }

    /**
     * @param Carrier $carrier
     *
     * @return RangePrice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     * @throws Adapter_Exception
     */
    protected function addPriceRange($carrier)
    {
        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = '0';
        $rangePrice->delimiter2 = '10000';
        $rangePrice->add();

        return $rangePrice;
    }

    /**
     * @param Carrier $carrier
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone) {
            $carrier->addZone($zone['id_zone']);
        }

        return $zones;
    }

    /**
     * Performs a basic check and return an array with errors
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.9
     */
    protected function basicCheck()
    {
        $errors = array();
        if (!Country::getByIso('NL') && !Country::getByIso('BE')) {
            $errors[] =
                $this->l('At least one of the following countries should be enabled: the Netherlands or Belgium.');
        }
        if (!Currency::getIdByIsoCode('EUR')) {
            $errors[] = $this->l('At least this currency has to be enabled: EUR');
        }

        return $errors;
    }


    /**
     * Is the old MyParcel BE version installed?
     *
     * @param bool $force Force old version check
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @since 2.2.0
     */
    protected function isOldVersionInstalled($force = false)
    {
        if (!$force && Configuration::get(static::CACHE_OLD_VERSION_INSTALLED) !== null) {
            return (bool) Configuration::get(static::CACHE_OLD_VERSION_INSTALLED);
        }

        $moduleFile = _PS_MODULE_DIR_.'myparcel/myparcel.php';
        if (file_exists($moduleFile)) {
            if (preg_match(
                '/bpost\.be/',
                file_get_contents($moduleFile)
            )) {
                Configuration::updateValue(static::CACHE_OLD_VERSION_INSTALLED, true, false, 0, 0);
                
                return true;
            }
        }
        Configuration::updateValue(static::CACHE_OLD_VERSION_INSTALLED, false, false, 0, 0);

        return false;
    }

    /**
     * Get module version
     *
     * @param string $moduleCode
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function getModuleVersion($moduleCode)
    {
        $sql = new DbQuery();
        $sql->select('`version`');
        $sql->from('module');
        $sql->where('`name` = \''.pSQL($moduleCode).'\'');

        return (string) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }
}

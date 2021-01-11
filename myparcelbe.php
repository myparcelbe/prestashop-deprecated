<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class MyParcelBE extends CarrierModule
{
    use \Gett\MyparcelBE\Module\Hooks\DisplayAdminProductsExtra;
    use \Gett\MyparcelBE\Module\Hooks\DisplayBackOfficeHeader;
    use \Gett\MyparcelBE\Module\Hooks\OrdersGridHooks;
    use \Gett\MyparcelBE\Module\Hooks\FrontHooks;
    use \Gett\MyparcelBE\Module\Hooks\LegacyOrderPageHooks;
    use \Gett\MyparcelBE\Module\Hooks\OrderLabelHooks;
    use \Gett\MyparcelBE\Module\Hooks\CarrierHooks;
    public $baseUrl;
    public $id_carrier;
    public $migrations = [
        \Gett\MyparcelBE\Database\CreateProductConfigurationTableMigration::class,
        \Gett\MyparcelBE\Database\CreateCarrierConfigurationTableMigration::class,
        \Gett\MyparcelBE\Database\CreateOrderLabelTableMigration::class,
        \Gett\MyparcelBE\Database\CreateDeliverySettingTableMigration::class,
    ];
    public $carrierStandardShippingCost = [];
    public $cartCarrierStandardShippingCost = null;

    public $configItems = [
        \Gett\MyparcelBE\Constant::POSTNL_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::BPOST_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::DPD_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::STATUS_CHANGE_MAIL_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::SENT_ORDER_STATE_FOR_DIGITAL_STAMPS_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::LABEL_SCANNED_ORDER_STATUS_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::DELIVERED_ORDER_STATUS_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::ORDER_NOTIFICATION_AFTER_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::IGNORE_ORDER_STATUS_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::WEBHOOK_ID_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::API_LOGGING_CONFIGURATION_NAME,// Keep the API key

        \Gett\MyparcelBE\Constant::PACKAGE_TYPE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::ONLY_RECIPIENT_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::AGE_CHECK_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::PACKAGE_FORMAT_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::RETURN_PACKAGE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::SIGNATURE_REQUIRED_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::INSURANCE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::CUSTOMS_FORM_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::CUSTOMS_CODE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::DEFAULT_CUSTOMS_CODE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::CUSTOMS_ORIGIN_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::DEFAULT_CUSTOMS_ORIGIN_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::CUSTOMS_AGE_CHECK_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::SHARE_CUSTOMER_EMAIL_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::SHARE_CUSTOMER_PHONE_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::LABEL_DESCRIPTION_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::LABEL_OPEN_DOWNLOAD_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::LABEL_SIZE_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::LABEL_POSITION_CONFIGURATION_NAME,
        \Gett\MyparcelBE\Constant::LABEL_PROMPT_POSITION_CONFIGURATION_NAME,

        \Gett\MyparcelBE\Constant::LABEL_CREATED_ORDER_STATUS_CONFIGURATION_NAME,
    ];

    public $hooks = [
        'displayAdminProductsExtra',
        'displayBackOfficeHeader',
        'actionProductUpdate',
        'displayCarrierExtraContent',
        'actionCarrierUpdate',
        'displayHeader',
        'actionCarrierProcess',
        'actionOrderGridDefinitionModifier',
        'actionAdminControllerSetMedia',
        'actionOrderGridQueryBuilderModifier',
        'actionAdminOrdersListingFieldsModifier',
        'displayAdminListBefore',
        'actionAdminControllerSetMedia',
        'displayAdminOrderMainBottom',
        'actionObjectGettMyParcelBEOrderLabelAddAfter',
        'actionObjectGettMyParcelBEOrderLabelUpdateAfter',
        'displayInvoice',
    ];
    /** @var string $baseUrlWithoutToken */
    protected $baseUrlWithoutToken;

    public function __construct()
    {
        $this->name = 'myparcelbe';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Gett';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        if (!empty(Context::getContext()->employee->id)) {
            $this->baseUrlWithoutToken = $this->getAdminLink(
                'AdminModules',
                false,
                [
                    'configure' => $this->name,
                    'tab_module' => $this->tab,
                    'module_name' => $this->name,
                ]
            );
            $this->baseUrl = $this->getAdminLink(
                'AdminModules',
                true,
                [
                    'configure' => $this->name,
                    'tab_module' => $this->tab,
                    'module_name' => $this->name,
                ]
            );
        }
        $this->displayName = $this->l('MyParcelBE');
        $this->description = $this->l('PrestaShop module to intergratie with MyParcel NL and MyParcel BE');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function getAdminLink(string $controller, bool $withToken = true, array $params = [])
    {
        $url = parse_url($this->context->link->getAdminLink($controller, $withToken));
        $url['query'] = isset($url['query']) ? $url['query'] : '';
        parse_str($url['query'], $query);
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            $url['query'] = http_build_query($query + $params, PHP_QUERY_RFC1738);
        } else {
            $url['query'] = http_build_query($query + $params);
        }

        return $this->mypa_stringify_url($url);
    }

    public function getContent()
    {
        $configuration = new \Gett\MyparcelBE\Module\Configuration\Configure($this);

        $this->context->smarty->assign([
            'menutabs' => $configuration->initNavigation(),
            'ajaxUrl' => $this->baseUrlWithoutToken,
        ]);

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->display(__FILE__, 'views/templates/admin/navbar.tpl');

        return $output . $configuration(Tools::getValue('menu'));
    }

    public function getOrderShippingCost($cart, $shipping_cost)
    {
        $this->carrierStandardShippingCost[(int) $this->id_carrier] = Tools::ps_round($shipping_cost, 2);
        if ($this->id_carrier != $cart->id_carrier) {
            return $shipping_cost;
        }
        if (!empty($this->context->controller->requestOriginalShippingCost)) {
            return $shipping_cost;
        }

        $this->cartCarrierStandardShippingCost = Tools::ps_round($shipping_cost, 2);

        $myParcelCost = 0;
        $deliverySettings = Tools::getValue('myparcel-delivery-options', false);
        if ($deliverySettings) {
            $deliverySettings = json_decode($deliverySettings, true);
        } else {
            $deliverySettings = $this->getDeliverySettingsByCart((int) $cart->id);
        }

        if (empty($deliverySettings)) {
            return $shipping_cost;
        }

        $isPickup = (isset($deliverySettings['isPickup'])) ? $deliverySettings['isPickup'] : false;
        if ($isPickup) {
            $myParcelCost += (float) \Gett\MyparcelBE\Service\CarrierConfigurationProvider::get(
                $cart->id_carrier,
                'pricePickup'
            );
        } else {
            $deliveryType = (isset($deliverySettings['deliveryType'])) ? $deliverySettings['deliveryType'] : 'standard';
            if ($deliveryType !== 'standard') {
                $priceHourInterval = 'price' . ucfirst($deliveryType) . 'Delivery';
                $myParcelCost += (float)\Gett\MyparcelBE\Service\CarrierConfigurationProvider::get(
                    $cart->id_carrier,
                    $priceHourInterval
                );
            }
            if (!empty($deliverySettings['shipmentOptions']['only_recipient'])) {
                $myParcelCost += (float) \Gett\MyparcelBE\Service\CarrierConfigurationProvider::get(
                    $cart->id_carrier,
                    'priceOnlyRecipient'
                );
            }
            if (!empty($deliverySettings['shipmentOptions']['signature'])) {
                $myParcelCost += (float) \Gett\MyparcelBE\Service\CarrierConfigurationProvider::get(
                    $cart->id_carrier,
                    'priceSignature'
                );
            }
        }

        return $shipping_cost + $myParcelCost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    public function install(): bool
    {
        return parent::install()
            && (new \Gett\MyparcelBE\Module\Installer($this))();
    }

    public function uninstall(): bool
    {
        return (new \Gett\MyparcelBE\Module\Uninstaller($this))()
            && parent::uninstall();
    }

    public function appendQueryToUrl($urlString, $query = [])
    {
        $url = parse_url($urlString);
        $url['query'] = isset($url['query']) ? $url['query'] : '';
        parse_str($url['query'], $oldQuery);
        if (version_compare(phpversion(), '5.4.0', '>=')) {
            $url['query'] = http_build_query($oldQuery + $query, PHP_QUERY_RFC1738);
        } else {
            $url['query'] = http_build_query($oldQuery + $query);
        }

        return $this->mypa_stringify_url($url);
    }

    public static function updateStatus($idShipment, $barcode, $statusCode, $date = null)
    {
        throw new Exception('Duplicate function trap: MyParcel::updateStatus(). Require delete.');
        if (!$date) {
            $date = date('Y-m-d H:i:s');
        }

        $order = static::getOrderByShipmentId($idShipment);

        if (!$order->shipping_number) {
            // Checking a legacy field is allowed in this case
            static::updateOrderTrackingNumber($order, $barcode);
        }

        if ($statusCode === 14) {
            if (Configuration::get(MyParcel::DIGITAL_STAMP_USE_SHIPPED_STATUS)) {
                MyParcelOrderHistory::setShipped($idShipment, false);
            } else {
                MyParcelOrderHistory::setPrinted($idShipment, false);
            }
        } else {
            if ($statusCode >= 2) {
                MyParcelOrderHistory::setPrinted($idShipment);
            }
            if ($statusCode >= 3) {
                MyParcelOrderHistory::setShipped($idShipment);
            }
            if ($statusCode >= 7 && $statusCode <= 11) {
                MyParcelOrderHistory::setReceived($idShipment);
            }
        }

        MyParcelOrderHistory::log($idShipment, $statusCode, $date);

        return (bool) Db::getInstance()->update(
            bqSQL(static::$definition['table']),
            [
                'tracktrace' => pSQL($barcode),
                'postnl_status' => (int) $statusCode,
                'date_upd' => pSQL($date),
            ],
            'id_shipment = ' . (int) $idShipment
        );
    }

    public static function getOrderByShipmentId(int $id_shipment)
    {
        $sql = new \DbQuery();
        $sql->select('*');
        $sql->from('myparcelbe_order_label', 'mol');
        $sql->where('mol.`id_label` = ' . $id_shipment);

        $shipment = Db::getInstance()->getRow($sql);

        if ($shipment) {
            return $shipment;
        }

        return false;
    }

    public function getModuleCountry()
    {
        return $this->name === 'myparcelbe' ? 'BE' : 'NL';
    }

    public function isNL()
    {
        return $this->getModuleCountry() === 'NL';
    }

    public function isBE()
    {
        return $this->getModuleCountry() === 'BE';
    }

//    public function getCarriers()
//    {
//        $carriers = ['postnl'];
//        if ($this->isBE()) {
//            $carriers[] = 'bpost';
//            $carriers[] = 'dpd';
//        }
//
//        return $carriers;
//    }

    private function mypa_stringify_url($parsedUrl)
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
        $pass = ($user || $pass) ? "{$pass}@" : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
    }

    public function getDeliverySettingsByCart(int $idCart)
    {
        $query = new DbQuery();
        $query->select('delivery_settings');
        $query->from('myparcelbe_delivery_settings');
        $query->where('id_cart = ' . (int) $idCart);
        $query->orderBy('id_delivery_setting DESC');
        $deliverySettings = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
        if (empty($deliverySettings)) {
            return null;
        }

        return json_decode($deliverySettings, true);
    }
}

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

require_once dirname(__FILE__).'/../myparcelbpost.php';

/**
 * Class MPBpostDeliveryOption
 *
 * @since 2.0.0
 */
class MPBpostDeliveryOption extends MPBpostObjectModel
{
    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'   => 'mpbpost_delivery_option',
        'primary' => 'id_mpbpost_delivery_option',
        'fields' => array(
            'id_cart'                  => array(
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'db_type'  => 'INT(11) UNSIGNED',
            ),
            'mpbpost_delivery_option'  => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'db_type'  => 'TEXT',
            ),
            'date_delivery'            => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isDate',
                'required' => false,
                'db_type'  => 'DATETIME',
            ),
            'pickup'                   => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(255)',
            ),
        ),
    );
    /**
     * The Cart ID to which this option belongs
     *
     * @var int $id_cart
     */
    public $id_cart;
    /**
     * Raw JSON of a delivery option
     * from which the other options, that were
     * available at the time, have been stripped.
     *
     * @var string $mpbpost_delivery_option
     */
    public $mpbpost_delivery_option;
    /**
     * Preferred date of delivery
     *
     * @var string $date_delivery
     */
    public $date_delivery;
    /**
     * Information about the pickup
     * - When this variable is filled
     *   the customer has chosen to pick up at
     *   a bpost location
     *
     * @var string $pickup
     */
    public $pickup;
    // @codingStandardsIgnoreEnd

    /**
     * Get MPBpostDeliveryOption by Cart ID or Cart object
     *
     * @param int|Cart $cart
     * @param bool     $cache
     *
     * @return false|self
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getByCartId($cart, $cache = true)
    {
        if ($cart instanceof Cart) {
            $idCart = $cart->id;
        } else {
            $idCart = $cart;
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_cart` = '.(int) $idCart);

        try {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getrow($sql, $cache);
        } catch (PrestaShopException $e) {
            return false;
        }

        if (empty($result)) {
            return false;
        }

        $option = new self();
        $option->hydrate($result);

        return $option;
    }

    /**
     * Get Delivery Option info by Cart
     *
     * @param int|Cart $cart  Cart ID or object
     * @param bool     $cache Enable DB cache
     *
     * @return false|object Delivery from DB
     *
     * @since 2.0.0
     */
    public static function getRawByCartId($cart, $cache = true)
    {
        if ($cart instanceof Cart) {
            $idCart = $cart->id;
        } else {
            $idCart = $cart;
        }

        $sql = new DbQuery();
        $sql->select('`mpbpost_delivery_option`');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_cart` = '.(int) $idCart);

        try {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql, $cache);
        } catch (PrestaShopException $e) {
            return false;
        }

        if ($result) {
            return @json_decode($result);
        }

        return false;
    }

    /**
     * Save raw delivery option to DB
     *
     * @param string $deliveryOption
     * @param int    $idCart
     *
     * @return bool Indicates whether the save was successfully
     *
     * @since 2.0.0
     */
    public static function saveRawDeliveryOption($deliveryOption, $idCart)
    {
        $preferredDeliveryDay = static::getPreferredDeliveryDay(@json_decode($deliveryOption, true));
        $preferredPickup = static::getPreferredPickup(@json_decode($deliveryOption, true));

        $sql = new DbQuery();
        $sql->select('`id_cart`');
        $sql->from(bqSQL(static::$definition['table']));
        $sql->where('`id_cart` = '.(int) $idCart);

        try {
            if (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql)) {
                return (bool) Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    array(
                        'mpbpost_delivery_option' => $deliveryOption,
                        'date_delivery'            => date('Y-m-d H:i:s', strtotime($preferredDeliveryDay)),
                        'pickup'                   => bqSQL($preferredPickup),
                    ),
                    '`id_cart` = '.(int) $idCart
                );
            } else {
                return (bool) Db::getInstance()->insert(
                    bqSQL(static::$definition['table']),
                    array(
                        'mpbpost_delivery_option' => $deliveryOption,
                        'id_cart'                  => (int) $idCart,
                        'date_delivery'            => date('Y-m-d H:i:s', strtotime($preferredDeliveryDay)),
                        'pickup'                   => bqSQL($preferredPickup),
                    )
                );
            }
        } catch (PrestaShopException $e) {
            return false;
        }
    }

    /**
     * Remove the delivery option info
     *
     * @param int $idCart
     *
     * @return bool
     *
     * @since 2.0.0
     */
    public static function removeDeliveryOption($idCart)
    {
        try {
            return Db::getInstance()->update(
                bqSQL(static::$definition['table']),
                array(
                    'mpbpost_delivery_option' => null,
                ),
                '`id_cart` = '.(int) $idCart,
                1,
                true
            );
        } catch (PrestaShopException $e) {
            return false;
        }
    }

    /**
     * Get by Order ID
     *
     * @param int $range
     *
     * @return mixed
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @since 2.2.0
     */
    public static function getByOrderId($range)
    {
        $values = array_values(array_pad(static::getByOrderIds(array($range)), 1, array()));

        return $values[0];
    }

    /**
     * Get by Order IDs
     *
     * @param array $range Range of Order IDs
     *
     * @return array Array with `MPBpostDeliveryOption`s
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getByOrderIds($range)
    {
        if (empty($range)) {
            return array();
        }

        foreach ($range as &$item) {
            $item = (int) $item;
        }

        $sql = new DbQuery();
        $sql->select('o.`id_order`, mdo.`mpbpost_delivery_option`, a.*');
        $sql->from(bqSQL(static::$definition['table']), 'mdo');
        $sql->innerJoin('orders', 'o', 'mdo.`id_cart` = o.`id_cart`');
        $sql->innerJoin('address', 'a', 'o.`id_address_delivery` = a.`id_address`');
        $sql->where('o.`id_order` IN ('.implode(',', $range).')');

        try {
            $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        } catch (PrestaShopException $e) {
            $results = array();
        }

        $deliveryOptions = array();
        foreach ($results as $result) {
            $deliveryOption = @json_decode($result['mpbpost_delivery_option'], true);
            $deliveryOption['idOrder'] = (int) $result['id_order'];

            if (empty($deliveryOption['concept']) || !static::validateDeliveryOption($deliveryOption, true)) {
                $order = new Order($result['id_order']);
                $address = new Address($order->id_address_delivery);
                $deliveryOption['concept'] = static::createConcept(
                    $order,
                    static::getByOrder($order),
                    $address
                );
            }

            if ($deliveryOption['concept']) {
                $deliveryOptions[] = $deliveryOption;
            }

            // Remove ID from range array
            if (($key = array_search($result['id_order'], $range)) !== false) {
                unset($range[$key]);
            }
        }

        if (!empty($range)) {
            $deliveryOptions = array_merge($deliveryOptions, static::getConceptsByOrderIds($range));
        }

        $results = array();
        foreach ($deliveryOptions as $deliveryOption) {
            $deliveryOption['idOrder'] = (int) $deliveryOption['idOrder'];
            $results[$deliveryOption['idOrder']] = $deliveryOption;
        }
        return $results;
    }

    /**
     * @param Order                      $order
     * @param bool|MPBpostDeliveryOption $deliveryOption
     * @param Address                    $address
     *
     * @return null|array
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function createConcept($order, $deliveryOption = null, $address = null)
    {
        if (!$address) {
            $address = new Address($order->id_address_delivery);
        }

        try {
            $countryIso = Tools::strtolower(Country::getIsoById($address->id_country));
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return null;
        }

        try {
            if (isset($deliveryOption->type) && $deliveryOption->type === 'pickup'
                && in_array($countryIso, array('be'))
            ) {
                return static::createPickupConcept($address, $deliveryOption, $order);
            } elseif (in_array($countryIso, array('be'))) {
                return static::createNationalConcept($address, $deliveryOption, $order);
            } else {
                return static::createInternationalConcept($address, $order);
            }
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Create concept for national shipments
     *
     * @param Address               $address
     * @param MPBpostDeliveryOption $deliveryOption
     * @param Order|null            $order
     *
     * @return array
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public static function createPickupConcept($address, $deliveryOption, $order = null)
    {
        $email = '';
        if ($order) {
            $customer = new Customer($order->id_customer);

            if (Validate::isLoadedObject($customer)) {
                $email = $customer->email;
            }
        }

        try {
            $configuration = Configuration::getMultiple(
                array(
                    MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE,
                    MyParcelBpost::DEFAULT_CONCEPT_LARGE_PACKAGE,
                    MyParcelBpost::DEFAULT_CONCEPT_RETURN,
                    MyParcelBpost::DEFAULT_CONCEPT_INSURED,
                    MyParcelBpost::LINK_EMAIL,
                    MyParcelBpost::LINK_PHONE,
                )
            );
        } catch (PrestaShopException $e) {
        }
        if (isset($deliveryOption->type) && $deliveryOption->type === 'pickup') {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE] = MyParcelBpost::TYPE_PARCEL;
        }

        preg_match(MyParcelBpost::SPLIT_STREET_REGEX, MPBpostTools::getAddressLine($address), $matches);
        $street = isset($matches['street']) ? $matches['street'] : '';
        $houseNumber = isset($matches['street_suffix']) ? $matches['street_suffix'] : '';

        $countryIso = Tools::strtolower(Country::getIsoById($address->id_country));
        if ($countryIso === 'nl' && $configuration[MyParcelBpost::DEFAULT_CONCEPT_INSURED]) {
            $insuranceAmount = 50000;
        } else {
            // Set the concept to 0, final export will set the amount acc. to the country
            $insuranceAmount = 0;
        }

        if (isset($deliveryOption->extraOptions->recipientOnly)) {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY] =
                !empty($deliveryOption->extraOptions->recipientOnly);
        }
        if (isset($deliveryOption->extraOptions->signed)) {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_SIGNED] = !empty($deliveryOption->extraOptions->signed);
        }

        $options = array(
            'package_type'      => 1,
            'delivery_type'     => isset($deliveryOption->data->time[0]->type) ? (int) $deliveryOption->data->time[0]->type : 4,
            'delivery_date'     => (string) date('Y-m-d 00:00:00', strtotime($deliveryOption->data->date)),
            'signature'         => (int) $configuration[MyParcelBpost::DEFAULT_CONCEPT_SIGNED],
            'insurance'         => array(
                'amount'   => $insuranceAmount,
                'currency' => 'EUR',
            ),
            'label_description' => static::getLabelConcept($order),
        );

        if (isset($deliveryOption->data->date) && $deliveryOption->data->date) {
            $options['delivery_date'] = date('Y-m-d 00:00:00', strtotime($deliveryOption->data->date));
        }
        if (isset($deliveryOption->data->type) && $deliveryOption->data->type) {
            $options['delivery_type'] = (int) $deliveryOption->data->type;
        }

        return array(
            'recipient' => array(
                'cc'                     => Tools::strtoupper(Country::getIsoById($address->id_country)),
                'street'                 => (string) $street,
                'street_additional_info' => (string) MPBpostTools::getAdditionalAddressLine($address),
                'number'                 => (string) $houseNumber,
                'postal_code'            => (string) $address->postcode,
                'city'                   => (string) $address->city,
                'region'                 => (string) $address->id_state ? State::getNameById($address->id_state) : '',
                'company'                => (string) $address->company,
                'person'                 => (string) $address->firstname.' '.$address->lastname,
                'phone'                  => (string) $configuration[MyParcelBpost::LINK_PHONE] ? ($address->phone_mobile
                    ? $address->phone_mobile : $address->phone)
                    : '',
                'email'                  => (string) ($configuration[MyParcelBpost::LINK_EMAIL]) ? $email : '',
            ),
            'options'   => $options,
            'pickup'    => array(
                'postal_code'       => (string) $deliveryOption->data->postal_code,
                'street'            => (string) $deliveryOption->data->street,
                'number'            => (string) $deliveryOption->data->number,
                'city'              => (string) $deliveryOption->data->city,
                'location_name'     => (string) $deliveryOption->data->location,
                'location_code'     => (string) $deliveryOption->data->location_code,
                'retail_network_id' => 'bpost',
            ),
            'carrier'   => 2,
        );
    }

    /**
     * Generate label text for concept
     *
     * @param Order $order
     *
     * @return bool|mixed|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getLabelConcept($order)
    {
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        try {
            $label = Configuration::get(MyParcelBpost::LABEL_DESCRIPTION);
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return '';
        }
        $label = str_replace('{order.id}', (int) $order->id, $label);
        $label = str_replace('{order.reference}', pSQL($order->reference), $label);

        return $label;
    }

    /**
     * Create concept for national shipments
     *
     * @param Address       $address
     * @param stdClass|null $deliveryOption
     * @param Order|null    $order
     *
     * @return null|array
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public static function createNationalConcept(
        $address,
        $deliveryOption = null,
        $order = null
    ) {
        $email = '';
        if ($order) {
            $customer = new Customer($order->id_customer);

            if (Validate::isLoadedObject($customer)) {
                $email = $customer->email;
            }
        }

        try {
            $configuration = Configuration::getMultiple(
                array(
                    MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE,
                    MyParcelBpost::DEFAULT_CONCEPT_LARGE_PACKAGE,
                    MyParcelBpost::DEFAULT_CONCEPT_RETURN,
                    MyParcelBpost::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY,
                    MyParcelBpost::DEFAULT_CONCEPT_SIGNED,
                    MyParcelBpost::DEFAULT_CONCEPT_INSURED,
                    MyParcelBpost::LINK_EMAIL,
                    MyParcelBpost::LINK_PHONE,
                )
            );
        } catch (PrestaShopException $e) {
            $configuration = array(
                MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE => MyParcelBpost::TYPE_PARCEL,
                MyParcelBpost::DEFAULT_CONCEPT_LARGE_PACKAGE => false,
                MyParcelBpost::DEFAULT_CONCEPT_RETURN => false,
                MyParcelBpost::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY => false,
                MyParcelBpost::DEFAULT_CONCEPT_SIGNED => false,
                MyParcelBpost::DEFAULT_CONCEPT_INSURED => false,
                MyParcelBpost::LINK_EMAIL => true,
                MyParcelBpost::LINK_PHONE => true,
            );
        }
        if (isset($deliveryOption->type) && in_array($deliveryOption->type, array('delivery', 'pickup'))) {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE] = MyParcelBpost::TYPE_PARCEL;
        }

        preg_match(MyParcelBpost::SPLIT_STREET_REGEX, MPBpostTools::getAddressLine($address), $matches);
        $street = isset($matches['street']) ? $matches['street'] : '';
        $houseNumber = isset($matches['street_suffix']) ? $matches['street_suffix'] : '';

        if ($configuration[MyParcelBpost::DEFAULT_CONCEPT_INSURED]) {
            $insuranceAmount = 50000;
        } else {
            $insuranceAmount = 0;
        }

        if (!empty($deliveryOption->extraOptions->recipientOnly)
        || (isset($deliveryOption->data->price_comment) && ($deliveryOption->data->price_comment === 'morning' || in_array($deliveryOption->data->price_comment, array('night', 'avond', 'evening'))))
        ) {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_HOME_DELIVERY_ONLY] = true;
        }
        if (isset($deliveryOption->extraOptions->signed)) {
            $configuration[MyParcelBpost::DEFAULT_CONCEPT_SIGNED] = !empty($deliveryOption->extraOptions->signed);
        }

        $options = array(
            'package_type'      => (int) $configuration[MyParcelBpost::DEFAULT_CONCEPT_PARCEL_TYPE] ?: 1,
            'signature'         => (int) $configuration[MyParcelBpost::DEFAULT_CONCEPT_SIGNED],
            'insurance'         => array(
                'amount'   => $insuranceAmount,
                'currency' => 'EUR',
            ),
            'label_description' => static::getLabelConcept($order),
        );

        if (!empty($deliveryOption->data->date)) {
            $options['delivery_date'] = date('Y-m-d 00:00:00', strtotime($deliveryOption->data->date));
        }
        if (!empty($deliveryOption->data->time[0]->type)) {
            $options['delivery_type'] = (int) $deliveryOption->data->time[0]->type;
        }
        if ($configuration[MyParcelBpost::DEFAULT_CONCEPT_RETURN]) {
            $options['return'] = 1;
        }

        return array(
            'recipient' => array(
                'cc'                     => Tools::strtoupper(Country::getIsoById($address->id_country)),
                'street'                 => (string) $street,
                'number'                 => (string) $houseNumber,
                'street_additional_info' => (string) MPBpostTools::getAdditionalAddressLine($address),
                'postal_code'            => (string) $address->postcode,
                'city'                   => (string) $address->city,
                'region'                 => (string) $address->id_state ? State::getNameById($address->id_state) : '',
                'company'                => (string) $address->company,
                'person'                 => (string) $address->firstname.' '.$address->lastname,
                'phone'                  => (string) $configuration[MyParcelBpost::LINK_PHONE]
                    ? ($address->phone_mobile ? $address->phone_mobile : $address->phone)
                    : '',
                'email'                  => (string) ($configuration[MyParcelBpost::LINK_EMAIL]) ? $email : '',
            ),
            'options'   => $options,
            'carrier'   => 2,
        );
    }

    /**
     * Create concept for international shipments
     *
     * @param Address    $address
     * @param Order|null $order
     *
     * @return array
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public static function createInternationalConcept($address, $order = null)
    {
        $email = '';
        if ($order) {
            $customer = new Customer($order->id_customer);

            if (Validate::isLoadedObject($customer)) {
                $email = $customer->email;
            }
        }

        $countryIso = Tools::strtoupper(Country::getIsoById($address->id_country));
        if ($countryIso === 'NL' && Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_INSURED)) {
            $insuranceAmount = 50000;
        } else {
            // Set the concept to 0, final export will set the amount acc. to the country
            $insuranceAmount = 0;
        }

        if (in_array($countryIso, array('NL', 'BE'))) {
            preg_match(MyParcelBpost::SPLIT_STREET_REGEX, MPBpostTools::getAddressLine($address), $matches);
            $street = isset($matches['street']) ? $matches['street'] : '';
            $houseNumber = isset($matches['street_suffix']) ? $matches['street_suffix'] : '';
            $additional = MPBpostTools::getAdditionalAddressLine($address);
        } else {
            $street = MPBpostTools::getAddressLine($address);
            $houseNumber = '';
            $additional = MPBpostTools::getAdditionalAddressLine($address);
        }

        return array(
            'recipient'           => array(
                'cc'                     => (string) Tools::strtoupper(Country::getIsoById($address->id_country)),
                'street'                 => (string) $street,
                'number'                 => (string) $houseNumber,
                'street_additional_info' => (string) $additional,
                'postal_code'            => (string) $address->postcode,
                'city'                   => (string) $address->city,
                'region'                 => (string) $address->id_state ? State::getNameById($address->id_state) : '',
                'company'                => (string) $address->company,
                'person'                 => (string) $address->firstname.' '.$address->lastname,
                'phone'                  => (string) Configuration::get(MyParcelBpost::LINK_PHONE)
                    ? ($address->phone ? $address->phone : $address->phone_mobile)
                    : '',
                'email'                  => (string) (Configuration::get(MyParcelBpost::LINK_EMAIL)) ? $email : '',
            ),
            'options'             => array(
                'package_type'      => 1,
                'label_description' => static::getLabelConcept($order),
                'large_format'      => (int) Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_LARGE_PACKAGE),
                'insurance'         => array(
                    'amount'   => $insuranceAmount,
                    'currency' => 'EUR',
                ),
            ),
            'customs_declaration' => array(
                'contents' => 1,
                'invoice'  => MPBpostTools::getInvoiceSuggestion($order),
                'weight'   => (int) MPBpostTools::getWeightSuggestion($order),
                'items'    => array(),
            ),
            'physical_properties' => array(
                'weight' => (int) MPBpostTools::getWeightSuggestion($order),
            ),
            'carrier'             => 2,
        );
    }

    /**
     * Get Delivery Option info by Order
     *
     * @param int|Order $order Order ID or object
     *
     * @return string Delivery from DB
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     * @since 2.0.0
     */
    public static function getByOrder($order)
    {
        if ($order instanceof Order) {
            $idOrder = $order->id;
        } else {
            $idOrder = $order;
        }

        $sql = new DbQuery();
        $sql->select('`mpbpost_delivery_option`');
        $sql->from(bqSQL(static::$definition['table']), 'mdo');
        $sql->innerJoin('orders', 'o', 'o.`id_cart` = mdo.`id_cart`');
        $sql->where('o.`id_order` = '.(int) $idOrder);

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);

        if ($result) {
            $concept = @json_decode($result, true);
            static::validateDeliveryOption($concept, true);

            return mypa_json_encode($concept);
        }

        $concepts = static::getConceptsByOrderIds(array($idOrder));
        if (is_array($concepts)) {
            $concept = reset($concepts);
            static::validateDeliveryOption($concept, true);

            return mypa_json_encode($concept);
        }

        return new stdClass();
    }

    /**
     * Get concepts by Order IDs
     *
     * @param array $range Range of Order IDs
     *
     * @return array Concepts
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getConceptsByOrderIds($range)
    {
        $sql = new DbQuery();
        $sql->select('o.`id_order`');
        $sql->from('orders', 'o');
        $sql->where('o.`id_order` IN ('.implode(',', $range).')');

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        $concepts = array();

        foreach ($results as $result) {
            $concept = array();
            $order = new Order($result['id_order']);
            $concept['concept'] = static::createConcept($order);
            $concept['idOrder'] = (int) $order->id;

            $concepts[$concept['idOrder']] = $concept;
        }

        return $concepts;
    }

    /**
     * Save concept
     *
     * @param Order|int $order
     * @param string    $concept
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     * @since 2.0.0
     */
    public static function saveConcept($order, $concept)
    {
        if ($order instanceof Order) {
            $idOrder = $order->id;
        } else {
            $idOrder = $order;
        }

        if (!$idOrder) {
            return false;
        }

        $concept = @json_decode($concept, true);

        $idCart = Cart::getCartIdByOrderId($idOrder);

        $sql = new DbQuery();
        $sql->select('`id_cart`');
        $sql->select(bqSQL(static::$definition['table']));
        $sql->from(bqSQL(static::$definition['table']), 'mdo');
        $sql->where('mdo.`id_cart` = '.(int) $idCart);

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
            $deliveryOption = @json_decode($result[static::$definition['table']], true);
            if (!empty($deliveryOption['concept']) && static::validateDeliveryOption($deliveryOption)) {
                $deliveryOption['concept'] = $concept;
                return Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    array(
                        bqSQL(static::$definition['table']) => array('type' => 'sql', 'value' => "'".pSQL(mypa_json_encode($deliveryOption), true)."'"),
                    ),
                    '`id_cart` = '.(int) $idCart
                );
            } else {
                $deliveryOption = array('concept' => $concept);
                return Db::getInstance()->update(
                    bqSQL(static::$definition['table']),
                    array(
                        bqSQL(static::$definition['table']) => array('type' => 'sql', 'value' => "'".pSQL(mypa_json_encode($deliveryOption), true)."'"),
                    ),
                    '`id_cart` = '.(int) $idCart
                );
            }
        }

        $deliveryOption = mypa_json_encode(array('concept' => $concept));

        return Db::getInstance()->insert(
            bqSQL(static::$definition['table']),
            array(
                bqSQL(static::$definition['table']) => array('type' => 'sql', 'value' => "'".pSQL($deliveryOption, true)."'"),
                'id_cart'                           => (int) $idCart,
            )
        );
    }

    /**
     * Save concept data
     *
     * @param Order|int $order
     * @param string    $conceptData
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     *
     * @since 2.2.0
     */
    public static function saveConceptData($order, $conceptData)
    {
        if ($order instanceof Order) {
            $idOrder = $order->id;
        } else {
            $idOrder = $order;
        }

        if (!$idOrder) {
            return false;
        }

        $conceptData = @json_decode($conceptData, true);

        $idCart = Cart::getCartIdByOrderId($idOrder);

        $sql = new DbQuery();
        $sql->select('`id_cart`');
        $sql->select(bqSQL(static::$definition['table']));
        $sql->from(bqSQL(static::$definition['table']), 'mdo');
        $sql->where('mdo.`id_cart` = '.(int) $idCart);

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {
            $deliveryOption = @json_decode($result[static::$definition['table']], true);
            if ($conceptData) {
                $deliveryOption['data'] = $conceptData;
            } else {
                unset($deliveryOption['data']);
            }
            $deliveryOption['idOrder'] = (int) $idOrder;
            return Db::getInstance()->update(
                bqSQL(static::$definition['table']),
                array(
                    bqSQL(static::$definition['table']) => array('type' => 'sql', 'value' => "'".pSQL(mypa_json_encode($deliveryOption), true)."'"),
                ),
                '`id_cart` = '.(int) $idCart
            );
        }

        return false;
    }

    /**
     * Calculates the next delivery date
     *
     * If a time is passed, this function will only add up the days, keeping the exact time intact
     *
     * @param string $date Shipping date (format: `Y-m-d H:i:s`)
     *
     * @return string (format: `Y-m-d H:i:s`)
     * @throws Exception
     */
    public static function getDeliveryDay($date)
    {
        $deliveryDate = new DateTime($date);

        $holidays = static::getHolidaysForYear(date('Y', strtotime($date)));

        do {
            $deliveryDate->add(new DateInterval('P1D'));
        } while (in_array($deliveryDate->format('Y-m-d'), $holidays) || $deliveryDate->format('w') == 0);

        return $deliveryDate->format('Y-m-d H:i:s');
    }

    /**
     * Get preferred delivery day from delivery option
     *
     * @param array $option
     *
     * @return string
     */
    public static function getPreferredDeliveryDay($option)
    {
        if (isset($option['data']['date'])) {
            $deliveryDate = $option['data']['date'];

            if (isset($option['data']['time'][0]['start'])) {
                $deliveryDate .= " {$option['data']['time'][0]['start']}";
            } else {
                $deliveryDate .= ' 15:00:00';
            }
        } else {
            $deliveryDate = '1970-01-01 00:00:00';
        }

        return $deliveryDate;
    }

    /**
     * Get preferred delivery day from delivery option
     *
     * @param array $option
     *
     * @return string|null
     */
    public static function getPreferredPickup($option)
    {
        if (isset($option['data']['location_code'])) {
            return "{$option['data']['location']}, {$option['data']['street']} {$option['data']['number']}".
            ", {$option['data']['city']}";
        }

        return null;
    }

    /**
     * Calculates amount of days remaining
     * i.e. preferred delivery date the day tomorrow => today = 0
     * i.e. preferred delivery date the day after tomorrow => today + tomorrow = 1
     * i.e. preferred delivery date the day after tomorrow, but one holiday => today + holiday = 0
     *
     * 0 means: should ship today
     * < 0 means: should've shipped in the past
     * anything higher means: you've got some more time
     *
     * @param string $shippingDate          Shipping date (format: `Y-m-d H:i:s`)
     * @param string $preferredDeliveryDate Customer preference
     *
     * @return int
     * @throws Exception
     */
    public static function getShippingDaysRemaining($shippingDate, $preferredDeliveryDate)
    {
        // Remove the hours/minutes/seconds
        $shippingDate = date('Y-m-d 00:00:00', strtotime($shippingDate));

        // Find the nearest delivery date
        $nearestDeliveryDate = static::getDeliveryDay($shippingDate);

        // Calculate the interval
        $nearestDeliveryDate = new DateTime($nearestDeliveryDate);
        $preferredDeliveryDate = new DateTime(date('Y-m-d 00:00:00', strtotime($preferredDeliveryDate)));

        $daysRemaining = (int) $nearestDeliveryDate->diff($preferredDeliveryDate)->format('%R%a');

        // Subtract an additional day if we cannot ship today (Sunday or holiday)
        if (date('w', strtotime($shippingDate)) == 0 ||
            in_array(
                date('Y-m-d', strtotime($shippingDate)),
                static::getHolidaysForYear(date('Y', strtotime($shippingDate)))
            )
        ) {
            $daysRemaining--;
        }

        return $daysRemaining;
    }

    /**
     * Raw `mpbpost_delivery_option`
     * This function checks if pickup has been chosen
     *
     * @param array $option
     *
     * @return bool
     */
    protected static function isPickup($option)
    {
        return isset($option['type']) && $option['type'] === 'pickup';
    }

    /**
     * Get an array with all Belgian holidays for the given year
     *
     * @param string $year
     *
     * @return array
     *
     * Credits to @tvlooy (https://gist.github.com/tvlooy/1894247)
     *
     * @throws Exception
     */
    protected static function getHolidaysForYear($year)
    {
        if (!extension_loaded('calendar')) {
            return array();
        }

        // Avoid holidays
        // Fixed
        $nieuwjaar = new DateTime($year.'-01-01');
        $eersteKerstDag = new DateTime($year.'-12-25');
        $tweedeKerstDag = new DateTime($year.'-12-25');
        $koningsdag = new DateTime($year.'-04-27');
        // Dynamic
        $pasen = new DateTime();
        $pasen->setTimestamp(easter_date($year)); // thanks PHP!
        $paasMaandag = clone $pasen;
        $paasMaandag->add(new DateInterVal('P1D'));
        $hemelvaart = clone $pasen;
        $hemelvaart->add(new DateInterVal('P39D'));
        $pinksteren = clone $hemelvaart;
        $pinksteren->add(new DateInterVal('P10D'));
        $pinksterMaandag = clone $pinksteren;
        $pinksterMaandag->add(new DateInterVal('P1D'));

        $holidays = array(
            $nieuwjaar->format('Y-m-d'),
            $pasen->format('Y-m-d'),
            $koningsdag->format('Y-m-d'),
            $paasMaandag->format('Y-m-d'),
            $hemelvaart->format('Y-m-d'),
            $pinksteren->format('Y-m-d'),
            $pinksterMaandag->format('Y-m-d'),
            $eersteKerstDag->format('Y-m-d'),
            $tweedeKerstDag->format('Y-m-d'),
        );

        return $holidays;
    }

    /**
     * Delivery option validator
     *
     * @param array $deliveryOption
     * @param bool  $autofix Try to restore the delivery option if possible
     *
     * @return bool
     *
     * @throws Exception
     */
    protected static function validateDeliveryOption(&$deliveryOption, $autofix = false)
    {
        $dot = mypa_dot($deliveryOption);
        // Skip concepts without basic info
        if (!$dot->has('concept.recipient')
            || !$dot->has('concept.recipient.cc')
            || !$dot->has('concept.recipient.city')
            || !$dot->has('concept.recipient.street')
            || !$dot->has('concept.recipient.person')
        ) {
            return false;
        }

        // Skip concepts in NL,BE,DE w/o postcode info
        if (in_array(strtolower($dot->get('concept.recipient.cc')), array('nl', 'be', 'de'))) {
            if (!$dot->has('concept.recipient.number')
                || !$dot->has('concept.recipient.postal_code')
            ) {
                return false;
            }
        }

        // Fix `delivery_date`s in the past
        $checkDate = null;
        if (!$dot->isEmpty('concept.options.delivery_date')) {
            $checkDate = date('Y-m-d', strtotime($dot->get('concept.options.delivery_date')));
        } elseif (!$dot->isEmpty('data.date')) {
            $checkDate = date('Y-m-d', strtotime($dot->get('data.date')));
        }

        if ($checkDate && $checkDate <= date('Y-m-d')) {
            // Restore the original delivery type when available
            if ($dot->has('old_data.time.0.type')) {
                $dot->set('concept.options.delivery_type', $dot->get('old_data.time.0.type'));
            }

            // Copy a deep clone of data to old_data
            $dot->set('old_data', $dot->get('data'));

            $newDeliveryDate = date('Y-m-d', strtotime(static::getDeliveryDay(date('Y-m-d H:i:s'))));
            $dot->set('concept.options.delivery_date', $newDeliveryDate);

            // Reset date in data if set
            if ($dot->has('data.date')) {
                $dot->set('data.date', $newDeliveryDate);
            }

            // Correct delivery type if necessary
            if (in_array(date('D', strtotime($newDeliveryDate)), array('Mon', 'Sat'))) {
                if (in_array($dot->get('concept.options.delivery_type'), array(1, 3))) {
                    $dot->set('concept.options.delivery_type', 2);
                    if ($dot->has('data.time.0')) {
                        $dot->set('data.time.0.start', '08:00:00');
                        $dot->set('data.time.0.end', '21:00:00');
                        $dot->set('data.time.0.price_comment', 'standard');
                    }
                    $dot->set('extraOptions.removedSpecialOption', true);
                } elseif (in_array($dot->get('concept.options.delivery_type'), array(5))) {
                    $dot->set('concept.options.delivery_type', 4);
                    if ($dot->has('data.time.0')) {
                        $dot->set('data.time.0.start', '08:00:00');
                        $dot->set('data.time.0.end', '21:00:00');
                        $dot->set('data.time.0.price_comment', 'retail');
                    }
                    $dot->set('extraOptions.removedSpecialOption', true);
                }
                // Set the new type in data
                if ($dot->has('data.time.0.type')) {
                    $dot->set('data.time.0.type', $dot->get('concept.options.delivery_type'));
                }
            }

            $dot->set('extraOptions.moved', true);
        }
        if ($dot->has('concept.options.delivery_type')
            && in_array($dot->get('concept.options.delivery_type'), array(4, 5))
            && $dot->isEmpty('concept.options.delivery_date')
            && strtolower($dot->get('concept.recipient.cc')) === 'nl'
        ) {
            if ($dot->get('data.date')
                && date('Y-m-d', strtotime($dot->get('data.date'))) > date('Y-m-d')
            ) {
                $dot->set('concept.options.delivery_date', $dot->get('data.date'));
            }
        }

        $deliveryOption = $dot->jsonSerialize();

        return true;
    }

    /**
     * Filter concept (ported from JavaScript)
     *
     * @param array $concept
     *
     * @return array
     */
    public static function filterConcept($concept)
    {
        $euCountries = array_column(MPBpostTools::getEUCountries(), 'alpha2Code');
        $concept = mypa_dot($concept);

        $concept->delete('recipient.label');
        if ($concept->has('options.delivery_date')) {
            if (date('Y-m-d', strtotime($concept->get('options.delivery_date'))) <= date('Y-m-d')) {
                $concept->delete('option.delivery_date');
            } else {
                $concept->set('options.delivery_date', date('Y-m-d 00:00:00', strtotime($concept->get('options.delivery_date'))));
            }
        }

        if ($concept->get('options.package_type') == MyParcelBpost::TYPE_PARCEL
            && $concept->get('recipient.cc')
            && in_array(strtolower($concept->get('recipient.cc')), array('nl', 'be'))
        ) {
            // Only keep the location code and retail network ID for shipments to Belgium
            if (strtolower($concept->get('recipient.cc')) === 'nl' && !$concept->isEmpty('pickup')) {
                $concept->delete('pickup.location_code');
                $concept->delete('pickup.retail_network_id');
            }
        } elseif ($concept->get('options.package_type') != MyParcelBpost::TYPE_PARCEL) {
            $concept->delete('options.delivery_type');
            $concept->delete('pickup');
            $concept->delete('options.delivery_date');
        }

        if (!$concept->isEmpty('recipient.cc')
            && in_array(strtolower($concept->get('recipient.cc')), array('nl', 'be', 'de'))
        ) {
            if (preg_match('/^(\d+)(.*?$)/', $concept->get('recipient.number'), $m)) {
                if (count($m) > 1) {
                    $concept->set('recipient.number', $m[1]);
                }
            }
            if (strtolower($concept->get('recipient.cc')) !== 'be') {
                $concept->delete('recipient.box_number');
            }
        } else {
            $concept->delete('recipient.number');
        }

        if (!$concept->isEmpty('recipient.cc') && in_array(strtoupper($concept->get('recipient.cc')), array('NL', 'BE'))
            || $concept->get('options.package_type') > 1
        ) {
            $concept->delete('options.signature');
        }

        if (in_array(strtoupper($concept->get('recipient.cc')), $euCountries)
            || strtoupper($concept->get('recipient.cc')) === 'NL'
        ) {
            $concept->delete('customs_declaration');
            $concept->delete('physical_properties');
        } elseif ($concept->isEmpty('physical_properties') || $concept->isEmpty('physical_properties.weight')) {
            $concept->set('physical_properties', array(
                'weight' => 1000,
            ));
        } else {
            $concept->set('physical_properties.weight', (int) $concept->get('physical_properties.weight'));
        }

        if (!$concept->has('physical_properties.weight')) {
            $concept->set('physical_properties.weight', 1000);
        }

        return $concept->jsonSerialize();
    }
}

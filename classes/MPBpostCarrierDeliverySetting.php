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
 * Class MPBpostCarrierDeliverySetting
 */
class MPBpostCarrierDeliverySetting extends MPBpostObjectModel
{
    const ENUM_NONE = 0;
    const ENUM_DELIVERY = 2;
    const ENUM_DELIVERY_SELF_DELAY = 3;

    const MONDAY_ENABLED = 'monday_enabled';
    const MONDAY_CUTOFF = 'monday_cutoff';
    const TUESDAY_ENABLED = 'tuesday_enabled';
    const TUESDAY_CUTOFF = 'tuesday_cutoff';
    const WEDNESDAY_ENABLED = 'wednesday_enabled';
    const WEDNESDAY_CUTOFF = 'wednesday_cutoff';
    const THURSDAY_ENABLED = 'thursday_enabled';
    const THURSDAY_CUTOFF = 'thursday_cutoff';
    const FRIDAY_ENABLED = 'friday_enabled';
    const FRIDAY_CUTOFF = 'friday_cutoff';
    const SATURDAY_ENABLED = 'saturday_enabled';
    const SATURDAY_CUTOFF = 'saturday_cutoff';
    const SATURDAY_DELIVERY = 'saturday_delivery';
    const SATURDAY_DELIVERY_FEE = 'saturday_delivery_fee_tax_incl';
    const SIGNED = 'signed';
    const SIGNED_FEE = 'signed_fee_tax_incl';
    const CUTOFF_EXCEPTIONS = 'cutoff_exceptions';
    const CUTOFF_EXCEPTIONS_SAMEDAY = 'cutoff_sameday_exceptions';
    const DROPOFF_DELAY = 'dropoff_delay';

    const DELIVERY = 'delivery';
    const PICKUP = 'pickup';
    const PICKUP_FEE = 'pickup_fee_tax_incl';

    const DEFAULT_CUTOFF = '15:30';

    // @codingStandardsIgnoreStart
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table'   => 'mpbpost_carrier_delivery_setting',
        'primary' => 'id_mpbpost_carrier_delivery_setting',
        'fields' => array(
            'id_reference'                       => array(
                'type'     => self::TYPE_INT,
                'validate' => 'isInt',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ),
            'delivery'                           => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'pickup'                             => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'pickup_fee_tax_incl'                => array(
                'type'     => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'DECIMAL(15, 5)',
            ),
            'monday_enabled'                     => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'tuesday_enabled'                    => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'wednesday_enabled'                  => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'thursday_enabled'                   => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'friday_enabled'                     => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'saturday_enabled'                   => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'cutoff_exceptions'                  => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'TEXT',
            ),
            'monday_cutoff'                      => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'tuesday_cutoff'                     => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'wednesday_cutoff'                   => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'thursday_cutoff'                    => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'friday_cutoff'                      => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'saturday_cutoff'                    => array(
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => false,
                'db_type'  => 'VARCHAR(5)',
            ),
            'saturday_delivery'                  => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'signed'                             => array(
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'TINYINT(1) UNSIGNED',
            ),
            'dropoff_delay'                      => array(
                'type'     => self::TYPE_INT,
                'validate' => 'isInt',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(2) UNSIGNED',
            ),
            'id_shop'                            => array(
                'type'     => self::TYPE_INT,
                'validate' => 'isInt',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ),
            'saturday_delivery_fee_tax_incl'        => array(
                'type'     => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'DECIMAL(15, 5)',
            ),
            'default_fee_tax_incl'               => array(
                'type'     => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'DECIMAL(15, 5)',
            ),
            'signed_fee_tax_incl'                => array(
                'type'     => self::TYPE_FLOAT,
                'validate' => 'isFloat',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'DECIMAL(15, 5)',
            ),
        ),
    );
    /** @var int $id_reference */
    public $id_reference;
    /** @var bool $delivery */
    public $delivery;
    /** @var bool $pickup */
    public $pickup;
    /** @var float $pickup_fee_tax_incl */
    public $pickup_fee_tax_incl;
    /** @var bool $monday_enabled */
    public $monday_enabled;
    /** @var string $monday_cutoff */
    public $monday_cutoff;
    /** @var bool $tuesday_enabled */
    public $tuesday_enabled;
    /** @var string $tuesday_cutoff */
    public $tuesday_cutoff;
    /** @var bool $wednesday_enabled */
    public $wednesday_enabled;
    /** @var string $wednesday_cutoff */
    public $wednesday_cutoff;
    /** @var bool $thursday_enabled */
    public $thursday_enabled;
    /** @var string $thursday_cutoff */
    public $thursday_cutoff;
    /** @var bool $friday_enabled */
    public $friday_enabled;
    /** @var string $friday_cutoff */
    public $friday_cutoff;
    /** @var bool $saturday_enabled */
    public $saturday_enabled;
    /** @var string $saturday_cutoff */
    public $saturday_cutoff;
    /** @var string $cutoff_exceptions */
    public $cutoff_exceptions;
    /** @var bool $saturday_delivery */
    public $saturday_delivery;
    /** @var bool $signed */
    public $signed;
    /** @var int $dropoff_delay */
    public $dropoff_delay;
    /** @var float $default_fee_tax_incl */
    public $default_fee_tax_incl;
    /** @var float $saturday_delivery_fee_tax_incl */
    public $saturday_delivery_fee_tax_incl;
    /** @var float $signed_fee_tax_incl */
    public $signed_fee_tax_incl;
    /** @var int $id_shop Shop ID */
    public $id_shop;
    // @codingStandardsIgnoreEnd

    /**
     * MPBpostDeliveryOption constructor.
     *
     * @param int $id     MyParcel Delivery Option ID
     * @param int $idLang Language ID
     * @param int $idShop Shop ID
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     *
     * @since 2.0.0
     */
    public function __construct($id = null, $idLang = null, $idShop = null)
    {
        parent::__construct($id, $idLang, $idShop);

        // Check if cutoff exceptions at least have their defaults
        if (empty($this->cutoff_exceptions)) {
            $this->cutoff_exceptions = '{}';
        }

        $cutoffs = array(
            'monday_cutoff',
            'tuesday_cutoff',
            'wednesday_cutoff',
            'thursday_cutoff',
            'friday_cutoff',
            'saturday_cutoff',
        );

        foreach ($cutoffs as $cutoff) {
            if (empty($this->{$cutoff})) {
                $this->{$cutoff} = '15:30';
            }
        }
    }

    /**
     * @param int      $idReference
     * @param int|null $idShop
     *
     * @return MPBpostCarrierDeliverySetting
     *
     * @throws Adapter_Exception
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function createDefault($idReference, $idShop = null)
    {
        if (!$idShop) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        $mcds = new self();
        foreach (static::$definition['fields'] as $key => $field) {
            if ($field['required'] && $field['default']) {
                $mcds->{$key} = $field['default'];
            }
        }
        $mcds->id_reference = $idReference;
        $mcds->id_shop = $idShop;

        return $mcds;
    }

    /**
     * Get delivery option by Carrier Reference
     *
     * @param int $idReference Carrier reference
     *
     * @return bool|MPBpostCarrierDeliverySetting
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getByCarrierReference($idReference)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(bqSQL(MPBpostCarrierDeliverySetting::$definition['table']), 'pcds');
        $sql->where('pcds.`id_reference` = '.(int) $idReference);

        try {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return false;
        }
        if (empty($result)) {
            return false;
        }

        $deliverySetting = new MPBpostCarrierDeliverySetting();
        $deliverySetting->hydrate($result);
        if (!Validate::isLoadedObject($deliverySetting)) {
            return false;
        }

        return $deliverySetting;
    }

    /**
     * Get Carrier reference by MPBpostCarrierDeliverySetting ID
     *
     * @param int $idMPBpostCarrierDeliverySetting MPBpostCarrierDeliverySetting ID
     *
     * @return bool|MPBpostCarrierDeliverySetting
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function getCarrierReferenceById($idMPBpostCarrierDeliverySetting)
    {
        $sql = new DbQuery();
        $sql->select('pcds.`id_reference`');
        $sql->from(bqSQL(static::$definition['table']), 'pcds');
        $sql->where('pcds.`'.bqSQL(static::$definition['primary']).'` = '.(int) $idMPBpostCarrierDeliverySetting);

        try {
            return (int) Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Toggle delivery status
     *
     * @param int $idMPBpostCarrierDeliverySetting
     *
     * @return bool Indicates whether the delivery status has been successfully toggled
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function toggleDelivery($idMPBpostCarrierDeliverySetting)
    {
        $mpcds = new static($idMPBpostCarrierDeliverySetting);
        $mpcds->delivery = !$mpcds->delivery;

        return $mpcds->save();
    }

    /**
     * Save
     *
     * @param bool $nullValues
     * @param bool $autoDate
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public function save($nullValues = false, $autoDate = true)
    {
        try {
            // Need to be associated with an active carrier
            $carrier = Carrier::getCarrierByReference($this->id_reference);
            if (!Validate::isLoadedObject($carrier)) {
                return false;
            }
            // Cannot associate with this carrier if it is already managage by another module
            if ($carrier->external_module_name && $carrier->external_module_name !== 'myparcelbpost') {
                return false;
            }
            // No delivery options for this carrier => release carrier
            if (!$this->pickup && !$this->delivery) {
                static::associateCarrierToModule($this->id_reference, false);
            } else {
                static::associateCarrierToModule($this->id_reference);
            }

            return parent::save($nullValues, $autoDate);
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Associate a carrier with a module
     *
     * @param int  $idReference Carrier reference ID
     * @param bool $associate   Associate/disassociate the module
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function associateCarrierToModule($idReference, $associate = true)
    {
        try {
            if ($associate) {
                return Db::getInstance()->update(
                    'carrier',
                    array(
                        'external_module_name' => 'myparcelbpost',
                        'shipping_external'    => 1,
                        'is_module'            => 1,
                        'need_range'           => 1,
                    ),
                    '`id_reference` = '.(int) $idReference.' AND `deleted` = 0'
                );
            } else {
                return Db::getInstance()->update(
                    'carrier',
                    array(
                        'external_module_name' => '',
                        'shipping_external'    => 0,
                        'is_module'            => 0,
                    ),
                    '`id_reference` = '.(int) $idReference.' AND `deleted` = 0'
                );
            }
        } catch (PrestaShopException $e) {
            Logger::addLog("MyParcel BE module error: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Toggle pickup status
     *
     * @param int $idMPBpostCarrierDeliverySetting
     *
     * @return bool Indicates whether the pickup status has been successfully toggled
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 2.0.0
     */
    public static function togglePickup($idMPBpostCarrierDeliverySetting)
    {
        $mpcds = new self($idMPBpostCarrierDeliverySetting);
        $mpcds->pickup = !$mpcds->pickup;

        return $mpcds->save();
    }

    /**
     * Get cut off times
     *
     * @param string $dateFrom From date
     * @param int    $method   Method
     *
     * @return array Array with cut off times
     *
     * @since 2.0.0
     */
    public function getCutOffTimes($dateFrom, $method)
    {
        $cutoffTimes = array();
        if ($method === static::ENUM_DELIVERY) {
            $date = new DateTime($dateFrom);
            $cutoffExceptions = json_decode($this->cutoff_exceptions, true);
            if (!is_array($cutoffExceptions)) {
                $cutoffExceptions = array();
            }
            for ($i = 0; $i < 7; $i++) {
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
                        'name'       => Translate::getModuleTranslation(
                            'myparcelbpost',
                            $date->format('D'),
                            'dates'
                        ),
                        'time'       => (array_key_exists('cutoff', $exceptionInfo)
                            ? $exceptionInfo['cutoff']
                            : ''),
                        'exception'  => true,
                        'nodispatch' => $nodispatch,
                    );
                } elseif (!empty($this->{Tools::strtolower($date->format('l')).'_enabled'})) {
                    $cutoffTimes[$i] = array(
                        'name'       => Translate::getModuleTranslation(
                            'myparcelbpost',
                            $date->format('D'),
                            'dates'
                        ),
                        'time'       => $this->{Tools::strtolower($date->format('l')).'_cutoff'},
                        'exception'  => false,
                        'nodispatch' => false,
                    );
                } else {
                    $cutoffTimes[$i] = array(
                        'name'       => Translate::getModuleTranslation(
                            'myparcelbpost',
                            $date->format('D'),
                            'dates'
                        ),
                        'time'       => '',
                        'exception'  => false,
                        'nodispatch' => true,
                    );
                }
                $date->modify('+1 day');
            }
        }

        return $cutoffTimes;
    }

    /**
     * Get cut off times
     *
     * @param string $dateFrom From date
     *
     * @return array Array with cut off times
     *
     * @since 2.0.0
     */
    public function getDropoffDays($dateFrom)
    {
        $cutoffTimes = array();

        $date = new DateTime($dateFrom);
        $cutoffExceptions = json_decode($this->cutoff_exceptions, true);
        if (!is_array($cutoffExceptions)) {
            $cutoffExceptions = array();
        }
        for ($i = 1; $i < 7; $i++) {
            if (array_key_exists($date->format('d-m-Y'), $cutoffExceptions)) {
                $exceptionInfo = $cutoffExceptions[$date->format('d-m-Y')];
                if ((array_key_exists('nodispatch', $exceptionInfo) && $exceptionInfo['nodispatch'])
                    && (array_key_exists('cutoff', $exceptionInfo))
                ) {
                    $nodispatch = false;
                } else {
                    $nodispatch = true;
                }

                if (!$nodispatch) {
                    $cutoffTimes[] = $date->format('N');
                }
            } elseif (!empty($this->{Tools::strtolower($date->format('l')).'_enabled'})) {
                $cutoffTimes[] = $date->format('N');
            }

            $date->modify('+1 day');
        }

        return $cutoffTimes;
    }

    /**
     * Get delivery options array
     *
     * @return array Array
     *
     * @since 2.0.0
     */
    public function getOptions()
    {
        return array(
            'daytime' => 'daytime',
        );
    }

    /**
     * Get the cutoff exceptions hash
     * Useful to invalidate caching
     *
     * @return string
     *
     * @since 2.1.0
     */
    public function getCutoffExceptionsHash()
    {
        return md5($this->cutoff_exceptions);
    }
}

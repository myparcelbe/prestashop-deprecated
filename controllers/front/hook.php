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
 * Class MyParcelBpostHookModuleFrontController
 */
class MyParcelBpostHookModuleFrontController extends ModuleFrontController
{
    /** @var MyParcel $module */
    public $module;

    /**
     * Initialize content and block unauthorized calls
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    public function initContent()
    {
        if (!Module::isEnabled('myparcelbpost')) {
            header('Content-Type: application/json; charset=utf8');
            die(mypa_json_encode(array('data' => array('message' => 'Module is not enabled'))));
        }
        if (!Configuration::get(MyParcelBpost::UPDATE_ORDER_STATUSES)) {
            header('Content-Type: application/json; charset=utf8');
            die(mypa_json_encode(array('data' => array('message' => 'Webhooks are not enabled'))));
        }

        $this->processWebhook();

        die('1');
    }

    /**
     * Process webhook
     *
     * @since 2.0.0
     * @throws PrestaShopException
     * @throws Adapter_Exception
     */
    protected function processWebhook()
    {
        // @codingStandardsIgnoreStart
        $content = file_get_contents('php://input');
        // @codingStandardsIgnoreEnd
        if (Configuration::get(MyParcelBpost::LOG_API)) {
            $logContent = pSQL($content);
            Logger::addLog("MyParcel webhook: $logContent");
        }

        $data = json_decode($content, true);
        if (isset($data['data']['hooks']) && is_array($data['data']['hooks'])) {
            foreach ($data['data']['hooks'] as &$item) {
                if (isset($item['shipment_id'])
                    && isset($item['status'])
                    && isset($item['barcode'])
                ) {
                    MPBpostOrder::updateStatus($item['shipment_id'], $item['barcode'], $item['status']);
                }
            }

            die('0');
        }

        die('1');
    }
}

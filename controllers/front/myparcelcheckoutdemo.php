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
 * Class MyParcelBpostmyparcelcheckoutdemoModuleFrontController
 *
 * @since 2.0.0
 */
class MyParcelBpostmyparcelcheckoutdemoModuleFrontController extends ModuleFrontController
{
    /**
     * MyParcelmyparcelcheckoutdemoModuleFrontController constructor.
     *
     * @since 2.0.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();

        // Check if employee is logged in
        $cookie = new Cookie('psAdmin');
        if (!$cookie->id_employee) {
            Tools::redirectLink($this->context->link->getPageLink('index'));
        }
    }

    /**
     * Prevent displaying the maintenance page
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * Initialize content
     *
     * @return string
     *
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     * @since 2.0.0
     */
    public function initContent()
    {
        $smarty = $this->context->smarty;

        $smarty->assign(array(
            'language_code'          => Tools::strtolower(Context::getContext()->language->language_code),
            'mypaBpostCheckoutJs'    => Media::getJSPath(_PS_MODULE_DIR_.'myparcelbpost/views/js/dist/front-cbfee8e3cbc20b2b.bundle.min.js'),
            'base_dir_ssl'           => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').Tools::getShopDomainSsl().__PS_BASE_URI__,
            'signedPreferred'        => (bool) Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_SIGNED),
        ));

        echo $smarty->fetch(_PS_MODULE_DIR_.'myparcelbpost/views/templates/admin/examplecheckout/checkout.tpl');
        exit;
    }
}

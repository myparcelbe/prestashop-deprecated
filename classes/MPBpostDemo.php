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

/**
 * Class MPBpostDemo
 */
class MPBpostDemo
{
    /**
     * Initialize content
     *
     * @return string
     *
     * @since 2.0.0
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws ErrorException
     */
    public static function renderDemo()
    {
        header('Content-Type: text/html');

        $smarty = Context::getContext()->smarty;
        $smarty->assign(array(
            'language_code'          => Tools::strtolower(Context::getContext()->language->language_code),
            'mypaBpostCheckoutJs'    => Media::getJSPath(_PS_MODULE_DIR_.'myparcelbpost/views/js/dist/checkout-853f0c02eaf3aba7.bundle.min.js'),
            'base_dir_ssl'           => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
                .Tools::getShopDomainSsl().__PS_BASE_URI__,
            'signedPreferred'        => (bool) Configuration::get(MyParcelBpost::DEFAULT_CONCEPT_SIGNED),
            'mpbCheckoutFont'        => Configuration::get(MyParcelBpost::CHECKOUT_FONT),
            'mpbAsync'               => (bool) Configuration::get(MyParcelBpost::DEV_MODE_ASYNC),
        ));
        @ob_clean();
        echo $smarty->fetch(_PS_MODULE_DIR_.'myparcelbpost/views/templates/admin/examplecheckout/checkout.tpl');
        exit;
    }
}

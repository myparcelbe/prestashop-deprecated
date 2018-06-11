{*
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
*}
<div id="mpbpost-export-panel"></div>
<script type="text/javascript" src="{$module_dir|escape:'htmlall':'UTF-8' nofilter}views/js/app/dist/orderpage-07481c8ea100e30c.bundle.min.js"></script>
<script type="text/javascript">
  (function () {
    function initAdminOrderDetail() {
      if (typeof $ === 'undefined' || typeof MyParcelBpostModule === 'undefined') {
        setTimeout(initAdminOrderDetail, 10);

        return;
      }

      window.MyParcelBpostModule.misc = window.MyParcelBpostModule.misc || {ldelim}{rdelim};
      window.MyParcelBpostModule.misc.process_url = '{$mpbpost_process_url|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.misc.module_url = '{$mpbpost_module_url|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.misc.countries = {mypa_json_encode($mpbJsCountries)};
      window.MyParcelBpostModule.invoiceSuggestion = '{$invoiceSuggestion|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.weightSuggestion = '{$weightSuggestion|escape:'javascript':'UTF-8' nofilter}';
      try {
        window.MyParcelBpostModule.paperSize = {mypa_json_encode($papersize)};
      } catch (e) {
        window.MyParcelBpostModule.paperSize = false;
      }
      window.MyParcelBpostModule.askPaperSize = {if !empty($askPaperSize)}true{else}false{/if};
      window.MyParcelBpostModule.debug = {if Configuration::get(MyParcelBpost::LOG_API)}true{else}false{/if};

      new window.MyParcelBpostModule.orderpage(
        {$idOrder|intval nofilter},
        JSON.parse('{$concept|escape:'javascript':'UTF-8' nofilter}'),
        JSON.parse('{$preAlerted|escape:'javascript':'UTF-8' nofilter}'),
        {include file="../translations.tpl"}
      );
    }

    initAdminOrderDetail();
  })();
</script>

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
<script type="text/javascript" src="{$mpbModuleDir|escape:'htmlall':'UTF-8' nofilter}views/js/dist/orderpage-853f0c02eaf3aba7.bundle.min.js"></script>
<script type="text/javascript">
  (function () {
    function initAdminOrderDetail() {
      if (typeof $ === 'undefined'
          || typeof window.MyParcelBpostModule === 'undefined'
          || typeof window.MyParcelBpostModule.orderpage === 'undefined'
          || typeof window.MyParcelBpostModule.orderpage.default === 'undefined'
      ) {
        setTimeout(initAdminOrderDetail, 10);

        return;
      }

      window.MyParcelBpostModule.misc = window.MyParcelBpostModule.misc || {ldelim}{rdelim};
      window.MyParcelBpostModule.misc.process_url = '{$mpbProcessUrl|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.misc.module_url = '{$mpbModuleDir|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.misc.countries = {mypa_json_encode($mpbJsCountries)};
      window.MyParcelBpostModule.invoiceSuggestion = '{$mpbInvoiceSuggestion|escape:'javascript':'UTF-8' nofilter}';
      window.MyParcelBpostModule.weightSuggestion = {$mpbWeightSuggestion|intval};
      try {
        window.MyParcelBpostModule.paperSize = {mypa_json_encode($mpbPaperSize)};
      } catch (e) {
        window.MyParcelBpostModule.paperSize = false;
      }
      window.MyParcelBpostModule.askPaperSize = {if $mpbAskPapersize}true{else}false{/if};
      window.MyParcelBpostModule.debug = {if $mpbLogApi}true{else}false{/if};
      window.MyParcelBpostModule.currency = {
        blank: '{$mpbCurrency->blank|escape:'javascript':'UTF-8'}',
        format: '{$mpbCurrency->format|escape:'javascript':'UTF-8'}',
        sign: '{$mpbCurrency->sign|escape:'javascript':'UTF-8'}',
        iso: '{$mpbCurrency->iso_code|escape:'javascript':'UTF-8'}'
      };
      window.MyParcelBpostModule.async = {if $mpbAsync}true{else}false{/if};

      new window.MyParcelBpostModule.orderpage.default(
        {$mpbIdOrder|intval nofilter},
        JSON.parse('{$mpbConcept|escape:'javascript':'UTF-8' nofilter}'),
        JSON.parse('{$mpbPreAlerted|escape:'javascript':'UTF-8' nofilter}'),
        {include file="../translations.tpl"}
      );
    }

    initAdminOrderDetail();
  })();
</script>

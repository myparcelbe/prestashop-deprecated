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
<script type="text/javascript">
  (function () {
    function initMyParcelExport() {
      if (typeof window.MyParcelBpostModule === 'undefined'
        || typeof window.MyParcelBpostModule.ordergrid === 'undefined'
        || typeof window.MyParcelBpostModule.ordergrid.default === 'undefined'
      ) {
        setTimeout(initMyParcelExport, 10);

        return;
      }

      function documentReady(fn) {
        if (document.readyState !== 'loading'){
          fn();
        } else if (document.addEventListener) {
          document.addEventListener('DOMContentLoaded', fn);
        } else {
          document.attachEvent('onreadystatechange', function() {
            if (document.readyState !== 'loading')
              fn();
          });
        }
      }

      documentReady(function () {
        window.MyParcelBpostModule.misc = window.MyParcelBpostModule.misc || {ldelim}{rdelim};
        window.MyParcelBpostModule.misc.process_url = '{$mpbProcessUrl|escape:'javascript':'UTF-8'}';
        window.MyParcelBpostModule.misc.module_url = '{$mpbModuleDir|escape:'javascript':'UTF-8'}';
        window.MyParcelBpostModule.misc.countries = {mypa_json_encode($mpbJsCountries)};
        window.MyParcelBpostModule.misc.icons = [];
        try {
          window.MyParcelBpostModule.paperSize = {mypa_json_encode($mpbPaperSize)};
        } catch (e) {
          window.MyParcelBpostModule.paperSize = false;
        }
        window.MyParcelBpostModule.askPaperSize = {if $mpbAskPaperSize}true{else}false{/if};
        window.MyParcelBpostModule.debug = {if $mpbLogApi}true{else}false{/if};
        window.MyParcelBpostModule.currency = {
          blank: '{$mpbCurrency->blank|escape:'javascript':'UTF-8'}',
          format: '{$mpbCurrency->format|escape:'javascript':'UTF-8'}',
          sign: '{$mpbCurrency->sign|escape:'javascript':'UTF-8'}',
          iso: '{$mpbCurrency->iso_code|escape:'javascript':'UTF-8'}'
        };
        window.MyParcelBpostModule.async = {if $mpbAsync}true{else}false{/if};

        if (!window.MyParcelBpostModule.paperSize) {
          window.MyParcelBpostModule.paperSize = {
            size: 'standard',
            labels: {
              1: true,
              2: true,
              3: true,
              4: true
            }
          };
        }

        new window.MyParcelBpostModule.ordergrid.default({include file="../translations.tpl"});
      });
    }

    {if $mpbCheckWebhooks}
      var request = new XMLHttpRequest();
      request.open('GET', '{$mpbProcessUrl|escape:'javascript'}&action=CheckWebhooks', true);
      request.send();
      request = null;
    {/if}
    initMyParcelExport();
  }());
</script>

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
<!doctype html>
<html lang="{$language_code|escape:'html':'UTF-8' nofilter}">
<body>
  <div id="mpbpostapp" class="mpbpostcheckout"></div>
  <script type="text/javascript">
    {if $smarty.const._TB_VERSION_}
    window.currencyModes = {mypa_json_encode(Currency::getModes())};
    {/if}
    window.priceDisplayPrecision = {$smarty.const._PS_PRICE_DISPLAY_PRECISION_|intval nofilter};
    window.currency_iso_code = '{Context::getContext()->currency->iso_code|escape:'htmlall':'UTF-8'}';
    window.currencySign = '{Context::getContext()->currency->sign|escape:'javascript':'UTF-8'}';
    window.currencyFormat = {Context::getContext()->currency->format|intval};
    window.currencyBlank = {Context::getContext()->currency->blank|intval};
  </script>
  <script type="text/javascript" src="{$base_dir_ssl|escape:'htmlall':'UTF-8' nofilter}js/jquery/jquery-1.11.0.min.js"></script>
  <script type="text/javascript" src="{$base_dir_ssl|escape:'htmlall':'UTF-8' nofilter}js/tools.js"></script>
  <script type="text/javascript">
    (function () {
      window.addEventListener('message', function (event) {
        if (!event.data) {
          return;
        }

        try {
          var data = JSON.parse(event.data);
        } catch (e) {
          return;
        }

        if (data) {
          if (typeof window.checkout !== 'undefined'
            && typeof data === 'object'
            && data.subject === 'sendStyle'
          ) {
            window.checkout.constructor.setStyle(data.style);

            var newEvent = {
              subject: 'receivedStyle',
              style: data.style
            };
            event.source.postMessage(JSON.stringify(newEvent), event.origin);
          }
        }
      });

      window.MyParcelBpostModule = window.MyParcelBpostModule || {ldelim}{rdelim};
      window.MyParcelBpostModule.misc = window.MyParcelBpostModule.misc || {ldelim}{rdelim};
      window.MyParcelBpostModule.async = {if $mpbAsync}true{else}false{/if};
      window.MyParcelBpostModule.misc.errorCodes = {
        '3212': '{l s='Unknown address' mod='myparcelbpost' js=1}'
      };

      function initMyParcelCheckout() {
        if (typeof window.MyParcelBpostModule === 'undefined'
          || typeof window.MyParcelBpostModule.checkout === 'undefined'
          || typeof window.MyParcelBpostModule.checkout.default === 'undefined'
        ) {
          setTimeout(initMyParcelCheckout, 100);

          return;
        }

        window.checkout = new window.MyParcelBpostModule.checkout.default({
          data: {include file="./example.json"},
          target: 'mpbpostapp',
          form: null,
          iframe: true,
          refresh: false,
          selected: null,
          street: 'Adriaan Brouwerstraat',
          houseNumber: '16',
          postalCode: '2000',
          deliveryDaysWindow: 12,
          dropoffDelay: 0,
          dropoffDays: '1,2,3,4,5',
          cutoffTime: '15:30:00',
          cc: 'BE',
          methodsAvailable: {
            timeframes: true,
            pickup: true,
            signed: true,
            saturdayDelivery: true
          },
          customStyle: {
            foreground1Color: '',
            foreground2Color: '',
            foreground3Color: '',
            background1Color: '',
            background2Color: '',
            highlightColor: '',
            inactiveColor: '',
            fontFamily: '{$mpbCheckoutFont|escape:'javascript':'UTF-8'}',
            fontSize: 2,
          },
          price: {
            standard: 0,
            saturdayDelivery: 2,
            signed: 2,
            pickup: 0,
          },
          signedPreferred: {if $signedPreferred}true{else}false{/if},
          baseUrl: '',
          locale: 'nl-BE',
          currency: 'EUR'
        },
          {include file="../../front/translations.tpl"}
        );
      }

      initMyParcelCheckout();
    })();
  </script>
  <script type="text/javascript" src="{$mypaBpostCheckoutJs|escape:'htmlall':'UTF-8' nofilter}"></script>
</body>
</html>

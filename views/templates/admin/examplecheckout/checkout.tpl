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
  <div id="myparcelapp" class="myparcelcheckout"></div>
  <script type="text/javascript">
    {if $smarty.const._TB_VERSION_}
    window.currencyModes = {Currency::getModes()|json_encode};
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
            window.checkout.setStyle(data.style);

            var newEvent = {
              subject: 'receivedStyle',
              style: data.style
            };
            event.source.postMessage(JSON.stringify(newEvent), event.origin);
          }
        }
      });

      window.MyParcelModule = window.MyParcelModule || {ldelim}{rdelim};
      window.MyParcelModule.misc = window.MyParcelModule.misc || {ldelim}{rdelim};
      window.MyParcelModule.misc.errorCodes = {
        '3212': '{l s='Unknown address' mod='myparcel' js=1}'
      };

      function initMyParcelCheckout() {
        if (typeof window.MyParcelModule === 'undefined'
          || typeof window.MyParcelModule.checkout === 'undefined') {
          setTimeout(initMyParcelCheckout, 100);

          return;
        }

        window.checkout = new MyParcelModule.checkout({
          data: {include file="./example.json"},
          target: 'myparcelapp',
          form: null,
          iframe: true,
          refresh: false,
          selected: null,
          street: 'Siriusdreef',
          houseNumber: '66',
          postalCode: '2132WT',
          deliveryDaysWindow: 12,
          dropoffDelay: 0,
          dropoffDays: '1,2,3,4,5',
          cutoffTime: '15:30:00',
          cc: 'NL',
          methodsAvailable: {
            timeframes: true,
            pickup: true,
            expressPickup: true,
            morning: true,
            night: true,
            signed: true,
            recipientOnly: true,
            signedRecipientOnly: true
          },
          customStyle: {
            foreground1Color: '',
            foreground2Color: '',
            background1Color: '',
            background2Color: '',
            background3Color: '',
            highlightColor: '',
            fontFamily: '{Configuration::get(MyParcel::CHECKOUT_FONT)|escape:'javascript':'UTF-8'}',
            fontSize: 2,
          },
          price: {
            morning: 2,
            standard: 0,
            night: 2,
            signed: 2,
            recipientOnly: 2,
            signedRecipientOnly: 2,
            pickup: 0,
            expressPickup: 0
          },
          signedPreferred: {if $signedPreferred}true{else}false{/if},
          recipientOnlyPreferred: {if $recipientOnlyPreferred}true{else}false{/if},
          baseUrl: '',
          locale: 'nl-NL',
          currency: 'EUR'
        },
          {include file="../../front/translations.tpl"}
        );
      }

      initMyParcelCheckout();
    })();
  </script>
  <script type="text/javascript" src="{$checkoutJs|escape:'htmlall':'UTF-8' nofilter}"></script>
</body>
</html>

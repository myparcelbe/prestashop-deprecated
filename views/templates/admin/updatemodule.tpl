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
<div class="panel">
  <div class="panel-heading">
    <img width="128" height="128" style="width: 16px; height: 16px" src="/modules/myparcelbpost/views/img/myparcelnl-grayscale.png">
    {l s='MyParcel Belgium' mod='myparcelbpost'}
  </div>
  <div class="alert alert-info">
    {l s='Welcome to the new MyParcel module.' mod='myparcelbpost'}
    <br>
    <br>
    {l s='You now have both the previous and the new module installed simultaneously.' mod='myparcelbpost'}
    {l s='Please click "Upgrade" whenever you are ready to move data to the new module and remove the old one.' mod='myparcelbpost'}
    <br>
    <br>
    <a style="color: #fff; height: 40px; font-size: 12pt"
       class="btn btn-success"
       href="{$smarty.server.REQUEST_URI|escape:'html'}&upgrade-myparcel=1"
    >
      {l s='Upgrade' mod='myparcelbpost'} <i class="icon icon-chevron-right"></i>
    </a>
  </div>
</div>

<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2018 thirty bees
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

spl_autoload_register(
    function ($class) {
        if (in_array($class, array(
            'MPBpostModule\\BoxPacker\\Box',
            'MPBpostModule\\BoxPacker\\BoxList',
            'MPBpostModule\\BoxPacker\\Item',
            'MPBpostModule\\BoxPacker\\ItemList',
            'MPBpostModule\\BoxPacker\\PackedBox',
            'MPBpostModule\\BoxPacker\\PackedBoxList',
            'MPBpostModule\\BoxPacker\\Packer',
            'MPBpostModule\\BoxPacker\\VolumePacker',
            'MPBpostModule\\BoxPacker\\WeightRedistribution',
        ))) {
            // project-specific namespace prefix
            $prefix = 'MPBpostModule\\BoxPacker\\';

            // base directory for the namespace prefix
            $baseDir = dirname(__FILE__).'/BoxPacker/';

            // does the class use the namespace prefix?
            $len = Tools::strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                // no, move to the next registered autoloader
                return;
            }

            // get the relative class name
            $relativeClass = Tools::substr($class, $len);

            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $baseDir.str_replace('\\', '/', $relativeClass).'.php';

            // require it
            require $file;
        }

        if (in_array($class, array(
            'MPBpostBrievenbuspakjeItem',
            'MPBpostCarrierDeliverySetting',
            'MPBpostDeliveryOption',
            'MPBpostObjectModel',
            'MPBpostOrder',
            'MPBpostOrderHistory',
            'MPBpostTools',
        ))) {
            // base directory for the namespace prefix
            $baseDir = dirname(__FILE__).'/';

            // replace the namespace prefix with the base directory, replace namespace
            // separators with directory separators in the relative class name, append
            // with .php
            $file = $baseDir.$class.'.php';

            // require it
            require $file;
        }
    }
);

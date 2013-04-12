<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Datatrans_Hiddenmode
 * @copyright  Copyright (c) 2012 PHOENIX MEDIA GmbH & Co. KG (http://www.phoenix-media.eu)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Datatrans_Hiddenmode_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Unified round() implementation for the Datatrans extension
     *
     * @param mixed $value String, Integer or Float
     * @return float
     */
    public function round($value)
    {
        return Zend_Locale_Math::round($value, 2);
    }

    /**
     * Translate byte array to hex string
     *
     * @param String
     * @return String
     */
    public function hexstr($hex)
    {
        $string = '';
        for ($i=0; $i<strlen($hex)-1; $i+=2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
}

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

class Datatrans_Hiddenmode_Model_System_Config_Source_Payment_Language
{
    /**
     * Returns supported languages
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'de',
                'label' => Mage::helper('datatrans_hm')->__('German')
            ),
            array(
                'value' => 'en',
                'label' => Mage::helper('datatrans_hm')->__('English')
            ),
            array(
                'value' => 'fr',
                'label' => Mage::helper('datatrans_hm')->__('French')
            ),
            array(
                'value' => 'it',
                'label' => Mage::helper('datatrans_hm')->__('Italian')
            ),
            array(
                'value' => 'es',
                'label' => Mage::helper('datatrans_hm')->__('Spanish')
            ),
            array(
                'value' => 'el',
                'label' => Mage::helper('datatrans_hm')->__('Greek')
            ),
            array(
                'value' => 'no',
                'label' => Mage::helper('datatrans_hm')->__('Norwegian')
            ),
            array(
                'value' => 'no',
                'label' => Mage::helper('datatrans_hm')->__('Danish')
            ),
        );
    }
}

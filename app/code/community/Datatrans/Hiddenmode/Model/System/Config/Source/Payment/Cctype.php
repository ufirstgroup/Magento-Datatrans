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

class Datatrans_Hiddenmode_Model_System_Config_Source_Payment_Cctype
{
    /**
     * Return the credit card types supported by the datatrans business gateway formatted as an option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        foreach ($this->getCcTypes() as $code => $name) {
            $options[] = array(
                'value' => $code,
                'label' => $name,
            );
        }

        return $options;
    }

    /**
     * Retrieve array of credit card types
     *
     * @return array
     */
    public function getCcTypes()
    {
        $_types = Mage::getConfig()->getNode('global/datatrans_hm/cc/types')->asArray();

        uasort($_types, array('Mage_Payment_Model_Config', 'compareCcTypes'));

        $types = array();
        foreach ($_types as $data) {
            $types[$data['code']] = $data['name'];
        }
        return $types;
    }
}

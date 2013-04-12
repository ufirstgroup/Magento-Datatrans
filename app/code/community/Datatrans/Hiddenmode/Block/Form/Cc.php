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

class Datatrans_Hiddenmode_Block_Form_Cc extends Mage_Payment_Block_Form_Cc
{
    /**
     * Set the method template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('datatrans/hiddenmode/form/cc.phtml');
    }

    /**
     * Return an array with URL's to icons for all supported (and configured) credit card brands
     *
     * @param string $methodCode
     * @return array
     */
    public function getPaymentImageSrcs($methodCode)
    {
        $images = array();
        foreach ($this->getCcAvailableTypes() as $typeCode => $typeName) {
            $imageFilename = Mage::getDesign()->getFilename(
            	'images' . DS . 'datatrans' . DS . 'hiddenmode' . DS . $typeCode,
                array('_type' => 'skin')
            );

            foreach (array('.gif', '.jpg', '-3ds.gif') as $filetype) {
                if (file_exists($imageFilename . $filetype)) {
                    $images[] = $this->getSkinUrl(
                    	'images' . DS . 'datatrans' . DS . 'hiddenmode' . DS . $typeCode . $filetype
                    );
                    break;
                }
            }
        }
        return $images;
    }

    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->getData('cc_available_types');
        if (is_null($types)) {
            $types = Mage::getModel('datatrans_hm/system_config_source_payment_cctype')->getCcTypes();
            if ($method = $this->getMethod()) {
                $availableTypes = $method->getConfigData('cctypes');
                if ($availableTypes) {
                    $availableTypes = explode(',', $availableTypes);
                    foreach ($types as $code=>$name) {
                        if (!in_array($code, $availableTypes)) {
                            unset($types[$code]);
                        }
                    }
                }
            }
            $this->setCcAvailableTypes($types);
        }
        return $types;
    }
}

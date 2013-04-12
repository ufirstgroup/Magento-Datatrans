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

class Datatrans_Hiddenmode_Model_Config
{
    const XML_PATH_DATATRANS_SETTINGS_MERCHANT_ID                   = 'datatrans/settings/merchant_id';
    const XML_PATH_DATATRANS_SETTINGS_HMAC                          = 'datatrans/settings/hmac';
    const XML_PATH_DATATRANS_SETTINGS_RESPONSE_HMAC                 = 'datatrans/settings/response_hmac';
    const XML_PATH_DATATRANS_SETTINGS_LANGUAGE                      = 'datatrans/settings/language';
    const XML_PATH_DATATRANS_SETTINGS_TESTMODE                      = 'datatrans/settings/testmode';
    const XML_PATH_DATATRANS_SETTINGS_PAYINIT_BASE_URL              = 'datatrans/settings/payinit_base_url';
    const XML_PATH_DATATRANS_SETTINGS_PAYCOMPLETE_BASE_URL          = 'datatrans/settings/paycomplete_base_url';
    const XML_PATH_DATATRANS_SETTINGS_TESTMODE_PAYINIT_BASE_URL     = 'datatrans/settings/testmode_payinit_base_url';
    const XML_PATH_DATATRANS_SETTINGS_TESTMODE_PAYCOMPLETE_BASE_URL = 'datatrans/settings/testmode_paycomplete_base_url';

    protected $_storeId;

    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    public function getStoreId()
    {
        return $this->_storeId;
    }

    public function getMerchantId()
    {
        return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_MERCHANT_ID, $this->getStoreId());
    }

    public function getHmac($hex = false)
    {
        $hmac = Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_HMAC, $this->getStoreId());
        if ($hex) {
            return $this->_getHelper()
                ->hexstr($hmac);
        }
        return $hmac;
    }

    public function getResponseHmac()
    {
        return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_RESPONSE_HMAC, $this->getStoreId());
    }

    public function getPayinitBaseURL()
    {
        if ($this->isTestmode()) {
            return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_TESTMODE_PAYINIT_BASE_URL, $this->getStoreId());
        } else {
            return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_PAYINIT_BASE_URL, $this->getStoreId());
        }
    }

    public function getPaycompleteBaseURL()
    {
        if ($this->isTestmode()) {
            return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_TESTMODE_PAYCOMPLETE_BASE_URL, $this->getStoreId());
        } else {
            return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_PAYCOMPLETE_BASE_URL, $this->getStoreId());
        }
    }

    public function getLanguage()
    {
        return Mage::getStoreConfig(self::XML_PATH_DATATRANS_SETTINGS_LANGUAGE, $this->getStoreId());
    }

    public function isTestmode()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DATATRANS_SETTINGS_TESTMODE, $this->getStoreId());
    }

    public function getRegisterSuccessUrl()
    {
        return Mage::getUrl('datatranshm/process/registerSuccess', array('_nosid' => 1));
    }

    public function getRegisterFailUrl()
    {
        return Mage::getUrl('datatranshm/process/registerFail', array('_nosid' => 1));
    }

    public function getRegisterErrorUrl()
    {
        return $this->getRegisterFailUrl();
    }

    /**
     * Return helper
     *
     * @return Datatrans_Hiddenmode_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('datatrans_hm');
    }
}
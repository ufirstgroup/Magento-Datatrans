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

class Datatrans_Hiddenmode_Model_Cc extends Datatrans_Hiddenmode_Model_Abstract
{
    protected $_code = 'datatranshm_cc';

    protected $_formBlockType = 'datatrans_hm/form_cc';
    protected $_infoBlockType = 'datatrans_hm/info_cc';
    protected $_canSaveCc = true;

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcLast4(), -4))
            ->setCcNumber(substr($data->getCcLast4(), -4))
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear());
        return $this;
    }

    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        if ($this->_canSaveCc) {
            $info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        }
        $info->setCcNumber(null)
            ->setCcCid(null);
        return $this;
    }

    /**
     * Return model specific parameters
     *
     * @param array $params
     */
    protected function _getModelSpecificParameters($params = array())
    {
        $params['paymentmethod'] = $this->_getCardTypeByCode($this->getInfoInstance()->getCcType());
        return $params;
    }

    /**
     * Return card type array
     *
     * @return array
     */
    protected function _getCardTypes()
    {
        return array(
            'VI' => 'VIS',
            'MC' => 'ECA',
            'AE' => 'AMX'
        );
    }

    /**
     * Return card type by code
     *
     * @param string $code
     * @return string
     */
    protected function _getCardTypeByCode($code)
    {
        $cardTypes = $this->_getCardTypes();
        return $cardTypes[$code];
    }
}

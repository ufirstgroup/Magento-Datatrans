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

class Datatrans_Hiddenmode_Model_Paypal extends Datatrans_Hiddenmode_Model_Abstract
{
    protected $_code = 'datatranshm_paypal';

    protected $_formBlockType = 'datatrans_hm/form_paypal';
    protected $_infoBlockType = 'datatrans_hm/info_paypal';

    /**
     * Return model specific parameters
     *
     * @param array $params
     */
    protected function _getModelSpecificParameters($params = array())
    {
        $params['paymentmethod'] = 'PAP';
        return $params;
    }
}

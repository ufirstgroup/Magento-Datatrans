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

abstract class Datatrans_Hiddenmode_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    protected $_isGateway              = false;
    protected $_canAuthorize           = false;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = false;
    protected $_canRefund              = true;
    protected $_canVoid                = true;
    protected $_canUseInternal         = false;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;

    /**
     * Config instance
     * @var Datatrans_Hiddenmode_Model_Config
     */
    protected $_config;

    protected $_cardRefId;

    /**
     * Return model specific parameters
     *
     * @param array $params
     */
    abstract protected function _getModelSpecificParameters($params = array());

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::isInitializeNeeded()
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::initialize()
     */
    public function initialize($paymentAction, $stateObject)
    {
        $stateObject
            ->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
            ->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT)
            ->setIsNotified(false);

        $this->_getSession()
            ->setDatatransPaymentMethod($this->getCode());

        // Initialize config
        $this->getConfig();

        return $this;
    }

    /**
     * Send request to datatrans
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @param string $reqType
     * @return SimpleXMLElement
     */
    protected function _request(Varien_Object $payment, $amount, $reqType = 'COA', $transType = '05') {
        $amount = intval($this->_getHelper()->round($amount, 2) * 100);
        $requestBody =
<<<XML
<?xml version="1.0" encoding="UTF-8" ?>
 <paymentService version="1">
   <body merchantId="{$this->getConfig()->getMerchantId()}">
     <transaction refno="{$payment->getOrder()->getIncrementId()}">
       <request>
         <amount>{$amount}</amount>
         <currency>{$payment->getOrder()->getOrderCurrencyCode()}</currency>
         <uppTransactionId>{$payment->getLastTransId()}</uppTransactionId>
         <reqtype>{$reqType}</reqtype>
         <transtype>{$transType}</transtype>
       </request>
     </transaction>
   </body>
 </paymentService>
XML;
        $this->debugData($this->_getHelper()->__('Datatrans %s request. Data:%s', $reqType, $requestBody));

        $config = array(
            'adapter'      => 'Zend_Http_Client_Adapter_Socket',
            'ssltransport' => 'tls'
        );
        $client = new Zend_Http_Client($this->getConfig()->getPaycompleteBaseURL(), $config);
        $client->setRawData($requestBody);
        $response = $client->request();

        $this->debugData($this->_getHelper()->__('Datatrans %s request. Data:%s', $reqType, $response->getBody()));

        $responseXml = new SimpleXMLElement($response->getBody());

        $status = (string)current($responseXml->xpath('/paymentService/body/transaction/@status'));
        if ($status == 'error' || $responseXml->xpath('//error')) {
            $errorCode    = (string)current($responseXml->xpath('//error/errorCode'));
            $errorMessage = (string)current($responseXml->xpath('//error/errorMessage'));
            $errorDetail  = (string)current($responseXml->xpath('//error/errorDetail'));
            $message = array(
                $this->_getHelper()->__('Datatrans %s error.', $reqType),
                $this->_getHelper()->__('Error Code: %s', $errorCode),
                $this->_getHelper()->__('Error Message: %s', $errorMessage),
                $this->_getHelper()->__('Error Detail: %s', $errorDetail)
            );
            $message = implode('<br />', $message);
            $payment->setStatus(self::STATUS_ERROR);
            $this->getOrder()
                ->addStatusHistoryComment($message)
                ->save();
            $this->_throwException($message);
        }

        return $responseXml;
    }

    /**
     * Capture preatutharized amount
     * @param Varien_Object $payment
     * @param float $amount
     */
    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);

        if (Mage::app()->getRequest()->getParam('uppTransactionId')) {
            // capture is called from response action
            $payment->setStatus(self::STATUS_APPROVED);
            return $this;
        }

        $responseXml = $this->_request($payment, $amount, 'COA');
        $payment->setStatus(self::STATUS_SUCCESS);
        return $this;
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::refund()
     */
    public function refund(Varien_Object $payment, $amount)
    {
        $responseXml = $this->_request($payment, $payment->getAmountPaid(), 'COA', '06');
        $payment->setStatus(self::STATUS_DECLINED)
            ->setIsTransactionClosed(1);
        return $this;
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::cancel()
     */
    public function cancel(Varien_Object $payment)
    {
        $responseXml = $this->_request($payment, $payment->getAmountAuthorized(), 'DOA');
        $payment->setStatus(self::STATUS_DECLINED)
            ->setIsTransactionClosed(1);
        return $this;
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::cancel()
     */
    public function void(Varien_Object $payment)
    {
        return $this->cancel($payment);
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::getInfoInstance()
     */
    public function getInfoInstance()
    {
        $payment = $this->getData('info_instance');
        if (!$payment) {
            $payment = $this->getOrder()->getPayment();
            $this->setInfoInstance($payment);
        }
        return $payment;
    }

    /**
     * Return URL for redirection after order placed
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        $amount     = round($this->getOrder()->getGrandTotal() * 100);
        $currency   = $this->getOrder()->getOrderCurrencyCode();
        $refno      = $this->getOrder()->getIncrementId();

        $params = array(
            'hiddenMode' => 'yes',
            'testOnly'   => $this->getConfig()->isTestmode() ? 'yes' : 'no',
            'language'   => $this->getConfig()->getLanguage(),
            'merchantId' => $this->getConfig()->getMerchantId(),
            'amount'     => $amount,
            'currency'   => $currency,
            'refno'      => $refno,
            'successUrl' => $this->getConfig()->getRegisterSuccessUrl(),
            'errorUrl'   => $this->getConfig()->getRegisterErrorUrl(),
            'cancelUrl'  => $this->getConfig()->getRegisterFailUrl(),
            'reqtype'    => $this->getConfigData('payment_action', $this->getOrder()->getStoreId()) == self::ACTION_AUTHORIZE_CAPTURE ? 'CAA' : 'NOA',
            'sign'       => $this->generateSignature($amount, $currency, $refno)
        );

        $url = $this->_appendQueryParams(
            $this->getConfig()->getPayinitBaseURL(),
            $this->_getModelSpecificParameters($params)
        );

        // save quote and order references in session
        $session = Mage::getSingleton('checkout/session');
        $session->setDatatransQuoteId($session->getQuoteId());
        $session->setDatatransSuccessQuoteId($session->getLastSuccessQuoteId());
        $session->setDatatransRealOrderId($this->getOrder()->getIncrementId());

        $this->debugData($this->_getHelper()->__('Datatrans order place url: %s', $url));

        return $url;
    }

    /**
     * Return order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        if (!$this->_order && !Mage::app()->getStore()->isAdmin()) {
            if ($id = $this->_getSession()->getLastOrderId()) {
                $this->_order = Mage::getModel('sales/order')->load($id);
            }
        }
        if (!$this->_order) {
            $this->_order = Mage::registry('datatrans_hm_current_order');
        }
        return $this->_order;
    }

    /**
     * Return specified additional information from payment info instance
     *
     * @param string $key
     * @param Varien_Object $payment
     * @return string
     */
    public function getPaymentInfoData($key, $payment = null)
    {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        return $payment->getAdditionalInformation($key);
    }

    /**
     * Add additional information to the payment info instance. If the instance
     * already is recorded in the databese, save the new settings.
     *
     * @param array $data
     * @param Varien_Object $payment
     * @return Datatrans_Hiddenmode_Model_Abstract
     */
    public function addPaymentInfoData(array $data, $payment = null)
    {
        if (is_null($payment)) {
            $payment = $this->getInfoInstance();
        }
        foreach ($data as $k => $v) {
            $payment->setAdditionalInformation($k, $v);
        }
        if ($payment->getId()) {
            /*
             * Required since Magento 1.4.1.1
             */
            $payment->save();
        }
        return $this;
    }

    /**
     * Set config object
     *
     * @param Datatrans_Hiddenmode_Model_Config $config
     * @return Datatrans_Hiddenmode_Model_Abstract
     */
    public function setConfig(Datatrans_Hiddenmode_Model_Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Return config object
     *
     * @return Datatrans_Hiddenmode_Model_Config
     */
    public function getConfig()
    {
        if (!$this->_config) {
            $order = $this->getOrder();
            if (!$order) {
                $this->_throwException('Cannot initialize config.');
            }
            $this->_config = Mage::getModel('datatrans_hm/config')
                ->setStoreId($order->getStoreId());
        }
        return $this->_config;
    }

    /**
     * Generate signature
     *
     * @param number $amount
     * @param string $currency
     * @param number $refno
     * @return string
     */
    public function generateSignature($amount, $currency, $refno)
    {
        return hash_hmac(
            'md5',
            $this->getConfig()->getMerchantId() . $amount . $currency . $refno,
            $this->getConfig()->getHmac(true)
        );
    }

    /**
     * Append the array of parameters to the given URL string
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function _appendQueryParams($url, array $params)
    {
        foreach ($params as $k => $v) {
            $url .= strpos($url, '?') === false ? '?' : '&';
            $url .= sprintf("%s=%s", $k, $v);
        }
        return $url;
    }

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /* (non-PHPdoc)
     * @see code/core/Mage/Payment/Model/Method/Mage_Payment_Model_Method_Abstract::_getHelper()
     */
    protected function _getHelper()
    {
        return Mage::helper('datatrans_hm');
    }

    /**
     * Throw an exception with a default error message if none is specified
     *
     * @param string $msg
     * @param array $params
     */
    protected function _throwException($msg = null, $params = null)
    {
        if (is_null($msg)) {
            $msg = $this->getConfigData('generic_error_msg');
        }
        Mage::throwException($this->_getHelper()->__($msg, $params));
    }

    /**
     * Used to call debug method from not Paymant Method context
     *
     * @param mixed $debugData
     */
    public function debugData($debugData)
    {
        $this->_debug($debugData);
    }

    /**
     * Log debug data to file
     *
     * @param mixed $debugData
     */
    protected function _debug($debugData)
    {
        if ($this->getDebugFlag()) {
            Mage::getModel('core/log_adapter', 'payment_' . $this->getCode() . '.log')
               ->setFilterDataKeys($this->_debugReplacePrivateDataKeys)
               ->log($debugData);
        }
    }

    /**
     * Define if debugging is enabled
     *
     * @return bool
     */
    public function getDebugFlag()
    {
        return $this->getConfigData('debug');
    }
}

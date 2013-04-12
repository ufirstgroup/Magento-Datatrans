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

class Datatrans_Hiddenmode_ProcessController extends Mage_Core_Controller_Front_Action
{
    protected $_order = NULL;
    protected $_paymentInst = NULL;

    /**
     * Register success action
     * Finalize order and redirect to success page. On error cancel order
     * and redirect to registerFailAction
     */
    public function registerSuccessAction()
    {
        try {
            $request = $this->_checkReturnedPost();
            $this->_processSale($request);
            $session = $this->_getSession();
            $session->unsDatatransRealOrderId();
            $session->setQuoteId($session->getDatatransQuoteId(true));
            $session->setLastSuccessQuoteId($session->getDatatransSuccessQuoteId(true));
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_logException($e);
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_logException($e);
            Mage::helper('datatrans_hm')->__('An error occured during payment.');
        }
        // cancel order and redirect to shopping cart
        $this->registerFailAction();
    }

    /**
     * Register fail action
     * Cancel order and redirect to shopping cart.
     */
    public function registerFailAction()
    {
        try {
            $request = $this->_checkReturnedPost();
            $this->_processCancel($request);

            // set quote to active
            $session = $this->_getSession();
            if ($quoteId = $session->getDatatransQuoteId()) {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                if ($quote->getId()) {
                    $quote->setIsActive(true)->save();
                    $session->setQuoteId($quoteId);
                }
            }

            // add payment cancel notice
            $errorCode = $this->getRequest()->getParam('errorCode', false);
            if (isset($request['errorCode']) && $request['errorCode'] == '1404') {
                $session->addError(Mage::helper('datatrans_hm')->__('Unfortunately, your credit card was declined as its limit was reached.'));
            } else {
                $session->addError(Mage::helper('datatrans_hm')->__('An error occured while processing the payment. Please contact the store owner for assistance.'));
            }
            $session->addError(Mage::helper('datatrans_hm')->__('The order has been canceled.'));
        } catch (Mage_Core_Exception $e) {
            $this->_logException($e);
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_logException($e);
            Mage::helper('datatrans_hm')->__('An error occured during payment.');
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Checking POST variables.
     * Creating invoice if payment was successfull or cancel order if payment was declined
     */
    protected function _checkReturnedPost()
    {
        // check request type
        if (!$this->getRequest()->isPost()) {
            Mage::throwException('Wrong request type.');
        }

        // get request variables
        $request = $this->getRequest()->getPost();
        if (empty($request)) {
            Mage::throwException('Request doesn\'t contain POST elements.');
        }

        // check order id
        if (empty($request['refno']) || strlen($request['refno']) > 50) {
            Mage::throwException('Missing or invalid order ID');
        }

        // load order for further validation
        $this->_order = $this->_initOrder($request['refno']);

        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();

        // check signature
        if (!empty($request['amount']) &&
            !empty($request['currency']) &&
            !empty($request['sign'])) {
            $signature = $this->_paymentInst->generateSignature(
                $request['amount'],
                $request['currency'],
                $request['refno']
            );
            if ($request['sign'] != $signature) {
                Mage::throwException(Mage::helper('datatrans_hm')->__('Datatrans response. Signs not equal error.'));
            }
        } else {
            Mage::throwException(Mage::helper('datatrans_hm')->__('Datatrans response. Signs not equal error.'));
        }

        return $request;
    }

    /**
     * Init order
     *
     * @param string  Sales increment ID
     * @return Mage_Sales_Model_Order
     */
    protected function _initOrder($incrementId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        if (!$order->getId()) {
             Mage::throwException(
                 Mage::helper('datatrans_hm')->__('Order with increment ID %d does not exist.', $incrementId)
             );
        }

        return $order;
    }

    /**
     * Process sale and finalize order
     *
     * @param array  Return parameters from PSP
     * @return void
     */
    protected function _processSale(Array $request)
    {
        // check transaction amount and currency
        $price      = round($this->_order->getGrandTotal() * 100);
        $currency   = $this->_order->getOrderCurrencyCode();

        // check transaction amount
        if ($price != (int)$request['amount']) {
            Mage::throwException(Mage::helper('datatrans_hm')->__('Transaction amount doesn\'t match.'));
        }

        // check transaction currency
        if ($currency != $request['currency']) {
            Mage::throwException(Mage::helper('datatrans_hm')->__('Transaction currency doesn\'t match.'));
        }

        // save transaction information
        $this->_order->getPayment()
            ->setTransactionId($request['uppTransactionId'])
            ->setLastTransId($request['uppTransactionId'])
            ->setAdditionalData((isset($request['authorizationCode']) ? $request['authorizationCode'] : null));

        switch($request['reqtype']) {
            case 'CAA':
                // authorize and capture request
                if ($this->_order->canInvoice()) {
                    $invoice = $this->_order->prepareInvoice();
                    $invoice->register()->capture();
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
                }

                $message = array(
                    Mage::helper('datatrans_hm')->__('Customer successfully returned from Datatrans Payment'),
                    Mage::helper('datatrans_hm')->__('Unique Transaction Identifier: %s', $request['uppTransactionId']),
                    Mage::helper('datatrans_hm')->__('Payment Method: %s', $request['pmethod']),
                    Mage::helper('datatrans_hm')->__('Card Issuing Banks Authorization Code: %s', $request['acqAuthorizationCode'])
                );
                $this->_order->addStatusToHistory($this->_paymentInst->getConfigData('order_status'), implode('<br />', $message));
                break;

            case 'NOA':
                // authorize only request
                $this->_order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    $this->_paymentInst->getConfigData('order_status'),
                    Mage::helper('datatrans_hm')->__('The payment has been authoized successfully')
                );

                $this->_order->getPayment()
                    ->setAmountAuthorized($request['amount'])
                    ->setIsTransactionClosed(0);
                $this->_order->getPayment()
                    ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                break;
        }

        $this->_order->sendNewOrderEmail();
        $this->_order->setEmailSent(true);

        $this->_order->save();
    }

    /**
     * Process cancelation of order
     *
     * @param array  Return parameters from PSP
     * @return void
     */
    protected function _processCancel(Array $request)
    {
        if ($this->_order->canCancel()) {
            $this->_order->cancel();
            $notice = (!empty($request['errorDetail']) ? $request['errorDetail'] : Mage::helper('datatrans_hm')->__('Payment was canceled'));
            $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, 'Datatrans: ' . $notice);
            $this->_order->save();
        }
    }

    /**
     * Return the checkout session
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Log exception via payment method or global log mechanism
     *
     * @param exception
     * @return void
     */
    protected function _logException(Exception $e)
    {
        if (is_null($this->_paymentInst)) {
            Mage::logException($e);
        } else {
            $this->_paymentInst->debugData($e->getMessage());
        }
        return $this;
    }
}

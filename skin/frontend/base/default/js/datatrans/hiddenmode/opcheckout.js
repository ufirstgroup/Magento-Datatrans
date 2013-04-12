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
if(typeof Datatrans == 'undefined') {
        var Datatrans = {};
}

Datatrans.Hiddenmode = Class.create({
        initialize: function() {
            var obj1 = typeof payment == 'undefined' ? Payment.prototype : payment;
            if(typeof obj1.save != 'undefined'){
                obj1.save = obj1.save.wrap(function (origMethod) {
                        if (checkout.loadWaiting != false) return;
                        var validator = new Validation(this.form);
                        if (this.validate() && validator.validate()) {
                                datatrans.ccnum = '';
                                datatrans.cccvc = '';
                                datatrans.ccexpmonth = '';
                                datatrans.ccexpyear = '';
                                if (this.currentMethod && this.currentMethod.substr(0, 12) == 'datatranshm_') {
                                        if (this.currentMethod == 'datatranshm_cc') {
                                                datatrans.ccnum = $('datatranshm_cc_cc_number').value;
                                                datatrans.cccvc = $('datatranshm_cc_cc_cid').value;
                                                datatrans.ccexpmonth = $('datatranshm_cc_expiration').value;
                                                datatrans.ccexpyear = $('datatranshm_cc_expiration_yr').value;
                                                var ccLast4StartPos = $('datatranshm_cc_cc_number').value.length - 4;
                                                $('datatranshm_cc_cc_last4').value = $('datatranshm_cc_cc_number').value.substring(ccLast4StartPos, ccLast4StartPos + 4);
                                        }
                                        datatrans.disableFields();
                                }
                                origMethod();
                                if (this.currentMethod && this.currentMethod.substr(0, 12) == 'datatranshm_') {
                                    datatrans.disableFields(false);
                                    var obj2 = typeof review == 'undefined' ? Review.prototype : review;
                                    obj2.save = obj2.save.wrap(function (origMethod) {
                                        datatrans.disableFields();
                                        origMethod();
                                        datatrans.disableFields(false);
                                    });
                                    if(typeof review == 'undefined'){
                                        // Magento 1.5.0.1 and higher
                                        Review.prototype.nextStep = Review.prototype.nextStep.wrap(datatrans.processReviewResponse);
                                        Review.prototype.resetLoadWaiting = Review.prototype.resetLoadWaiting.wrap(datatrans.resetLoadWaiting);
                                    }else{
                                        // Magento 1.4.2.0 and lower
                                        review.onSave = datatrans.processReviewResponse;
                                        review.onComplete = datatrans.updateLoadWaiting;
                                    }
                                }
                        }
                });
            }else{
                checkout.submitComplete = checkout.submitComplete.wrap(function(origMethod, request){
                    datatrans.ccnum = '';
                    datatrans.cccvc = '';
                    datatrans.ccexpmonth = '';
                    datatrans.ccexpyear = '';
                    if (payment.currentMethod && payment.currentMethod.substr(0, 12) == 'datatranshm_') {
                        if (payment.currentMethod == 'datatranshm_cc') {
                            datatrans.ccnum = $('datatranshm_cc_cc_number').value;
                            datatrans.cccvc = $('datatranshm_cc_cc_cid').value;
                            datatrans.ccexpmonth = $('datatranshm_cc_expiration').value;
                            datatrans.ccexpyear = $('datatranshm_cc_expiration_yr').value;
                        }
                        datatrans.disableFields();
                        var transport;
                        if (request.transport) transport = request.transport;
                        else transport = false;
                        datatrans.processReviewResponse(review.nextStep, transport);
                    }else{
                        origMethod(request);
                    }
                });
            }
        },
        disableFields: function(mode) {
                if (typeof mode == 'undefined') mode = true;
                var form = $('payment_form_' + payment.currentMethod);
                var elements = form.getElementsByClassName('no-submit');
                for (var i=0; i<elements.length; i++) elements[i].disabled = mode;
        },
        updateLoadWaiting: function(request)
        {
                var transport;
                if (request.transport) transport = request.transport;
                else transport = false;
                
                if (transport && transport.responseText) {
                        try {
                                var response = eval('(' + transport.responseText + ')');
                                if (response.redirect) {
                                        /*
                                         * Keep the spinner active
                                         */
                                        return true;
                                }
                        }
                        catch (e) {}
                }
                /*
                 * Some kind of error - deactivate the spinner
                 */
                checkout.setLoadWaiting(false);
        },
        resetLoadWaiting: function(transport)
        {
            if (transport && transport.responseText) {
                try {
                    var response = eval('(' + transport.responseText + ')');
                    if (response.redirect) {
                        /*
                         * Keep the spinner active
                         */
                        return true;
                    }
                }
                catch (e) {}
            }
            /*
             * Some kind of error - deactivate the spinner
             */
            checkout.setLoadWaiting(false);
        },
        processReviewResponse: function(origMethod, transport)
        {
                if(!transport && origMethod.transport){
                    transport = origMethod.transport;
                }
                if (payment.currentMethod && payment.currentMethod.substr(0, 12) == 'datatranshm_') {
                        if (transport && transport.responseText) {
                                try {
                                        var response = eval('(' + transport.responseText + ')');
                                        if (response.redirect) {
                                                temp = response.redirect.split('?');
                                                var form = new Element('form', {'action': temp[0], 'method': 'post', 'id': 'datatrans_hm_transport'});
                                                $$('body')[0].insert(form);
                                                temp = temp[1].split('&');
                                                for(i=0; i<temp.length; i++){
                                                    pair = temp[i].split('=');
                                                    if(pair.length == 2){
                                                        form.insert(new Element('input', {'type': 'hidden', 'name': pair[0], 'value':  pair[1]}));
                                                    }
                                                }
                                                if (String(datatrans.ccnum).length > 0) {
                                                        form.insert(new Element('input', {'type': 'hidden', 'name': 'cardno', 'value':  datatrans.ccnum}));
                                                        form.insert(new Element('input', {'type': 'hidden', 'name': 'expm', 'value':  (datatrans.ccexpmonth.length < 2 ? '0' : '') + datatrans.ccexpmonth}));
                                                        form.insert(new Element('input', {'type': 'hidden', 'name': 'expy', 'value':  datatrans.ccexpyear.substring(2)}));
                                                        form.insert(new Element('input', {'type': 'hidden', 'name': 'cvv', 'value':  datatrans.cccvc}));
                                                }
                                                form.submit();
                                                
                                                return true;
                                        }
                                }
                                catch (e) {}
                        }
                }
                if(origMethod.transport){
                    review.nextStep(transport);
                }else{
                    origMethod(transport);
                }
        }
});

Event.observe(window, 'load', function() {
        datatrans = new Datatrans.Hiddenmode();
});
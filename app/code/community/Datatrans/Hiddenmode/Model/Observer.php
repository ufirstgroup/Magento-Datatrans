<?php

class Datatrans_Hiddenmode_Model_Observer
{
    /**
     * Stores current order in registry
     *
     * @param Varien_Event_Observer $observer
     * @return Datatrans_Hiddenmode_Model_Observer
     */
    public function saveOrderInRegistry(Varien_Event_Observer $observer)
    {
        if (Mage::registry('datatrans_hm_current_order')) {
            Mage::unregister('datatrans_hm_current_order');
        }
        Mage::register('datatrans_hm_current_order', $observer->getOrder());
        return $this;
    }
}

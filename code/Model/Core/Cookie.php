<?php

class Loewenstark_Crypt_Model_Core_Cookie
extends Mage_Core_Model_Cookie
{
    /**
     * Is https secure request
     * Use secure on adminhtml ONLY,
     * naaa also for Backend bro
     *
     * @return bool
     */
    public function isSecure()
    {
        if (Mage::helper('ldscrypt')->isFrontendSecureOnly())
        {
            return $this->_getRequest()->isSecure();
        }
        return parent::isSecure();
    }
}

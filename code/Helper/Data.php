<?php

class Loewenstark_Crypt_Helper_Data
extends Mage_Core_Helper_Data
{
    /**
     * Check if there is https on both
     * secure and non-secure
     * 
     * @return 
     */
    public function isFrontendSecureOnly()
    {
        if (strstr(Mage::getStoreConfig('web/unsecure/base_url'), 'https://')
                && strstr(Mage::getStoreConfig('web/secure/base_url'), 'https://')
        )
        {
            return true;
        }
        return false;
    }
}

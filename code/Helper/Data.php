<?php

class Loewenstark_Crypt_Helper_Data
extends Mage_Core_Helper_Data
{
    /**
     * 
     * @return bool
     */
    public function canUseStrongPasswordHash()
    {
        if (!function_exists('password_hash'))
        {
            return false;
        }
        return Mage::getStoreConfigFlag('customer/password/use_strong_password_hash');
    }

    /**
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

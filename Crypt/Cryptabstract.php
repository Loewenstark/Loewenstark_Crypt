<?php

class Varien_Crypt_Cryptabstract
extends Varien_Crypt_Abstract
{
    /**
     * 
     * @param string $key
     * @return boolean
     * @throws Mage_Core_Exception
     */
    protected function _checkLengthOfKey($key)
    {
        if (strlen($key) >= 6)
        {
            return true;
        }
        throw new Mage_Core_Exception('Your encryption key is to short.');
        return false;
    }

    /**
     * 
     * @param string $key
     * @return $this
     */
    public function init($key)
    {
        $this->_checkLengthOfKey($key);
        return $this;
    }
}

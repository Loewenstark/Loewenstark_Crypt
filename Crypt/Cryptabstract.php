<?php

class Varien_Crypt_Cryptabstract
extends Varien_Crypt_Abstract
{
    /**
     * Constuctor
     *
     * @param array $data
     */
    public function __construct(array $data=array())
    {
        register_shutdown_function(array($this, 'destruct'));
        parent::__construct($data);
    }

    public function destruct()
    {
        $this->_reset();
    }

    protected function _reset()
    {
    }

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

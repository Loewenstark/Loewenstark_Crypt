<?php

class Varien_Crypt_Openssl
extends Varien_Crypt_Cryptabstract
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

    /**
     * Close mcrypt module on shutdown
     */
    public function destruct()
    {
        $this->_reset();
    }

    /**
     * Initialize mcrypt module
     *
     * @param string $key cipher private key
     * @return Varien_Crypt_Mcrypt
     */
    public function init($key)
    {
        parent::init($key);
        if (!$this->getCipher()) {
            $this->setCipher('aes-256-cbc');
        }

        if (!$this->getMode()) { // mode is in cipher!
            $this->setMode($this->getCipher());
        }
        
        $this->setKey($key);

        return $this;
    }

    /**
     * Encrypt data
     *
     * @param string $data source string
     * @return string
     */
    public function encrypt($data)
    {
        if (strlen($data) == 0) {
            return $data;
        }
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->getCipher()));
        return base64_encode($iv).':'.base64_encode(openssl_encrypt($data, $this->getCipher(), $this->getKey(),  OPENSSL_RAW_DATA, $iv));
    }

    /**
     * Decrypt data
     *
     * @param string $data encrypted string
     * @return string
     */
    public function decrypt($data)
    {
        if (strlen($data) == 0) {
            return $data;
        }
        $iv = '';
        $dec = $data;
        // check if there is salt in
        if (strstr($data, ':'))
        {
            list($iv, $dec) = explode(':', $data, 2);
            $iv = base64_decode($iv);
        }
        unset($data);
        return openssl_decrypt(base64_decode($dec), $this->getCipher(), $this->getKey(), OPENSSL_RAW_DATA, $iv);
    }

    /**
     * 
     */
    protected function _reset()
    {
    }
}
<?php

class Varien_Crypt_Sodium
extends Varien_Crypt_Cryptabstract
{
    /**
     * Initialize sodium module
     *
     * @param string $key cipher private key
     * @return Varien_Crypt_Sodium
     */
    public function init($key)
    {
        parent::init($key);
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
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce) . ':'
                . sodium_crypto_secretbox($data, $nonce, $this->getKey());
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
        $nonce = '';
        $dec = $data;
        // check if there is salt in
        if (strstr($data, ':'))
        {
            list($nonce, $dec) = explode(':', $data, 2);
            $nonce = base64_decode($nonce);
        }
        unset($data);
        return sodium_crypto_secretbox_open($dec, $nonce, $this->getKey());
    }
}
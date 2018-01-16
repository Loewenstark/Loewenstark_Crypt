<?php
/**
 * 
 * Special thanks to https://www.zimuel.it/slides/phpce2017/
 */

class Varien_Crypt_Openssl
extends Varien_Crypt_Cryptabstract
{
    /**
     * Initialize openssl module
     * Check if modes are supported
     *
     * @param string $key cipher private key
     * @return Varien_Crypt_Openssl
     */
    public function init($key)
    {
        parent::init($key);
        if (!$this->getCipher()) {
            $this->setCipher('aes-256-cbc');
        }

        if (!function_exists('openssl_encrypt'))
        {
            throw new Mage_Core_Exception('Could not found php-openssl');
        }

        if (!in_array($this->getCipher(), openssl_get_cipher_methods(true)))
        {
            throw new Mage_Core_Exception('Could not found Cipher: '.$this->getCipher());
        }

        // gcm is only able to use since php 7.1
        if (stristr($this->getCipher(), 'gcm') && version_compare(PHP_VERSION, '7.1.0', '<'))
        {
            throw new Mage_Core_Exception('gcm mode is only possible in PHP7.1');
        }

        if (!$this->getMode()) { // mode is in cipher!
            $this->setMode($this->getCipher());
        }

        $this->setKey($key);

        return $this;
    }

    /**
     * 
     * @return int
     */
    protected function _getIvLength()
    {
        // on aes-256-cbc, cipher length sould be 16
        return openssl_cipher_iv_length($this->getCipher());
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
        $iv = openssl_random_pseudo_bytes($this->_getIvLength());
        return $iv.
                openssl_encrypt(
                    $data,
                    $this->getCipher(),
                    $this->getKey(),
                    OPENSSL_RAW_DATA,
                    $iv
                );
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
        return openssl_decrypt(
                mb_substr($data, $this->_getIvLength(), null, '8bit'),
                $this->getCipher(),
                $this->getKey(),
                OPENSSL_RAW_DATA,
                mb_substr($data, 0, $this->_getIvLength(), '8bit')
            );
    }
}
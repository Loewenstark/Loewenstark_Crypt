<?php

class Loewenstark_Crypt_Model_Encryption
extends Mage_Core_Model_Encryption
{
    /**
     * get Name of default encryption model
     * 
     * @var string
     */
    protected $_defaultCryptModel = 'mcrypt';

    /**
     * name of used encryption model
     * 
     * @var string
     */
    protected $_cryptExtension = null;

    /**
     * model array of used Crypt Models
     * 
     * @var string
     */
    protected $_cryptModel = array();

    /**
     * hash string with md5
     * 
     * @return string
     */
    public function hashByMd5($data)
    {
        return hash('md5', $data);
    }

    /**
     * hash string with sha256
     * 
     * @return string
     */
    public function hashBySha256($data)
    {
        return hash('sha256', $data);
    }

    /**
     * Bcrypt Hash
     * in PHP >= 5.5.0 fallback to hash256
     * 
     * @param string $data
     * @return string
     */
    public function hashByBcrypt($data)
    {
        if (!defined('PASSWORD_BCRYPT'))
        {
            return $this->hashBySha256($data);
        }
        return password_hash($data, PASSWORD_BCRYPT, array('cost' => 12));
    }

    /**
     * Argon2 Crypt Hash
     * in PHP >= 7.2 fall back to bcrypt
     * 
     * @param string $data
     * @return string
     */
    public function hashByArgon2($data)
    {
        if (!defined('PASSWORD_ARGON2I'))
        {
            // fall back
            return $this->hashByBcrypt($data);
        }
        return password_hash($data, PASSWORD_ARGON2I);
    }

    /**
     *
     * @param string $password
     * @param mixed $salt
     * @return string
     */
    public function getStrongHash($password, $salt = false)
    {
        if (is_integer($salt)) {
            $salt = $this->_helper->getRandomString($salt);
        }
        if ($this->_canUseArgon2() && defined('PASSWORD_ARGON2I'))
        {
            return $salt === false ? $this->hashByArgon2($password) : $this->hashByArgon2($salt . $password) . ':' . $salt;
        } else {
            return $salt === false ? $this->hashByBcrypt($password) : $this->hashByBcrypt($salt . $password) . ':' . $salt;
        }
    }

    /**
     * hash string
     * 
     * @return string
     */
    public function hash($data)
    {
        return $this->hashBySha256($data);
    }

    /**
     * Validate hash against hashing method (with or without salt)
     *
     * @param string $password
     * @param string $hash
     * @return bool
     * @throws Exception
     */
    public function validateHash($password, $hash)
    {
        $hashArr = explode(':', $hash);
        // without salt
        if (count($hashArr) == 1)
        {
            if ($this->password_verify($password, $hash)
                || $this->hashByMd5($password) === $hash
                || $this->hashBySha256($password) === $hash)
            {
                return true;
            }
            return false;
        } elseif(count($hashArr) == 2)
        {
            if ($this->password_verify($hashArr[1] . $password, $hashArr[0])
                || $this->hashByMd5($hashArr[1] . $password) === $hashArr[0]
                || $this->hashBySha256($hashArr[1] . $password) === $hashArr[0])
            {
                return true;
            }
            return false;
        }
        Mage::throwException('Invalid hash.');
    }

    /**
     * Verifies that a password matches a hash
     * 
     * @param string $password
     * @param string $hash
     * @return boolean
     */
    protected function password_verify ($password, $hash)
    {
        if (function_exists('password_verify'))
        {
            return password_verify($password, $hash);
        }
        return false;
    }

    /**
     *
     * @param string $key
     * @return Varien_Crypt_Abstract
     */
    protected function _getCrypt($key = null)
    {
        if (!$this->_crypt) {
            if (null === $key) {
                $key = (string)Mage::getConfig()->getNode('global/crypt/key');
            }
            $extension = $this->_getCryptExtension();
            $this->_crypt = Varien_Crypt::factory($extension)->init($key);
        }
        return $this->_crypt;
    }

    /**
     *
     * @param string $key
     * @param string $extension Model Name
     * @return Varien_Crypt_Abstract
     */
    protected function _getCryptByModel($key = null, $extension = 'mcrypt') 
    {
        if ($extension == $this->_getCryptExtension())
        {
            return $this->_getCrypt($key);
        }
        if (!isset($this->_cryptModel[$extension])) {
            if (null === $key) {
                $key = (string)Mage::getConfig()->getNode('global/crypt/key');
            }
            if ($extension == 'mcrypt')
            {
                $this->_loadWorkaroundForMcrypt();
            }
            $this->_cryptModel[$extension] = Varien_Crypt::factory($extension)->init($key);
        }
        return $this->_cryptModel[$extension];
    }

    /**
     * get Extension Name
     * will be Mcrypt, when its not defined
     * 
     * @return string
     */
    protected function _getCryptExtension()
    {
        if (is_null($this->_cryptExtension))
        {
            $extension = trim((string)Mage::getConfig()->getNode('global/crypt/extension'));
            if (empty($extension))
            {
                $extension = trim($this->_defaultCryptModel);
            }
            if ($extension == 'mcrypt')
            {
                $this->_loadWorkaroundForMcrypt();
            }
            $this->_cryptExtension = strtolower($extension);
        }
        return $this->_cryptExtension;
    }

    /**
     * added handle to enable argon2
     * may you are using an
     * mixed state of PHP7 and PHP5
     * 
     * @return boolean
     */
    protected function _canUseArgon2()
    {
        $canuse = trim((string)Mage::getConfig()->getNode('global/crypt/use_argon2'));
        if ($canuse == 'true')
        {
            return true;
        }
        return false;
    }

    /**
     * 
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        return $this->_getCryptExtension() . ':' . parent::encrypt($data);
    }

    /**
     * 
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        $enc = 'mcrypt';
        $value = $data;
        if (substr_count($data, ':') == 1)
        {
            list($enc, $value) = explode(':', $data, 2);
        }
        return str_replace("\x0", '', trim($this->_getCryptByModel(null, $enc)->decrypt(base64_decode((string)$value))));
    }

    /**
     * mcrypt is marked as DEPRECATED since PHP 7.1
     * and removed in PHP 7.2
     * phpseclib does provide an workaround for this
     * https://github.com/phpseclib/mcrypt_compat
     * if you encounter an issue with this method
     * you have to update phpseclib to the version min. 2.0.9
     */
    protected function _loadWorkaroundForMcrypt()
    {
        if (!defined('MCRYPT_MODE_ECB')) {
            require_once 'phpseclib'.DS.'Crypt'.DS.'Base.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'Rijndael.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'Twofish.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'Blowfish.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'DES.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'TripleDES.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'RC2.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'RC4.php';
            require_once 'phpseclib'.DS.'Crypt'.DS.'Random.php';
            $dir = dirname(__DIR__);
            require_once $dir.DS.'lib'.DS.'mcrypt_compat'.DS.'lib'.DS.'mcrypt.php';
        }
    }
}
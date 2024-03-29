<?php

class Loewenstark_Crypt_Model_Encryption
extends Mage_Core_Model_Encryption
{
    
    const HASH_VERSION_SHA256 = 256;
    
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
     * model array of used Crypt Models
     * 
     * @var string
     */
    protected $_is_newer1943 = false;

    /**
     * check if const exists
     */
    public function __construct()
    {
        $this->_is_newer1943 = defined(get_class($this).'::HASH_VERSION_MD5');
    }
    
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
    public function hash($data, $version = null)
    {
        if ($this->isNewer1943() && $version == self::HASH_VERSION_MD5
        ) {
            return parent::hash($data, $version);
        }
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
        $password_verify = false;
        if(substr($hash, 0, 1) === '$')
        {
            $password_verify = true;
        }
        if ($this->isNewer1943() && !$password_verify)
        {
            return $this->validateHashByVersion($password, $hash, self::HASH_VERSION_SHA256)
                    || parent::validateHash($password, $hash);
        }
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
     * Validate hash by specified version
     *
     * added sha256
     * 
     * @param string $password
     * @param string $hash
     * @param int $version
     * @return bool
     */
    public function validateHashByVersion($password, $hash, $version = null)
    {
        if (!$this->isNewer1943())
        {
            return false;
        }
        if (is_null($version))
        {
            $version = self::HASH_VERSION_MD5;
        }
        if ($version == self::HASH_VERSION_SHA256)
        {
            $hashArr = explode(':', $hash);
            // without salt
            if (count($hashArr) == 1)
            {
                return ($this->hashBySha256($password) === $hash);
            } elseif(count($hashArr) == 2)
            {
                return ($this->hashBySha256($hashArr[1] . $password) === $hashArr[0]);
            }
        }
        return parent::validateHashByVersion($password, $hash, $version);
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
     * 
     * @return bool
     */
    public function isNewer1943()
    {
        return $this->_is_newer1943;
    }
}

<?php

class Loewenstark_Crypt_Model_Customer_Observer
extends Mage_Core_Model_Abstract
{
    /**
     * list of controller action, wich can use this method
     * 
     * @var array
     */
    protected $_beforeSaveActions = array(
        'customer_account_editpost',
        'customer_account_resetpasswordpost'
    );

    /**
     * on Customer Edit / Create in Backend
     * 
     * @mageEvent adminhtml_customer_save_after
     * @param Varien_Event_Observer $event
     */
    public function onAdminhtmlCustomerSaveAfter($event)
    {
        if (!$this->_getHelper()->canUseStrongPasswordHash())
        {
            return;
        }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $params = Mage::app()->getRequest()->getParam('account');
        $pw = trim((string)$params['new_password']);
        unset($params);
        $this->saveCustomersPassword($customer, 'new_password', $pw);
    }

    /**
     * on Customer Registration
     * 
     * @mageEvent customer_register_success
     * @param Varien_Event_Observer $event
     */
    public function onCustomerRegisterSuccess($event)
    {
        if (!$this->_getHelper()->canUseStrongPasswordHash())
        {
            return;
        }
        $customer = $event->getCustomer();
        /* @var $customer Mage_Customer_Model_Customer */
        $this->saveCustomersPassword($customer, 'password');
    }

    /**
     * 
     * @param Mage_Customer_Model_Customer $customer
     */
    public function saveCustomersPassword(Mage_Customer_Model_Customer $customer, $requestName = 'password', $password = null)
    {
        if (!$this->_getHelper()->canUseStrongPasswordHash())
        {
            return;
        }
        if ($this->_getEncryptor() instanceof Loewenstark_Crypt_Model_Encryption
                && (
                    strlen(Mage::app()->getRequest()->getParam($requestName)) >= 6
                    || (!is_null($password) && strlen($password) >= 6)
                    )
            )
        {
            $hash = $this->_getHash(Mage::app()->getRequest()->getParam($requestName));
            if (!is_null($password) && strlen($password) >= 6)
            {
                $hash = $this->_getHash($password);
            }
            $customer->setPasswordHash($hash);
            $customer->getResource()
                    ->saveAttribute($customer, 'password_hash');
        }
    }

    /**
     * Save Customer Password based on Strong Crypt (Bcrypt, Argon2, etc.)
     * 
     * @mageEvent customer_save_before
     * @param Varien_Event_Observer $event
     */
    public function onCustomerSaveBefore($event)
    {
        if (!$this->_getHelper()->canUseStrongPasswordHash())
        {
            return;
        }
        if (in_array($this->_getFullActionName(), $this->_beforeSaveActions))
        {
            $customer = $event->getCustomer();
            /* @var $customer Mage_Customer_Model_Customer */
            if ($this->_getFullActionName() == 'customer_account_resetpasswordpost')
            {
                $customer->setIsChangePassword(true);
            }
            $appRequest = Mage::app()->getRequest();
            /* @var $appRequest Mage_Core_Controller_Request_Http */
            if ($appRequest && $customer->getIsChangePassword()
                    && !strstr($customer->getPasswordHash(), '$'))
            {
                $hash = $this->_getHash(Mage::app()->getRequest()->getParam('password'));
                $customer->setPasswordHash($hash);
            }
        }
    }

    /**
     * 
     * @return Loewenstark_Crypt_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ldscrypt');
    }

    /**
     * 
     * @return Loewenstark_Crypt_Model_Encryption
     */
    protected function _getEncryptor()
    {
        return Mage::helper('core')->getEncryptor();
    }

    /**
     * 
     * @param string $password
     * @param null|int $salt
     * @return type
     */
    protected function _getHash($password, $salt = null)
    {
        return $this->_getEncryptor()
            ->getStrongHash(trim($password), !is_null($salt) ? $salt : Mage_Admin_Model_User::HASH_SALT_LENGTH);
    }

    /**
     * get full action name in lower cases
     * 
     * @return string
     */
    protected function _getFullActionName()
    {
        return strtolower(Mage::app()->getFrontController()->getAction()->getFullActionName());
    }
}

Loewenstark_Crypt
===================

Info
-----------
- added Multiple Function about encryption in Magento 1
- use SHA256 as Hash
- replacement of MCrypt with Openssl
- default Encyption Model will be MCrypt

Requirements
------------

- PHP >= 5.5.0
- Backword Compatible PHP >= 5.3.3 =< 5.4.x
- Workround for removed MCrypt in PHP 7.2 (Deprecated since PHP 7.1) - Based on [phpseclib/mcrypt_compact](https://github.com/phpseclib/mcrypt_compat/tree/1.0.2)
- Argon2 min. Req. is PHP 7.2 - fallback to BCrypt
- Added Openssl (Varien_Crypt_Openssl) Replacement for Varien_Crypt
- If you are want to encrypt or decrypt MCrypt Values, you have to update the [phpseclib](https://github.com/phpseclib/phpseclib/tree/2.0.9)

Compatibility
-------------
- Magento >= 1.7.0.2

Configuration
-------------
- to enable support for Openssl you have to add "openssl" to the app/etc/local.xml Node (global/crypt/extension)
  When you are change the "extension" the Old Values will always encrypted by the used encryption model.
- When you are want to use "Argon2", enable this also in the app/etc/local.xml (Node: "global/crypt/use_argon2", Value: true)


Support
-------
If you encounter any problems or bugs, please create an issue on [GitHub](https://github.com/Loewenstark/Loewenstark_Crypt/issues).

Contribution
------------
Any contribution to the development is highly welcome. The best possibility to provide any code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Related and Inspired Posts
--------------------------
- [OpenMage/magento-lts](https://github.com/OpenMage/magento-lts) | [If You're Typing the Word MCRYPT Into Your PHP Code, You're Doing It Wrong](https://github.com/OpenMage/magento-lts/issues/129)
- [Inchoo/Inchoo_PHP7](https://github.com/Inchoo/Inchoo_PHP7) | [mcrypt is deprecated in PHP7.1](https://github.com/Inchoo/Inchoo_PHP7/issues/98)

Developer
---------
* Mathis Klooss

Licence
-------
[Open Software License (OSL-3)](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2017 Loewenstark Digital Solutions GmbH
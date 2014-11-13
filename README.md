phpbb-onelogin
==============

phpBB SAML Authentication plugin based on OneLogin PHP SAML Toolkit.
This plugin enables your board users to log in through SAML.

Features
--------

Single sign on
Single log out
Just on time provisioning
Supports groups

Pre-requisite
-------------

Take a look on the php saml toolkit dependences:
https://github.com/onelogin/php-saml#dependences

Installation
------------

Copy the 'includes' and the 'language' folder at the base folder of phpbb.


Settings
--------




In Case of Emergency
--------------------

If you happened to be locked out of your board, don't panic.

Change the authentication method in the DB table `phpbb3_config` back to db.

UPDATE phpbb_config SET config_value = 'db' WHERE config_name = 'auth_method';

Clear phpbb sessions and caches and you should get access to your board again.
cd cache
rm *

phpbb-onelogin
==============

phpBB SAML Authentication plugin based on OneLogin PHP SAML Toolkit.

This plugin enables your board users to log in through SAML.

Features
--------

* Single sign on
* Single log out
* Just on time provisioning
* Supports groups

Pre-requisite
-------------

Take a look on the php saml toolkit dependences:
https://github.com/onelogin/php-saml#dependences

Installation
------------

Copy the 'includes' and the 'language' folder at the base folder of phpbb.


Settings
--------

In the admin interface, at the 'General' tab, in the 'Client communication' section access to 'Authentication'.

First, you need to change the authentication method to 'Onelogin_saml' in order to enable the SAML authentication.

Then go to the 'Onelogin SAML Settings' and configure your SP (the parameters are documented).


Local Login
-----------

When SAML enabled, you can always continue login through the 'alternative auth method' normally 'db' or 'ldap'
by adding to the login url the parameter &normal

For example, access:
http://example.com/phpbb/ucp.php?mode=login&sid=737ce11eb651394709cdc8735bc3abfa&normal
instead of
http://example.com/phpbb/ucp.php?mode=login&sid=737ce11eb651394709cdc8735bc3abfa


In Case of Emergency
--------------------

If you happened to be locked out of your board, don't panic.

Change the authentication method in the DB table `phpbb3_config` back to db.

UPDATE phpbb_config SET config_value = 'db' WHERE config_name = 'auth_method';

Clear phpbb sessions and caches and you should get access to your board again.
cd cache
rm *

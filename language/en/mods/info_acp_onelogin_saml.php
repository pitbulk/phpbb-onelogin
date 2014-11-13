<?php
/**
 * phpBB Onelogin SAML auth plug-in based on the Onelogin's PHP SAML Toolkit. English translation.
 *
 * @package language
 * @version $Id$
 * @copyright (c) 2010-2014 Onelogin Inc
 * @author Onelogin Inc. <support@onloginc.com>
 * @licence http://opensource.org/licenses/MIT MIT Licence
 */

if (empty($lang) || !is_array($lang)) {
    $lang = array();
}

$lang = array_merge($lang, array(
    'ONELOGIN_SAML_METADATA_LINK' => 'Go to the metadata of this SP',

    'ONELOGIN_SAML_SETTINGS_TITLE' => 'Onelogin SAML Settings',
    'ONELOGIN_SAML_IDP_SECTION_TITLE' => 'IDENTITY PROVIDER SETTINGS',
    'ONELOGIN_SAML_IDP_SECTION_DESC' => "Set here some info related to the IdP that will be connected with our phpBB. You can find this values at the Onelogin's platform in the phpBB App at the Single Sign-On tab",

    'ONELOGIN_SAML_IDP_ENTITY_TITLE' => 'IdP Entity Id * ',
    'ONELOGIN_SAML_IDP_ENTITY_DESC' => 'Identifier of the IdP entity. ("Issuer URL")',
    'ONELOGIN_SAML_IDP_SSO_TITLE' => 'Single Sign On Service Url * ',
    'ONELOGIN_SAML_IDP_SSO_DESC' => 'SSO endpoint info of the IdP. URL target of the IdP where the SP will send the Authentication Request. ("SAML 2.0 Endpoint (HTTP)")',
    'ONELOGIN_SAML_IDP_SLO_TITLE' => 'Single Log Out Service Url',
    'ONELOGIN_SAML_IDP_SLO_DESC' => 'SLO endpoint info of the IdP. URL target of the IdP where the SP will send the SLO Request. ("SLO Endpoint (HTTP)")',
    'ONELOGIN_SAML_IDP_X509CERT_TITLE' => 'X.509 Certificate',
    'ONELOGIN_SAML_IDP_X509CERT_DESC' => 'Public x509 certificate of the IdP. ("X.509 certificate")',

    'ONELOGIN_SAML_OPTIONS_SECTION_TITLE' => 'OPTIONS',
    'ONELOGIN_SAML_OPTIONS_SECTION_DESC' => 'In this section the behavior of the plugin is set.',
    'ONELOGIN_SAML_AUTOCREATE_TITLE' => 'Create user if not exists',
    'ONELOGIN_SAML_AUTOCREATE_DESC' => 'Auto-provisioning. If user not exists, phpBB will create a new user with the data provided by the IdP.
Review the Mapping section.',
    'ONELOGIN_SAML_UPDATEUSER_TITLE' => 'Update user data',
    'ONELOGIN_SAML_UPDATEUSER_DESC' => 'Auto-update. phpBB will update the account of the user with the data provided by the IdP.
Review the Mapping section.',
    'ONELOGIN_SAML_FORCELOGIN_TITLE' => 'Force SAML login',
    'ONELOGIN_SAML_FORCELOGIN_DESC' => 'Protect phpBB and force the user to authenticate at the IdP in order to access',
    'ONELOGIN_SAML_SECOND_AUTH_METHOD_TITLE' => 'Alternative auth method',
    'ONELOGIN_SAML_SECOND_AUTH_METHOD_DESC' => "When Force SAML login is disable, you can access to the login page adding a '&normal' parameter in order to log using an alternative method: db or ldap, providing the user credentials",
    'ONELOGIN_SAML_SLO_TITLE' => 'Single Log Out',
    'ONELOGIN_SAML_SLO_DESC' => 'Enable/disable Single Log Out. SLO is a complex functionality, the most common SLO implementation is based on front-channel (redirections), sometimes if the SLO workflow fails a user can be blocked in an unhandled view. If the admin does not controls the set of apps involved in the SLO process maybe is better to disable this functionality due could carry more problems than benefits.',
    'ONELOGIN_SAML_ACCOUNT_MATCHER_TITLE' => 'Match phpBB account by',
    'ONELOGIN_SAML_ACCOUNT_MATCHER_DESC' => "Select what field will be used in order to find the user account. If you select the 'email' fieldname the plugin will prevent that the user can change his mail in his profile.",
    'USERNAME' => 'username',

    'ONELOGIN_SAML_ATTR_MAPPING_SECTION_TITLE' => 'ATTRIBUTE MAPPING',
    'ONELOGIN_SAML_ATTR_MAPPING_SECTION_DESC' => "Sometimes the names of the attributes sent by the IdP not match the names used by phpBB for the user accounts. In this section we can set the mapping between IdP fields and phpBB fields. Notice that this mapping could be also set at Onelogin's IdP",
    'ONELOGIN_SAML_ATTR_MAPPING_USERNAME_TITLE' => 'Username',
    'ONELOGIN_SAML_ATTR_MAPPING_USERNAME_DESC' => '',
    'ONELOGIN_SAML_ATTR_MAPPING_MAIL_TITLE' => 'E-mail',
    'ONELOGIN_SAML_ATTR_MAPPING_MAIL_DESC' => '',
    'ONELOGIN_SAML_ATTR_MAPPING_GROUPS_TITLE' => 'Group',
    'ONELOGIN_SAML_ATTR_MAPPING_GROUPS_DESC' => '',

    'ONELOGIN_SAML_GROUP_MAPPING_SECTION_TITLE' => 'GROUP MAPPING',
    'ONELOGIN_SAML_GROUP_MAPPING_SECTION_DESC' => "The IdP can use it's own groups. Set in this section the mapping between IdP and phpBB groups. Accepts multiple valued comma separated. Example: Administrators,Guests,Registered users <br>You can map pre-defined groups or map your custom groups adding the name and its related map",
    'ONELOGIN_SAML_GROUP_MAPPING_ADMINISTRATORS_TITLE' => 'Administrators',
    'ONELOGIN_SAML_GROUP_MAPPING_BOTS_TITLE' => 'Bots',
    'ONELOGIN_SAML_GROUP_MAPPING_GLOBAL_MODERATORS_TITLE' => 'Global moderators',
    'ONELOGIN_SAML_GROUP_MAPPING_GUESTS_TITLE' => 'Guests',
    'ONELOGIN_SAML_GROUP_MAPPING_NEWLY_REGISTERED_USERS_TITLE' => 'Newly registered users',
    'ONELOGIN_SAML_GROUP_MAPPING_REGISTERED_USERS_TITLE' => 'Registered users',
    'ONELOGIN_SAML_GROUP_MAPPING_REGISTERED_COPPA_USERS_TITLE' => 'Registered COPPA users',
    'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM1_TITLE' => 'Custom group 1',
    'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM2_TITLE' => 'Custom group 2',
    'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM3_TITLE' => 'Custom group 3',
    'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM4_TITLE' => 'Custom group 4',
    'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM5_TITLE' => 'Custom group 5',

    'ONELOGIN_SAML_ADVANCED_SETTINGS_SECTION_TITLE' => 'ADVANCED SETTINGS',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SECTION_DESC' => "Handle some other parameters related to customizations and security issues.<br>If sign/encryption is enabled, then x509 cert and private key for the SP must be provided. There are 2 ways:<br>1. Store them as files named sp.key and sp.crt on the 'certs' folder of the plugin. (be sure that the folder is protected and not exposed to internet)<br>2. Store them at the database, filling the corresponding textareas. (take care of security issues)",
    'ONELOGIN_SAML_ADVANCED_SETTINGS_DEBUG_TITLE' => 'Debug Mode',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_DEBUG_DESC' => 'Enable it when your are debugging the SAML workflow. Errors and Warnigs will be showed.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_STRICT_MODE_TITLE' => 'Strict Mode',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_STRICT_MODE_DESC' => 'If Strict mode is Enabled, then phpBB will reject unsigned or unencrypted messages if it expects them signed or encrypted. Also will reject the messages if not strictly follow the SAML standard: Destination, NameId, Conditions ... are validated too.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_ENTITY_ID_TITLE' => 'Service Provider Entity Id',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_ENTITY_ID_DESC' => "Set the Entity ID for the Service Provider. If not provided, 'php-saml' will be used.",
    'ONELOGIN_SAML_ADVANCED_SETTINGS_NAMEID_ENCRYPTED_TITLE' => 'Encrypt nameID',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_NAMEID_ENCRYPTED_DESC' => 'The nameID sent by this SP will be encrypted.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_AUTHN_REQUEST_SIGNED_TITLE' => 'Sign AuthnRequest',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_AUTHN_REQUEST_SIGNED_DESC'  => 'The samlp:AuthnRequest messages sent by this SP will be signed.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_REQUEST_SIGNED_TITLE' => 'Sign LogoutRequest',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_REQUEST_SIGNED_DESC' => 'The samlp:logoutRequest messages sent by this SP will be signed.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_RESPONSE_SIGNED_TITLE' => 'Sign LogoutResponse',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_RESPONSE_SIGNED_DESC' => 'The samlp:logoutResponse messages sent by this SP will be signed.',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_MESSAGE_SIGNED_TITLE' => 'Reject Unsigned Messages',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_MESSAGE_SIGNED_DESC' => 'Reject unsigned samlp:Response, samlp:LogoutRequest and samlp:LogoutResponse received',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_SIGNED_TITLE' => 'Reject Unsigned Assertions',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_SIGNED_DESC' => 'Reject unsigned saml:Assertion received',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_ENCRYPTED_TITLE' => 'Reject Unencrypted Assertions',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_ENCRYPTED_DESC' => 'Reject unencrypted saml:Assertion received',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_X509CERT_TITLE' => 'Service Provider X.509 Certificate',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_X509CERT_DESC' => 'Public x509 certificate of the SP. Leave this field empty if you gonna provide the cert by the sp.crt',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_PRIVATEKEY_TITLE' => 'Service Provider Private Key',
    'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_PRIVATEKEY_DESC' => 'Private Key of the SP. Leave this field empty if you gonna provide the private key by the sp.key',
    'ONELOGIN_SAML_CANNOT_INCLUDE_TOOLKIT' => "Unable to load Onelogin's PHP Toolkit. Did you installed it in the right directory?",
    'ONELOGIN_SAML_CANNOT_INCLUDE_SETTINGS' => "Unable to load Onelogin's PHP Settings. Did you installed it in the right directory?",
    'ONELOGIN_SAML_IDP_ENTITY_ID_NOT_DEFINED' => 'Review the SAML settings, the IdP entityid is required',
    'ONELOGIN_SAML_IDP_SSO_URL_NOT_DEFINED' => 'Review the SAML settings, the IdP SSO URL is required',
    'ONELOGIN_SAML_NOT_CONFIGURED' => 'The Onelogin SSO/SAML plugin is not correctly configured',

    'NO_AUTHENTICATED' => 'SAML failed, user was not authenticated',
    'NO_USERNAME' => 'Login requires a username and none could be obtained from the IdP.',
    'NO_EMAIL' => 'Login requires a mail and none could be obtained from the IdP.',
    'USER_DOES_NOT_EXIST_AND_NO_DATA' => 'The user authenticated in the IdP does not exists in phpBB, tried to create an account but username or mail were not provided',
    'USER_DOES_NOT_EXIST_AND_NOT_ALLOWED_TO_CREATE' => 'The user authenticated in the IdP does not exists in phpBB and is not allowed to create an account',
    'SLO PROCESS FAILED' => 'SLO process failed'
));

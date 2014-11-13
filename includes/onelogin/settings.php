<?php
            
    $saml_settings = array (
        'strict' => array_key_exists('onelogin_saml_advanced_settings_debug', $config) && $config['onelogin_saml_advanced_settings_debug'],
        'debug' => array_key_exists('onelogin_saml_advanced_settings_strict_mode', $config) && $config['onelogin_saml_advanced_settings_strict_mode'],
        'sp' => array (
            'entityId' => (array_key_exists('onelogin_saml_advanced_settings_sp_entity_id', $config) && !empty($config['onelogin_saml_advanced_settings_sp_entity_id']) ? $config['onelogin_saml_advanced_settings_sp_entity_id'] : 'php-saml' ),
            'assertionConsumerService' => array (
                'url' => generate_board_url().'/ucp.php?mode=login',
            ),
            'singleLogoutService' => array (
                'url' => generate_board_url().'/ucp.php?mode=logout',
            ),
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:unspecified',
            'x509cert' => (array_key_exists('onelogin_saml_advanced_settings_sp_x509cert', $config) ? $config['onelogin_saml_advanced_settings_sp_x509cert'] : '' ),
            'privateKey' => (array_key_exists('onelogin_saml_advanced_settings_sp_privatekey', $config) ? $config['onelogin_saml_advanced_settings_sp_privatekey'] : '' ),
        ),

        'idp' => array (
            'entityId' => (array_key_exists('onelogin_saml_idp_entityid', $config) ? $config['onelogin_saml_idp_entityid'] : '' ),
            'singleSignOnService' => array (
                'url' => (array_key_exists('onelogin_saml_idp_sso', $config) ? $config['onelogin_saml_idp_sso'] : '' ),
            ),
            'singleLogoutService' => array (
                'url' => (array_key_exists('onelogin_saml_idp_slo', $config) ? $config['onelogin_saml_idp_slo'] : '' ),
            ),
            'x509cert' => (array_key_exists('onelogin_saml_idp_x509cert', $config) ? $config['onelogin_saml_idp_x509cert'] : '' ),
        ),

        // Security settings
        'security' => array (

            /** signatures and encryptions offered */

            // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
            // will be encrypted.
            'nameIdEncrypted' => array_key_exists('onelogin_saml_advanced_settings_nameid_encrypted', $config) && $config['onelogin_saml_advanced_settings_nameid_encrypted'],

            // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
            // will be signed.              [The Metadata of the SP will offer this info]
            'authnRequestsSigned' => array_key_exists('onelogin_saml_advanced_settings_authn_request_signed', $config) && $config['onelogin_saml_advanced_settings_authn_request_signed'],

            // Indicates whether the <samlp:logoutRequest> messages sent by this SP
            // will be signed.
            'logoutRequestSigned' => array_key_exists('onelogin_saml_advanced_settings_logout_request_signed', $config) && $config['onelogin_saml_advanced_settings_logout_request_signed'],

            // Indicates whether the <samlp:logoutResponse> messages sent by this SP
            // will be signed.
            'logoutResponseSigned' => array_key_exists('onelogin_saml_advanced_settings_logout_response_signed', $config) && $config['onelogin_saml_advanced_settings_logout_response_signed'],

            /** signatures and encryptions required **/

            // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and
            // <samlp:LogoutResponse> elements received by this SP to be signed.
            'wantMessagesSigned' => array_key_exists('onelogin_saml_advanced_settings_want_message_signed', $config) && $config['onelogin_saml_advanced_settings_want_message_signed'],

            // Indicates a requirement for the <saml:Assertion> elements received by
            // this SP to be signed.        [The Metadata of the SP will offer this info]
            'wantAssertionsSigned' => array_key_exists('onelogin_saml_advanced_settings_want_assertion_signed', $config) && $config['onelogin_saml_advanced_settings_want_assertion_signed'],

            // Indicates a requirement for the NameID received by
            // this SP to be encrypted.
            'wantNameIdEncrypted' => array_key_exists('onelogin_saml_advanced_settings_want_assertion_encrypted', $config) && $config['onelogin_saml_advanced_settings_want_assertion_encrypted'],
        ),
       
    );

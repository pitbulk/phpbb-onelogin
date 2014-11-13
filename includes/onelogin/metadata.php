<?php
    define('IN_PHPBB', true);

    $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : dirname(dirname(dirname(__FILE__))).'/';
    $phpEx = substr(strrchr(__FILE__, '.'), 1);
    include($phpbb_root_path . 'common.' . $phpEx);
    ob_end_clean();     # We eliminate buffer output (common.php includes a space)

    require_once '_toolkit_loader.php';
    require 'settings.php';
    $saml_auth = new Onelogin_Saml2_Auth($saml_settings);

    $settings = $saml_auth->getSettings();
    $metadata = $settings->getSPMetadata();
    
    header('Content-Type: text/xml');
    echo $metadata;
    exit();

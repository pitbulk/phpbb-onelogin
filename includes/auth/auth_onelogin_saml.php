<?php
/**
 * phpBB Onelogin SAML auth plug-in based on the Onelogin's PHP SAML Toolkit.
 *
 * @package login
 * @version $Id$
 * @copyright (c) 2010-2014 Onelogin Inc
 * @author Onelogin Inc. <support@onloginc.com>
 * @licence http://opensource.org/licenses/MIT MIT Licence
 *
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB')) {
    exit;
}

$username = request_var('username', '');
$password = request_var('password', '');
$normal_login = isset($_REQUEST['normal']) || (!empty($username) && !empty($password));

if (defined('IN_LOGIN') && request_var('mode', '') === 'login') {
    if (!$normal_login && !isset($_POST['SAMLResponse'])) {
        onelogin_init_sso();
    } 
    else if (isset($_POST['SAMLResponse'])) {
        $_POST['login'] = 1;
    }
}

if (defined('IN_LOGIN') && request_var('mode', '') === 'logout') {
    if (isset($_GET['SAMLRequest'])) {
        onelogin_sls();
    }
}


//function onelogin_saml(&$username, &$password)
//{
//    login_onelogin_saml($username, $password);
//}

/** Onelogin SAML init.
 *  
 */
function init_onelogin_saml()
{
    global $config, $user, $phpbb_root_path;

    $user->add_lang('mods/info_acp_onelogin_saml');

    if (!file_exists($phpbb_root_path . 'includes/onelogin/_toolkit_loader.php')) {
         return $user->lang['ONELOGIN_SAML_CANNOT_INCLUDE_TOOLKIT'];
    }

    if (!file_exists($phpbb_root_path . 'includes/onelogin/settings.php')) {
         return $user->lang['ONELOGIN_SAML_CANNOT_INCLUDE_SETTINGS'];
    }

    if (!array_key_exists('onelogin_saml_idp_entityid', $config) || empty($config['onelogin_saml_idp_entityid'])) {
         return $user->lang['ONELOGIN_SAML_IDP_ENTITY_ID_NOT_DEFINED'];
    }

    if (!array_key_exists('onelogin_saml_idp_sso', $config) || empty($config['onelogin_saml_idp_sso'])) {
         return $user->lang['ONELOGIN_SAML_IDP_SSO_URL_NOT_DEFINED'];
    }

    return false;
}

/** Autologin through SAML.
 *
 *  Init the SSO process if 'Force SAML login' is enabled
 *
 *  @return an empty array if Force SAML login is disabled.
 */
function autologin_onelogin_saml()
{
    global $config;

    if (!array_key_exists('onelogin_saml_forcelogin', $config) || !($config['onelogin_saml_forcelogin'])) {
        return array();
    }

    // Init SSO process
    onelogin_init_sso();
}

/** Login through SAML.
 *
 *  Called both when hitting the "Login" button or whenever a login_box() call occur.
 *  Both parameters are unused since everything is done through SAML.
 *
 *  @param string $username Supplied username (unused)
 *  @param string $password Supplied password (unused)
 *
 *  @return Matching user row (eventually anonymous) or an error.
 */
function login_onelogin_saml(&$username, &$password)
{
    global $config, $user, $phpbb_root_path, $phpEx;

    $user->add_lang('mods/info_acp_onelogin_saml');

    if (!empty($username) && !empty($password)) {
        if (array_key_exists('onelogin_saml_forcelogin', $config) && $config['onelogin_saml_forcelogin'] == 'ldap') {
            require_once $phpbb_root_path.'includes/auth/auth_ldap.php';
            return login_ldap($username, $password);
        } else {
            require_once $phpbb_root_path.'includes/auth/auth_db.php';
            return login_db($username, $password);
        }
    }

    $debug = array_key_exists('onelogin_saml_advanced_settings_debug', $config) ? $config['onelogin_saml_advanced_settings_debug'] : false;

    // Init SSO process
    if (!isset($_POST['SAMLResponse'])) {
        onelogin_init_sso();
        exit();
    }

    $saml_auth = onelogin_saml_instance();
    $saml_auth->processResponse();

    if (!$saml_auth->isAuthenticated()) {
        $msg_error = 'NO_AUTHENTICATED';
        $errors = $saml_auth->getErrors();
        
        if (!empty($errors) && $debug) {
            $msg_error .= '<br>'.implode(', ', $errors);
        }
        return array(
            'status' => LOGIN_ERROR_EXTERNAL_AUTH,
            'error_msg'=> $msg_error,
            'user_row'=> array('user_id' => $user->data['user_id'])
        );
    }

    $attrs = $saml_auth->getAttributes();
    $username = '';
    $email = '';

    if (empty($attrs)) {
        $username = $saml_auth->getNameId();
        $email = $username;
    } else {
        $usernameMapping = array_key_exists('onelogin_saml_attr_mapping_username', $config) ? $config['onelogin_saml_attr_mapping_username'] : null;
        $mailMapping = array_key_exists('onelogin_saml_attr_mapping_mail', $config) ? $config['onelogin_saml_attr_mapping_mail'] : null;
        if (!empty($usernameMapping) && isset($attrs[$usernameMapping]) && !empty($attrs[$usernameMapping][0])) {
            $username = $attrs[$usernameMapping][0];
        }
        if (!empty($mailMapping) && isset($attrs[$mailMapping]) && !empty($attrs[$mailMapping][0])) {
            $email = $attrs[$mailMapping][0];
        }
    }

    $matcher = array_key_exists('onelogin_saml_account_matcher', $config) ? $config['onelogin_saml_account_matcher'] : 'username';

    if (empty($username) && $matcher == 'username') {
        return array(
            'status' => LOGIN_ERROR_EXTERNAL_AUTH,
            'error_msg'=> 'NO_USERNAME',
            'user_row'=> array('user_id' => $user->data['user_id'])
        );
    }

    if (empty($email) && $matcher == 'mail') {
        return array(
            'status' => LOGIN_ERROR_EXTERNAL_AUTH,
            'error_msg'=> 'NO_MAIL',
            'user_row'=> array('user_id' => $user->data['user_id'])
        );
    }

    $user_row = onelogin_saml_user_row($matcher, $username, $email);

    if (empty($user_row)) {
        // User not found, check if could be created
        $autocreate = array_key_exists('onelogin_saml_autocreate', $config) ? $config['onelogin_saml_autocreate'] : false;

        if ($autocreate) {
            if (empty($username) && empty($email)) {
                return array(
                    'status' => LOGIN_ERROR_EXTERNAL_AUTH,
                    'error_msg'=> 'USER_DOES_NOT_EXIST_AND_NO_DATA',
                    'user_row'=> array('user_id' => $user->data['user_id'])
                );
            } else if (empty($username)) {
                $username = $email;
            }

            $groups = onelogin_saml_get_groups($attrs);
            if (empty($groups)) {
                $main_group = onelogin_saml_get_default_group();
            } else {
                $main_group = array_shift($groups);
            }

            $user_row = array(
                'username' => $username,
                'user_password' => phpbb_hash($email . rand() . $username),
                'user_email' => $email,
                'user_type'  => USER_NORMAL,
                'group_id'   => $main_group,
                'user_ip'    => $user->ip,
                'user_new'   => ($config['new_member_post_limit']) ? 1 : 0,
            );

            $user_row['user_id'] = user_add($user_row);

            foreach ($groups as $group_id) {
                group_user_add($group_id, $user_row['user_id']);
            }
            return array(
                'status' => LOGIN_SUCCESS,
                'error_msg' => false,
                'user_row' => $user_row,
            );
        } else {
            return array(
                'status' => LOGIN_ERROR_EXTERNAL_AUTH,
                'error_msg'=> 'USER_DOES_NOT_EXIST_AND_NOT_ALLOWED_TO_CREATE',
                'user_row'=> array('user_id' => $user->data['user_id'])
            );
        }
    } else {
        // User found, check if data should be update
        $autoupdate = array_key_exists('onelogin_saml_updateuser', $config) ? $config['onelogin_saml_updateuser'] : false;

        if ($autoupdate) {
            if ($matcher != 'mail' && !empty($email)) {
                onelogin_saml_update_mail($user_row['user_id'], $email);
            }
            $user_groups = onelogin_saml_get_user_groups($user_row['user_id']);
            $groups = onelogin_saml_get_groups($attrs);

            if (empty($groups)) {
                $groups[] = onelogin_saml_get_default_group();
            }

            $to_add = array_diff($groups, $user_groups);
            $to_delete = array_diff($user_groups, $groups);

            foreach ($to_add as $group_id) {
                group_user_add($group_id, $user_row['user_id']);
            }

            foreach ($to_delete as $group_id) {
                group_user_del($group_id, $user_row['user_id']);
            }
        }

        if ($user_row['user_type'] == USER_INACTIVE || $user_row['user_type'] == USER_IGNORE) {
            return array(
                'status' => LOGIN_ERROR_ACTIVE,
                'error_msg' => 'ACTIVE_ERROR',
                'user_row' => $user_row,
            );
        }

        return array(
            'status' => LOGIN_SUCCESS,
            'error_msg' => false,
            'user_row' => $user_row,
        );
    }
}

/** Single Logout.
 *
 *  Logout the user from SAML and bring him back to the board index.
 *
 *  @param array $user_row The user row (unused)
 *  @param array $new_session His/Her new session (unused)
 */
function logout_onelogin_saml($user_row, $new_session)
{   global $config, $user;

    $normal_logout = isset($_GET['normal']);
    $slo_active = $debug = array_key_exists('onelogin_saml_slo', $config) ? $config['onelogin_saml_slo'] : false;

    if ($slo_active && !$normal_logout) {
        if (isset($_GET['SAMLRequest']) || isset($_GET['SAMLResponse'])) {
            onelogin_sls();
        } else {
            onelogin_init_slo();
        }
    }
}


/** Generate fields in ACP for SAML.
 *
 *  @param array $new The configuration variables.
 *
 *  @return array The updated template.
 */
function acp_onelogin_saml(&$new)
{
    global $user;

    $tpl = '<hr>';

    $tpl .=  '<p style="float:right"><a target="_blank" href="'.generate_board_url().'/includes/onelogin/metadata.php">'.$user->lang['ONELOGIN_SAML_METADATA_LINK'].'</a></p>';

    $tpl .= '<h3 style="margin-top: 0px;padding-bottom:5px;">'.$user->lang['ONELOGIN_SAML_SETTINGS_TITLE'].'</h3>'
        .'<b>'.$user->lang['ONELOGIN_SAML_IDP_SECTION_TITLE'].'</b>'
        . '<p>'.$user->lang['ONELOGIN_SAML_IDP_SECTION_DESC'].'</p>'
        . onelogin_saml_acp_line('onelogin_saml_idp_entityid', 'ONELOGIN_SAML_IDP_ENTITY_TITLE', 'ONELOGIN_SAML_IDP_ENTITY_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_idp_sso', 'ONELOGIN_SAML_IDP_SSO_TITLE', 'ONELOGIN_SAML_IDP_SSO_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_idp_slo', 'ONELOGIN_SAML_IDP_SLO_TITLE', 'ONELOGIN_SAML_IDP_SLO_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_idp_x509cert', 'ONELOGIN_SAML_IDP_X509CERT_TITLE', 'ONELOGIN_SAML_IDP_X509CERT_DESC', $new, 'textarea')

        .'<b>'.$user->lang['ONELOGIN_SAML_OPTIONS_SECTION_TITLE'].'</b>'
        . '<p>'.$user->lang['ONELOGIN_SAML_OPTIONS_SECTION_DESC'].'</p>'
        . onelogin_saml_acp_line('onelogin_saml_autocreate', 'ONELOGIN_SAML_AUTOCREATE_TITLE', 'ONELOGIN_SAML_AUTOCREATE_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_updateuser', 'ONELOGIN_SAML_UPDATEUSER_TITLE', 'ONELOGIN_SAML_UPDATEUSER_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_forcelogin', 'ONELOGIN_SAML_FORCELOGIN_TITLE', 'ONELOGIN_SAML_FORCELOGIN_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_second_auth_method', 'ONELOGIN_SAML_SECOND_AUTH_METHOD_TITLE', 'ONELOGIN_SAML_SECOND_AUTH_METHOD_DESC', $new, 'select', array('db' => ucfirst('db'), 'ldap' => 'ldap'))
        . onelogin_saml_acp_line('onelogin_saml_slo', 'ONELOGIN_SAML_SLO_TITLE', 'ONELOGIN_SAML_SLO_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_account_matcher', 'ONELOGIN_SAML_ACCOUNT_MATCHER_TITLE', 'ONELOGIN_SAML_ACCOUNT_MATCHER_DESC', $new, 'select', array('username' => ucfirst($user->lang['USERNAME']), 'mail' => ucfirst($user->lang['EMAIL']) ))

        .'<b>'.$user->lang['ONELOGIN_SAML_ATTR_MAPPING_SECTION_TITLE'].'</b>'
        . '<p>'.$user->lang['ONELOGIN_SAML_ATTR_MAPPING_SECTION_DESC'].'</p>'
        . onelogin_saml_acp_line('onelogin_saml_attr_mapping_username', 'ONELOGIN_SAML_ATTR_MAPPING_USERNAME_TITLE', 'ONELOGIN_SAML_ATTR_MAPPING_USERNAME_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_attr_mapping_mail', 'ONELOGIN_SAML_ATTR_MAPPING_MAIL_TITLE', 'ONELOGIN_SAML_ATTR_MAPPING_MAIL_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_attr_mapping_groups', 'ONELOGIN_SAML_ATTR_MAPPING_GROUPS_TITLE', 'ONELOGIN_SAML_ATTR_MAPPING_GROUPS_DESC', $new)

        .'<b>'.$user->lang['ONELOGIN_SAML_GROUP_MAPPING_SECTION_TITLE'].'</b>'
        . '<p>'.$user->lang['ONELOGIN_SAML_GROUP_MAPPING_SECTION_DESC'].'</p>'
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_administrators', 'ONELOGIN_SAML_GROUP_MAPPING_ADMINISTRATORS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_bots', 'ONELOGIN_SAML_GROUP_MAPPING_BOTS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_global_moderators', 'ONELOGIN_SAML_GROUP_MAPPING_GLOBAL_MODERATORS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_guests', 'ONELOGIN_SAML_GROUP_MAPPING_GUESTS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_newly_registered_users', 'ONELOGIN_SAML_GROUP_MAPPING_NEWLY_REGISTERED_USERS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_registered_users', 'ONELOGIN_SAML_GROUP_MAPPING_REGISTERED_USERS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_registered_coppa_users', 'ONELOGIN_SAML_GROUP_MAPPING_REGISTERED_COPPA_USERS_TITLE', '', $new)
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_custom1', 'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM1_TITLE', '', $new, 'special')
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_custom2', 'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM2_TITLE', '', $new, 'special')
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_custom3', 'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM3_TITLE', '', $new, 'special')
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_custom4', 'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM4_TITLE', '', $new, 'special')
        . onelogin_saml_acp_line('onelogin_saml_group_mapping_custom5', 'ONELOGIN_SAML_GROUP_MAPPING_CUSTOM5_TITLE', '', $new, 'special')

        .'<b>'.$user->lang['ONELOGIN_SAML_ADVANCED_SETTINGS_SECTION_TITLE'].'</b>'
        . '<p>'.$user->lang['ONELOGIN_SAML_ADVANCED_SETTINGS_SECTION_DESC'].'</p>'
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_debug', 'ONELOGIN_SAML_ADVANCED_SETTINGS_DEBUG_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_DEBUG_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_strict_mode', 'ONELOGIN_SAML_ADVANCED_SETTINGS_STRICT_MODE_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_STRICT_MODE_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_sp_entity_id', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_ENTITY_ID_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_ENTITY_ID_DESC', $new)
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_nameid_encrypted', 'ONELOGIN_SAML_ADVANCED_SETTINGS_NAMEID_ENCRYPTED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_NAMEID_ENCRYPTED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_authn_request_signed', 'ONELOGIN_SAML_ADVANCED_SETTINGS_AUTHN_REQUEST_SIGNED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_AUTHN_REQUEST_SIGNED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_logout_request_signed', 'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_REQUEST_SIGNED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_REQUEST_SIGNED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_logout_response_signed', 'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_RESPONSE_SIGNED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_LOGOUT_RESPONSE_SIGNED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_want_message_signed', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_MESSAGE_SIGNED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_MESSAGE_SIGNED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_want_assertion_signed', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_SIGNED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_SIGNED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_want_assertion_encrypted', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_ENCRYPTED_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_WANT_ASSERTION_ENCRYPTED_DESC', $new, 'radio')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_sp_x509cert', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_X509CERT_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_X509CERT_DESC', $new, 'textarea')
        . onelogin_saml_acp_line('onelogin_saml_advanced_settings_sp_privatekey', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_PRIVATEKEY_TITLE', 'ONELOGIN_SAML_ADVANCED_SETTINGS_SP_PRIVATEKEY_DESC', $new, 'textarea')
        .'<hr>';

    return array(
        'tpl'       => $tpl,
        'config'    => array(
            'onelogin_saml_idp_entityid',
            'onelogin_saml_idp_sso',
            'onelogin_saml_idp_slo',
            'onelogin_saml_idp_x509cert',
            'onelogin_saml_autocreate',
            'onelogin_saml_updateuser',
            'onelogin_saml_slo',
            'onelogin_saml_account_matcher',
            'onelogin_saml_attr_mapping_username',
            'onelogin_saml_attr_mapping_mail',
            'onelogin_saml_attr_mapping_groups',
            'onelogin_saml_group_mapping_administrators',
            'onelogin_saml_group_mapping_bots',
            'onelogin_saml_group_mapping_global_moderators',
            'onelogin_saml_group_mapping_guests',
            'onelogin_saml_group_mapping_newly_registered_users',
            'onelogin_saml_group_mapping_registered_users',
            'onelogin_saml_group_mapping_registered_coppa_users',
            'onelogin_saml_group_mapping_custom1',
            'onelogin_saml_group_mapping_custom1_name',
            'onelogin_saml_group_mapping_custom2',
            'onelogin_saml_group_mapping_custom2_name',
            'onelogin_saml_group_mapping_custom3',
            'onelogin_saml_group_mapping_custom3_name',
            'onelogin_saml_group_mapping_custom4',
            'onelogin_saml_group_mapping_custom4_name',
            'onelogin_saml_group_mapping_custom4',
            'onelogin_saml_group_mapping_custom5_name',
            'onelogin_saml_advanced_settings_debug',
            'onelogin_saml_advanced_settings_strict_mode',
            'onelogin_saml_advanced_settings_sp_entity_id',
            'onelogin_saml_advanced_settings_nameid_encrypted',
            'onelogin_saml_advanced_settings_authn_request_signed',
            'onelogin_saml_advanced_settings_logout_request_signed',
            'onelogin_saml_advanced_settings_logout_response_signed',
            'onelogin_saml_advanced_settings_want_message_signed',
            'onelogin_saml_advanced_settings_want_assertion_signed',
            'onelogin_saml_advanced_settings_want_assertion_encrypted',
            'onelogin_saml_advanced_settings_sp_x509cert',
            'onelogin_saml_advanced_settings_sp_privatekey',
        ),
    );
}

/** HTML generation for a configuration variable.
 *
 *  Generates the HTML code for a configuration variable.
 *
 *  @param string   $name       Parameter name.
 *  @param string   $short_desc Short description of the parameter.
 *  @param string   $long_desc  Long description of the parameter.
 *  @param array    $new        Configuration array.
 *
 *  @return string The HTML code.
 */
function onelogin_saml_acp_line($name, $short_desc, $long_desc, &$new, $type = "text", $options = array())
{
    global $user;

    switch($type) {
        case "textarea":
            $html = '
                <dl>
                    <dt><label for="' . $name . '">' . $user->lang[$short_desc] . ':</label><br/><span>' . (isset($user->lang[$long_desc])? $user->lang[$long_desc] : '') . '</span></dt>
                    <dd><textarea id="' . $name . '" name="config[' . $name . ']" rows="10" cols="40">'.(isset($new[$name])? $new[$name] : '').'</textarea></dd>
                </dl>
                ';
            break;
        case "radio":
            $html = '
                <dl>
                    <dt><label for="' . $name . '">' . $user->lang[$short_desc] . ':</label><br/><span>' . (isset($user->lang[$long_desc])? $user->lang[$long_desc] : '') . '</span></dt>
                    <dd><label><input type="radio" id="' . $name . '" name="config[' . $name . ']" value="1" '.(isset($new[$name]) && $new[$name] == 1 ? 'checked="checked': '').' " class="radio"> ' . $user->lang['YES'] . '</label><label><input type="radio" name="config[' . $name . ']" value="0" '.(!isset($new[$name]) || $new[$name] == 0 ? 'checked="checked' : '').' class="radio"> ' . $user->lang['NO'] . '</label></dd>
                </dl>
                ';
            break;
        case "select":
            $html = '
                <dl>
                    <dt><label for="' . $name . '">' . $user->lang[$short_desc] . ':</label><br/><span>' . (isset($user->lang[$long_desc])? $user->lang[$long_desc] : '') . '</span></dt>
                    <dd><select id="' . $name . '" name="config[' . $name . ']" >';
            foreach ($options as $index => $value) {
                $html .= '<option value ="'.$index.'" '.(isset($new[$name]) && $value == $new[$name]? 'selected="selected"' : '').' >'.$value.'</option>';
            }

            $html .= '</select></dd>
                </dl>
                ';
            break;
        case "special":
            $html = '
                <dl>
                    <dt><label for="' . $name . '">' . $user->lang[$short_desc] . ':</label><br/><span>' . (isset($user->lang[$long_desc])? $user->lang[$long_desc] : '') . '</span></dt>
                    <dd>
                        <input type="text" id="' . $name .'_name'. '" size="40" name="config[' . $name . '_name]" value="' . (isset($new[$name.'_name'])? $new[$name.'_name'] : '') . '" />
                        <input type="text" id="' . $name . '" size="40" name="config[' . $name . ']" value="' . (isset($new[$name])? $new[$name] : '') . '" />
                    </dd>
                </dl>
                ';
            break;
        case "text":
        default:
            $html = '
                <dl>
                    <dt><label for="' . $name . '">' . $user->lang[$short_desc] . ':</label><br/><span>' . (isset($user->lang[$long_desc])? $user->lang[$long_desc] : '') . '</span></dt>
                    <dd><input type="text" id="' . $name . '" size="60" name="config[' . $name . ']" value="' . (isset($new[$name])? $new[$name] : '') . '" /></dd>
                </dl>
                ';
            break;
    }

    return $html;
}

/** Get the user row.
 *
 *  Reads the user row from the database. If none is found, then returns the $default_row.
 *
 *  @param string $matcher Selected field to identify the user.
 *  @param string $username Username.
 *  @param string $mail Email.
 *  @param array $default_row The default row in case no user is found.
 *  @param bool $select_all Whether to retrieve all fields or just a specific subset.
 *
 *  @return array The user row or $default_row if the user does not exists in phpBB.
 */
function onelogin_saml_user_row($matcher, $username, $mail, $default_row = array(), $select_all = true)
{
    global $db;

    $user_row = $default_row;

    if ($matcher == 'username') {
        $matcher = 'username_clean';
        $value = $db->sql_escape(utf8_clean_string($username));
    } else {
        $value = $mail;
    }

    $sql = 'SELECT';
    if ($select_all) {
        $sql .= ' *';
    }
    $sql .= ' FROM ' . USERS_TABLE . " WHERE ".$matcher." = '" . $value . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);

    if ($row) {
        $user_row = $row;
    }

    return $user_row;
}

function onelogin_saml_get_groups($attrs)
{
    global $db, $config;

    $groups_ids = array();

    $groupMapping = array_key_exists('onelogin_saml_attr_mapping_groups', $config) ? $config['onelogin_saml_attr_mapping_groups'] : null;
    if (!empty($groupMapping) && isset($attrs[$groupMapping]) && !empty($attrs[$groupMapping])) {
        $user_saml_groups = $attrs[$groupMapping];

        $administrators_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_administrators', $config) ? $config['onelogin_saml_group_mapping_administrators'] : '');
        $bots_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_bots', $config) ? $config['onelogin_saml_group_mapping_administrators'] : '');
        $global_moderators_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_global_moderators', $config) ? $config['onelogin_saml_group_mapping_global_moderators'] : '');
        $guests_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_guests', $config) ? $config['onelogin_saml_group_mapping_guests'] : '');
        $newly_registered_users_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_newly_registered_users', $config) ? $config['onelogin_saml_group_mapping_newly_registered_users'] : '');
        $registered_users_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_registered_users', $config) ? $config['onelogin_saml_group_mapping_registered_users'] : '');
        $registered_coppa_users_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_registered_coppa_users', $config) ? $config['onelogin_saml_group_mapping_registered_coppa_users'] : '');
        $custom1_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_custom1', $config) ? $config['onelogin_saml_group_mapping_custom1'] : '');
        $custom2_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_custom2', $config) ? $config['onelogin_saml_group_mapping_custom2'] : '');
        $custom3_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_custom3', $config) ? $config['onelogin_saml_group_mapping_custom3'] : '');
        $custom4_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_custom4', $config) ? $config['onelogin_saml_group_mapping_custom4'] : '');
        $custom5_mapping = explode(',', array_key_exists('onelogin_saml_group_mapping_custom5', $config) ? $config['onelogin_saml_group_mapping_custom5'] : '');

        $custom1_name = array_key_exists('onelogin_saml_group_mapping_custom1_name', $config) ? $config['onelogin_saml_group_mapping_custom1_name'] : '';
        $custom2_name = array_key_exists('onelogin_saml_group_mapping_custom2_name', $config) ? $config['onelogin_saml_group_mapping_custom2_name'] : '';
        $custom3_name = array_key_exists('onelogin_saml_group_mapping_custom3_name', $config) ? $config['onelogin_saml_group_mapping_custom3_name'] : '';
        $custom4_name = array_key_exists('onelogin_saml_group_mapping_custom4_name', $config) ? $config['onelogin_saml_group_mapping_custom4_name'] : '';
        $custom5_name = array_key_exists('onelogin_saml_group_mapping_custom5_name', $config) ? $config['onelogin_saml_group_mapping_custom5_name'] : '';

        $db_groups = onelogin_saml_get_db_groups();

        foreach ($user_saml_groups as $user_saml_group) {
            $user_saml_group = trim($user_saml_group);
            if (empty($user_saml_group)) {
                break;
            } else if (in_array($user_saml_group, $administrators_mapping)) {
                $groups_ids[] = $db_groups['ADMINISTRATORS'];
            } else if (in_array($user_saml_group, $bots_mapping)) {
                $groups_ids[] = $db_groups['BOTS'];
            } else if (in_array($user_saml_group, $global_moderators_mapping)) {
                $groups_ids[] = $db_groups['GLOBAL_MODERATORS'];
            } else if (in_array($user_saml_group, $guests_mapping)) {
                $groups_ids[] = $db_groups['GUESTS'];
            } else if (in_array($user_saml_group, $newly_registered_users_mapping)) {
                $groups_ids[] = $db_groups['NEWLY_REGISTERED'];
            } else if (in_array($user_saml_group, $registered_users_mapping)) {
                $groups_ids[] = $db_groups['REGISTERED'];
            } else if (in_array($user_saml_group, $registered_coppa_users_mapping)) {
                $groups_ids[] = $db_groups['REGISTERED_COPPA'];
            } else if (in_array($user_saml_group, $custom1_mapping) && array_key_exists($custom1_name, $db_groups)) {
                $group_id = onelogin_saml_get_db_group_by_name($custom1_name);
                if ($group_id) {
                    $groups_ids[] = $group_id;
                }
            } else if (in_array($user_saml_group, $custom2_mapping) && array_key_exists($custom2_name, $db_groups)) {
                $group_id = onelogin_saml_get_db_group_by_name($custom2_name);
                if ($group_id) {
                    $groups_ids[] = $group_id;
                }
            } else if (in_array($user_saml_group, $custom3_mapping) && array_key_exists($custom3_name, $db_groups)) {
                $group_id = onelogin_saml_get_db_group_by_name($custom3_name);
                if ($group_id) {
                    $groups_ids[] = $group_id;
                }
            } else if (in_array($user_saml_group, $custom4_mapping) && array_key_exists($custom4_name, $db_groups)) {
                $group_id = onelogin_saml_get_db_group_by_name($custom4_name);
                if ($group_id) {
                    $groups_ids[] = $group_id;
                }
            } else if (in_array($user_saml_group, $custom5_mapping) && array_key_exists($custom5_name, $db_groups)) {
                $group_id = onelogin_saml_get_db_group_by_name($custom5_name);
                if ($group_id) {
                    $groups_ids[] = $group_id;
                }
            }
        }
    }
    return array_unique($groups_ids);
}

function onelogin_saml_get_db_groups()
{
    global $db;

    $groups = array();

    $sql = 'SELECT group_id, group_name
            FROM ' . GROUPS_TABLE;
    $result = $db->sql_query($sql);

    while ($row = $db->sql_fetchrow($result)) {
        $groups[$row['group_name']] = (int) $row['group_id'];
    }
    $db->sql_freeresult($result);

    return $groups;
}

function onelogin_saml_get_default_group()
{
    global $db;

    $sql = 'SELECT group_id
            FROM ' . GROUPS_TABLE . "
            WHERE group_name = '" . $db->sql_escape('REGISTERED') . "'
            AND group_type = " . GROUP_SPECIAL;
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if (!$row) {
        trigger_error('NO_GROUP', E_USER_ERROR);
    }

    return (int) $row['group_id'];
}


function onelogin_saml_get_user_groups($user_id)
{
    global $db;

    $groups = array();

    $sql = 'SELECT group_id
            FROM ' . USER_GROUP_TABLE . "
            WHERE user_id = " . $db->sql_escape($user_id) . " AND user_pending = 0";
    $result = $db->sql_query($sql);

    while ($row = $db->sql_fetchrow($result)) {
        $groups[] = $row['group_id'];
    }
    $db->sql_freeresult($result);

    return $groups;
}

function onelogin_saml_get_db_group_by_name($group_name) {
    global $db;

    $sql = 'SELECT group_id
            FROM ' . GROUPS_TABLE . "
            WHERE group_name = '".$group_name."'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if (!empty($row)) {
        return $row['group_id'];
    } else {
        return false;
    }
}

function onelogin_saml_update_mail($user_id, $email)
{
    global $db;

    $sql = 'UPDATE '. USERS_TABLE . "
            SET user_email = '" . $db->sql_escape($email) . "'
            , user_email_hash = '" . phpbb_email_hash($email) . "'
            WHERE user_id = '" . $db->sql_escape($user_id) . "'";
    $db->sql_query($sql);

    add_log('user', $user_id, 'LOG_USER_UPDATE_EMAIL', $email);
}


/* Shared Onelogin_Saml2_Auth instance in the module.
 *
 *  @return Onelogin_Saml2_Auth instance if not error found.
 */
function onelogin_saml_instance()
{
    global $config, $phpbb_root_path;
    
    $error = init_onelogin_saml();
    if ($error !== false) {
        trigger_error($error, E_USER_ERROR);
        exit();
    }

    require_once $phpbb_root_path . 'includes/onelogin/_toolkit_loader.php';
    require $phpbb_root_path . 'includes/onelogin/settings.php';

    try {
        $saml_auth = new Onelogin_Saml2_Auth($saml_settings);
    } catch (Exception $e) {
        trigger_error('ONELOGIN_SAML_NOT_CONFIGURED'.':<br>'.$e->getMessage(), E_USER_ERROR);
        exit();
    }

    return $saml_auth;
}

/** 
 *  SSO SP-initiated
 */
function onelogin_init_sso()
{
    $saml_auth = onelogin_saml_instance();
    $saml_auth->login();
}

/** 
 *  SLO SP-initiated
 */
function onelogin_init_slo()
{
    $saml_auth = onelogin_saml_instance();
    $saml_auth->logout();
}


/** 
 *  Single Logout Service. Process Logout Request and Logout Response
 */
function onelogin_sls()
{
    global $user;

    $saml_auth = onelogin_saml_instance();
    $saml_auth->processSLO();
    
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        trigger_error('SLO PROCESS FAILED'.implode(', ', $errors), E_USER_ERROR);
    }
}

/** Authenticate or redirect.
 *
 *  Requires SAML authentication or redirects the user to the SAML login page.
 *  The ReturnTo parameter is set to the 'redirect' value or the current user page.
 */
function onelogin_saml_auth_or_redirect()
{
    global $user, $phpEx;
    $returnTo = generate_board_url() . '/';
    $returnTo .= request_var('redirect', $user->page['page']);
    onelogin_saml_instance()->requireAuth(array(
        'ReturnTo' => $returnTo,
    ));
}

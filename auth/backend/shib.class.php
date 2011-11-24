<?php

/*
 * Shibboleth authentization backend
 *
 * @author		Ivan Novakov <ivan.novakov@debug.cz>
 * @license		GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @copyright	Ivan Novakov 2009-02-23
 * @version		0.5.0
 *
 */

define('DOKU_AUTH', dirname(__FILE__));
require_once (DOKU_AUTH . '/basic.class.php');

define('DEF_SHIB_HANDLEBASE', '/Shibboleth.sso');


class auth_shib extends auth_basic
{
    
    var $_options = array();
    var $_userInfo = array();


    function auth_shib ()
    {
        $this->cando['external'] = true;
        $this->cando['logoff'] = true;
        
        $this->_init();
    }


    function _init ()
    {
        global $conf;
        
        $defaults = array(
            
            'shibboleth_base_handle' => DEF_SHIB_HANDLEBASE, 
            'lazy_sessions' => false, 
            'logout_redirect' => DEF_SHIB_HANDLEBASE . '/Logout?return=' . $_SERVER['HTTP_REFERER'], 
            'var_remote_user' => 'REMOTE_USER', 
            'var_name' => '', 
            'var_mail' => '', 
            'var_entitlement' => '', 
            'var_groups' => '', 
            'tpl_user_name' => '', 
            'superusers' => array(), 
            'defaultgroup' => $conf['defaultgroup'], 
            'admingroup' => 'admin', 
            'customgroups' => false, 
            'customgroups_file' => DOKU_CONF . 'custom_groups.php', 
            'entitlement_groups' => array(), 
            'debug' => false
        );
        
        $this->_options = (array) $conf['auth']['shib'] + $defaults;
    }


    function _getOption ($optionName)
    {
        if (isset($this->_options[$optionName])) {
            return $this->_options[$optionName];
        }
        return NULL;
    }


    function logOff ()
    {
        # Redirect to central logout
        $redirectURL = $this->_getOption('logout_redirect');
        header("Location: {$redirectURL}");
    }


    function trustExternal ()
    {
        return $this->_authenticate();
    }


    function _authenticate ()
    {
        
        $remoteUser = $this->_getShibVar($this->_getOption('var_remote_user'));
        if ($remoteUser) {
            
            $userId = $remoteUser;
            $userName = $this->_getUserName($userId);
            
            $this->_userInfo = array(
                
            'uid' => $userId, 'name' => $userName, 'mail' => ''
            );
            
            $mails = $this->_getShibVar($this->_getOption('var_mail'), true);
            if (count($mails)) {
                $this->_userInfo['mail'] = $mails[0];
            }
            
            if (NULL !== $this->_getOption('defaultgroup')) {
                $this->_addUserGroup($this->_getOption('defaultgroup'));
            }
            
            if ((NULL !== $this->_getOption('superusers')) && is_array($this->_getOption('superusers')) && in_array($userId, $this->_getOption('superusers'))) {
                $this->_addUserGroup($this->_getOption('admingroup'));
            }
            
            $this->_setGroups();
            $this->_setCustomGroups($userId);
            $this->_setEntitlementGroups();
            
            $this->_saveUserInfo();
            
            return true;
        }
        
        if (! $this->_getOption('lazy_sessions')) {
            auth_logoff();
        }
        
        return false;
    }


    function _getUserName ($userId)
    {
        if (($tplUserName = $this->_getOption('tpl_user_name'))) {
            if (($userName = $this->_getUserNameFromTpl($tplUserName))) {
                return $userName;
            }
        }
        
        if (($varName = $this->_getOption('var_name'))) {
            $userName = $this->_getShibVar($varName);
            if ($userName) {
                return $userName;
            }
        }
        
        return $userId;
    }


    function _getUserNameFromTpl ($tplUserName)
    {
        $matches = array();
        if (preg_match_all('/({([^{}]+)})/', $tplUserName, $matches)) {
            $vars = $matches[2];
            
            $userName = $tplUserName;
            foreach ($vars as $var) {
                $value = $this->_getShibVar($var);
                if (! $value) {
                    return '';
                }
                $userName = str_replace('{' . $var . '}', $value, $userName);
            }
            
            return $userName;
        }
        
        return '';
    }


    function _setGroups ()
    {
        $varGroups = $this->_getOption('var_groups');
        if (! $varGroups) {
            return;
        }
        
        $groups = $this->_getShibVar($varGroups, true);
        foreach ($groups as $groupName) {
            $this->_addUserGroup($groupName);
            //$this->_userInfo['grps'][] = trim($group);
        }
    }


    function _setCustomGroups ($userId)
    {
        if (! $this->_getOption('customgroups')) {
            return;
        }
        
        $groupsFile = $this->_getOption('customgroups_file');
        if (! file_exists($groupsFile)) {
            $this->_log(sprintf("Non-existent custom groups file '%s'.", $groupsFile));
            return;
        }
        
        $customGroups = array();
        @include $groupsFile;
        
        if (! isset($customGroups)) {
            $this->_log('Custom groups variable not found.');
            return;
        }
        
        if (! is_array($customGroups) || empty($customGroups)) {
            $this->_log('No custom groups specified.');
            return;
        }
        
        foreach ($customGroups as $groupName => $groupMembers) {
            if (! is_array($groupMembers) || empty($groupMembers)) {
                continue;
            }
            
            if (in_array($userId, $groupMembers)) {
                $this->_addUserGroup($groupName);
            }
        }
    
    }


    function _setEntitlementGroups ()
    {
        $entVarName = $this->_getOption('var_entitlement');
        if (! $entVarName) {
            $this->_log('entitlement variable name not set');
            return;
        }
        
        $entitlement = $this->_getShibVar($entVarName, true);
        if (! $entitlement) {
            $this->_log('no entitlement values set');
            return;
        }
        
        $entGroups = $this->_getOption('entitlement_groups');
        if (! $entGroups || ! is_array($entGroups)) {
            $this->_log('entitlement groups not configured');
            return;
        }
        
        foreach ($entitlement as $entVal) {
            if (isset($entGroups[$entVal])) {
                $this->_addUserGroup($entGroups[$entVal]);
            }
        }
    
    }


    function _addUserGroup ($groupName)
    {
        if (! isset($this->_userInfo['grps'])) {
            $this->_userInfo['grps'] = array();
        }
        $this->_userInfo['grps'][] = trim($groupName);
    }


    function _saveUserInfo ()
    {
        global $USERINFO;
        
        $USERINFO = $this->_userInfo;
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $USERINFO['uid'];
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
        
        // Despite setting the user into the session, DokuWiki still uses hard-coded REMOTE_USER variable    
        if ($this->_getOption('var_remote_user') != 'REMOTE_USER') {
            $_SERVER['REMOTE_USER'] = $USERINFO['uid'];
        }
        
        $this->_log($this->_userInfo);
    }


    function _getShibVar ($varName, $multivalue = false)
    {
        if (! isset($_SERVER[$varName])) {
            return NULL;
        }
        
        if (! $multivalue) {
            return $_SERVER[$varName];
        }
        
        $values = explode(';', $_SERVER[$varName]);
        return $values;
    }


    function _log ($value)
    {
        if ($this->_getOption('debug')) {
            error_log(print_r($value, true));
        }
    }

}

?>
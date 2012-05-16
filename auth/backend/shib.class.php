<?php

/**
 * Shibboleth authentization backend.
 *
 * @author		Ivan Novakov <ivan.novakov@debug.cz>
 * @license		FreeBSD http://debug.cz/license/freebsd
 * @version		0.6.1
 */

define('DOKU_AUTH', dirname(__FILE__));
require_once (DOKU_AUTH . '/basic.class.php');


class auth_shib extends auth_basic
{

    /**
     * Default Shibboleth base handler location.
     * 
     * @var string
     */
    protected $_defShibHandleBase = '/Shibboleth.sso';

    /**
     * Internal options array.
     * 
     * @var array
     */
    protected $_options = array();

    /**
     * Internal user data array.
     * 
     * @var array
     */
    protected $_userInfo = array();


    /**
     * Constructor.
     */
    public function __construct ()
    {
        $this->cando['external'] = true;
        $this->cando['logoff'] = true;
        
        $this->_init();
    }


    /**
     * Internal initializations.
     */
    protected function _init ()
    {
        global $conf;
        
        $defaults = array(
            // OBSOLETE
            'shibboleth_base_handle' => $this->_defShibHandleBase, 
            
            'lazy_sessions' => false, 
            'use_dokuwiki_session' => false, 
            'logout_redirect' => '', 
            'logout_return_url' => '', 
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
            'debug' => false, 
            'debug_encode_nonscalar' => false, 
            
            // not implemented
            'log_file' => '', 
            'log_enabled' => false
        );
        
        $this->_options = (array) $conf['auth']['shib'] + $defaults;
    }


    /**
     * Returns an option value by its name. Returns NULL, if option not set.
     * 
     * @param string $optionName
     * @return mixed
     */
    protected function _getOption ($optionName)
    {
        if (isset($this->_options[$optionName])) {
            return $this->_options[$optionName];
        }
        return NULL;
    }


    /**
     * logOff() implementation.
     * 
     * The 'logout_redirect' value should be an URL, where the current Shibboleth session is ended - either using
     * the '/Logout' Shibboleth handler, or some custom cookie management.
     * 
     * @see auth_basic::logOff()
     */
    public function logOff ()
    {
        $url = $this->_getOption('logout_redirect');
        if (! $url) {
            $url = $this->_getLogoutUrl($this->_getOption('logout_return_url'));
        }
        
        $this->_debugLog(sprintf("Logout redirect: %s", $url));
        
        header('Location: ' . $url);
        exit();
    }


    /**
     * trustExternal() implementation.
     * 
     * @see auth_basic::trustExternal()
     * @return boolean
     */
    public function trustExternal ()
    {
        if ($this->_authenticate()) {
            $this->_setGlobalUserInfo();
            return true;
        }
        
        return false;
    }


    /**
     * Tries to authenticate the user.
     * 
     * Checks the environment variables and sets the user identity with the appropriate attributes. Sets user's groups.
     * Returns true, if successful.
     * 
     * @return boolean
     */
    protected function _authenticate ()
    {
        if ($this->_getOption('use_dokuwiki_session') && ($userInfo = $this->_loadUserInfo())) {
            $this->_debugLog(sprintf("Loaded user from session", $userInfo['uid']));
            return true;
        }
        
        $remoteUser = $this->_getShibVar($this->_getOption('var_remote_user'));
        if ($remoteUser) {
            
            $userId = $remoteUser;
            $userName = $this->_getUserName($userId);
            
            $this->_userInfo = array(
                'uid' => $userId, 
                'name' => $userName, 
                'mail' => ''
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
            
            $this->_debugLog('User authenticated');
            $this->_debugLog($this->_userInfo, true);
            
            $this->_saveUserInfo();
            
            return true;
        }
        
        if (! $this->_getOption('lazy_sessions')) {
            auth_logoff();
        }
        
        return false;
    }


    /**
     * Returns the user's real name.
     * 
     * The real name is resolved either through a template ('tpl_user_name' configuration directive) or directly
     * from an attribute. If neither is successful, the user ID is returned.
     * 
     * @param string $userId
     * @return string
     */
    protected function _getUserName ($userId)
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


    /**
     * Resolves the template for the user's real name and returns it.
     * 
     * @param string $tplUserName
     * @return string
     */
    protected function _getUserNameFromTpl ($tplUserName)
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


    /**
     * Sets user groups directly from the configured environment variable ('var_groups' config option).
     */
    protected function _setGroups ()
    {
        $varGroups = $this->_getOption('var_groups');
        if (! $varGroups) {
            return;
        }
        
        $groups = $this->_getShibVar($varGroups, true);
        foreach ($groups as $groupName) {
            $this->_addUserGroup($groupName);
        }
    }


    /**
     * Sets groups locally defined in the custom groups file.
     * 
     * @param string $userId
     */
    protected function _setCustomGroups ($userId)
    {
        if (! $this->_getOption('customgroups')) {
            return;
        }
        
        $groupsFile = $this->_getOption('customgroups_file');
        if (! file_exists($groupsFile)) {
            $this->_debugLog(sprintf("Non-existent custom groups file '%s'.", $groupsFile));
            return;
        }
        
        $customGroups = array();
        @include $groupsFile;
        
        if (! isset($customGroups)) {
            $this->_debugLog('Custom groups variable not found.');
            return;
        }
        
        if (! is_array($customGroups) || empty($customGroups)) {
            $this->_debugLog('No custom groups specified.');
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


    /**
     * Sets groups defined by user entitlement.
     */
    protected function _setEntitlementGroups ()
    {
        $entVarName = $this->_getOption('var_entitlement');
        if (! $entVarName) {
            $this->_debugLog('entitlement variable name not set');
            return;
        }
        
        $entitlement = $this->_getShibVar($entVarName, true);
        if (! $entitlement) {
            $this->_debugLog('no entitlement values set');
            return;
        }
        
        $entGroups = $this->_getOption('entitlement_groups');
        if (! $entGroups || ! is_array($entGroups)) {
            $this->_debugLog('entitlement groups not configured');
            return;
        }
        
        foreach ($entitlement as $entVal) {
            if (isset($entGroups[$entVal])) {
                $this->_addUserGroup($entGroups[$entVal]);
            }
        }
    }


    /**
     * Adds a group to the user's group list.
     * 
     * @param string $groupName
     */
    protected function _addUserGroup ($groupName)
    {
        if (! isset($this->_userInfo['grps'])) {
            $this->_userInfo['grps'] = array();
        }
        $this->_userInfo['grps'][] = trim($groupName);
    }


    /**
     * Saves user info into the session.
     */
    protected function _saveUserInfo ()
    {
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $this->_userInfo['uid'];
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $this->_userInfo;
    }


    /**
     * Loads user info from the session.
     */
    protected function _loadUserInfo ()
    {
        $this->_userInfo = array();
        if (isset($_SESSION[DOKU_COOKIE]['auth']['info']['uid']) && $_SESSION[DOKU_COOKIE]['auth']['info']['uid'] != '') {
            $this->_userInfo = $_SESSION[DOKU_COOKIE]['auth']['info'];
            return $this->_userInfo;
        }
        
        return NULL;
    }


    /**
     * Sets user info accordingly to the DokuWiki speifics.
     * 
     * Sets the $USERINFO global variable. Sets the REMOTE_USER variable, if it is not populated with the 
     * username from the Shibboleth environment. Despite having the $USERINFO global array, it seems that
     * DokuWiki still uses the REMOTE_USER value.
     * 
     * @param array $userInfo
     */
    protected function _setGlobalUserInfo (Array $userInfo = NULL)
    {
        global $USERINFO;
        
        if (! $userInfo) {
            $userInfo = $this->_userInfo;
        }
        
        $USERINFO = $userInfo;
        
        if ($this->_getOption('var_remote_user') != 'REMOTE_USER') {
            $_SERVER['REMOTE_USER'] = $this->_userInfo['uid'];
        }
    }


    /**
     * Returns current user uid value (username) if available.
     * 
     * @return string
     */
    protected function _getCurrentUid ()
    {
        if (isset($this->_userInfo['uid']) && $this->_userInfo['uid']) {
            return $this->_userInfo['uid'];
        }
        
        return 'unknown';
    }


    /**
     * Returns an environment variable.
     * 
     * @param string $varName
     * @param boolean $multivalue Set true, to expect multivalue attribute.
     */
    protected function _getShibVar ($varName, $multivalue = false)
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


    /**
     * Generates a "logout" URL - an URL, where the current authentication data are destroyed.
     * 
     * It uses the Shibboleth Logout handler.
     * 
     * @param string $returnUrl
     * @param string $handlerName
     * @return string
     */
    protected function _getLogoutUrl ($returnUrl = NULL, $handlerName = 'Logout')
    {
        if (! $returnUrl) {
            $returnUrl = $_SERVER['HTTP_REFERER'];
        }
        
        return sprintf("https://%s%s/%s?return=%s", $_SERVER['HTTP_HOST'], $this->_defShibHandleBase, $handlerName, $returnUrl);
    }


    /**
     * Prints message to the log, if 'debug' is on.
     * 
     * @param string $value
     */
    protected function _debugLog ($value)
    {
        if ($this->_getOption('debug')) {
            if (! is_scalar($value)) {
                if ($this->_getOption('debug_encode_nonscalar')) {
                    $value = json_encode($value);
                } else {
                    $value = print_r($value, true);
                }
            }
            
            /*
             * The log format is:
             * [username/ip address]: message [request uri]
             */
            error_log(sprintf("[%s/%s]: %s [%s]", $this->_getCurrentUid(), $_SERVER['REMOTE_ADDR'], $value, $_SERVER['REQUEST_URI']));
        }
    }
}

?>
<?php

/*
 * Shibboleth Login plugin 
 *
 * Intercepts the 'login' action and redirects the user to the Shibboleth Session Initiator Handler
 * instead of showing the login form. Intended to work with the Shibboleth authentication backend with 
 * "lazy session" enabled.
 * 
 * @author		Ivan Novakov <ivan.novakov@debug.cz>
 * @license 	GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @version		0.4.0
 *
 */

if (! defined('DOKU_INC'))
    die();
if (! defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');


class action_plugin_shiblogin extends DokuWiki_Action_Plugin
{


    function getInfo ()
    {
        return array(
            
            'author' => 'Ivan Novakov', 
            'email' => 'ivan.novakov@debug.cz', 
            'date' => '2008-11-07', 
            'name' => 'Shibboleth Login Plugin', 
            'desc' => 'Action plugin that intercepts the "login" action and triggers Shibboleth Session Initiation. To be used with the Shibboleth authentication backend with lazy sessions enabled.', 
            'url' => 'http://wiki.debug.cz/dokuwiki/plugins/shiblogin'
        );
    }


    function register ($controller)
    {
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_redirectToLoginHandler');
    }


    function _redirectToLoginHandler ($event, $param)
    {
        global $ACT;
        
        if ('login' == $ACT) {
            $loginHandlerLocation = $this->getConf('login_handler_location');
            if (! $loginHandlerLocation) {
                $target = $this->getConf('target');
                if (!$target) {
                  $target = $this->_mkRefererUrl();
                }
            
                $loginHandlerLocation = $this->_mkUrl($_SERVER['HTTP_HOST'], $this->_mkShibHandler(), array(
                    
                    'target' => $target
                ));
            }
            
            header("Location: " . $loginHandlerLocation);
            exit();
        }
    }


    function _mkShibHandler ()
    {
        return sprintf("/%s/%s", $this->getConf('sso_handler'), $this->getConf('login_handler'));
    }


    function _mkUrl ($host, $path, $params = array(), $ssl = true)
    {
        return sprintf("%s://%s%s%s", $ssl ? 'https' : 'http', $host, $path, $this->_mkQueryString($params));
    }


    function _mkRefererUrl ($ssl = true)
    {
        $urlParts = parse_url($_SERVER['HTTP_REFERER']);
        
        $host = $urlParts['host'];
        if ($urlParts['port'] && $urlParts['port'] != '80' && $urlParts['port'] != '443') {
            $host .= ':' . $urlParts['port'];
        }
        
        $query = array();
        parse_str($urlParts['query'], $query);
        
        return $this->_mkUrl($host, $urlParts['path'], $query, $ssl);
    }


    function _mkQueryString ($params = array())
    {
        if (empty($params)) {
            return '';
        }
        
        $queryParams = array();
        foreach ($params as $key => $value) {
            $queryParams[] = sprintf("%s=%s", $key, urlencode($value));
        }
        
        return '?' . implode('amp;', $queryParams);
    }

}

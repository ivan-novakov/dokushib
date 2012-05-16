<?php

/**
 * Example configuration for the Shibboleth Authentication Backend.
 * 
 * Edit the contents of the file and add it to your DokuWiki conf/local.conf file.
 * 
 * @author		Ivan Novakov <ivan.novakov@debug.cz>
 * @license		FreeBSD http://debug.cz/license/freebsd
 */ 

/*
 * Global DokuWiki configuration
 */

// Sets shibboleth authentication
$conf['authtype'] = 'shib';

// Defines the group with superuser permissions
$conf['superuser'] = '@admin';

/*
 * Shibboleth authentication backend configuration
 */
$conf['auth']['shib'] = array(
   
    /*
     * Enable/disable lazy sessions 
     * [ default: false ]
     */
    'lazy_sessions' => false, 
    
    /*
     * Useful, when lazy sessions are on. In that case, Shibboleth session is checked only upon login 
     * and the DokuWiki session is used further on.
     * [ default: false ]
     */
    'use_dokuwiki_session' => false,
    
    /*
     * Explicitly specify users to be granted superuser permission (they are automatically added to the admin group).
     * [ default: <empty array> ]
     */
    'superusers' => array(
        'joe', 
        'admin'
    ),
    
    /*
     * The attribute to be used for the user's username.
     * [ default: 'REMOTE_USER' ]
     */
    'var_remote_user' => 'REMOTE_USER',
    
    /*
     * The attribute containing user's name.
     * [ default: 'cn' ]
     */
    'var_name' => 'cn',
    
    /*
     * The attribute containing user's email.
     * [ default: 'mail' ]
     */
    'var_mail' => 'mail',
    
    /*
     * The name of the attribute containing the user entitlement (eduPersonEntitlement).
     * [ default: 'entitlement' ]
     */
    //'var_entitlement'=> 'entitlement',
    
    /*
     * Custom user name template with server variable substitution.
     * Allows to customize how users' names appear. If not specified, the attribute specified 
     * in the 'var_name' option is used.
     * Example:
     *   - '{cn} ({mail})' - renders the 'cn' attribute followed by 'mail' in brackets
     */
    //'tpl_user_name' => '',

    /*
     * The name of the default group for all authenticated users.
     * [ default: $conf['defaultgroup'] ]
     */
    //'defaultgroup' => 'allusers',

    /*
     * The name of the superusers group.
     * [ default: 'admin' ]
     */
    //'admingroup' => 'admin',

    /*
     * Enable/disable the use of custom groups
     * If enabled, it's possible to define custom user groups in a separate file (see below).
     * The file must contain group information in the following format:
     * $customGroups = array(
     *   'group1' => array('user1', 'user2', ...),
     *   'group2' => array('user1', 'user2', ...),
     *   ...
     * );
     * [ default: false ]
     */
    //'customgroups' => false,

    /*
     * Specify a path for the custom groups file.
     * [ default: DOKU_CONF/custom_groups.php ]
     */
    //'customgroups_file' => '/path/to/custom_groups.php',

    /*
     * Maps entitlements to user groups, 'var_entitlement' must be set.
     */
    /*
    'entitlement_groups' => array(
        'some:special:entitlement' => special_group',
        'https://example.org/entitlement/other' => 'other_group'
    ),
    */
    
    /*
     * Enable/disable debug. If enabled some info is written to he PHP log.
     * [ default: false ]
     */
    //'debug' => false,
    
);

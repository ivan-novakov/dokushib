<?php

# Example configuration for the Shibboleth Authentication Backend


# Global configuration


# Sets shibboleth authentication
$conf['authtype'] = 'shib';

# Defines the group with superuser permissions
$conf['superuser'] = '@admin';

# Shibboleth configuration


# 'lazy sessions' - Enable/disable lazy sessions 
# [ default: false ]
$conf['auth']['shib']['lazy_sessions'] = false;

# 'superusers' - Specify users to be added to the superuser group
$conf['auth']['shib']['superusers'] = array(
    'joe', 'foo'
);

# 'var_remote_user' - The attribute to be used for the user's username
# [ default: REMOTE_USER ]
$conf['auth']['shib']['var_remote_user'] = 'uid';

# 'var_name' - The attribute containing user's name
# [ default: none ]
$conf['auth']['shib']['var_name'] = 'cn';

# 'var_mail' - The attribute containing user's email
# [ default: none ]
$conf['auth']['shib']['var_mail'] = 'mail';

# 'tpl_user_name' - Custom user name template with server variable substitution.
# Allows to customize how users' names appear. If not specified, the attribute specified 
# in the 'var_name' option is used.
# Example:
#   - '{cn} ({mail})' - renders the 'cn' attribute followed by 'mail' in brackets
#$conf['auth']['shib']['tpl_user_name'] = 


# 'defaultgroup' - The name of the default group for all authenticated users
# [ default: $conf['defaultgroup'] ]
#$conf['auth']['shib']['defaultgroup'] = 'allusers';


# 'admingroup' - The name of the superusers group.
# [ default: 'admin' ]
#$conf['auth']['shib']['admingroup'] = 'administrators';


# 'customgroups' - Enable/disable the use of custom groups
# If enabled, it's possible to define custom user groups in a separate file (see below).
# The file must contain group information in that format:
# $customGroups = array(
#   'group1' => array('user1', 'user2', ...),
#   'group2' => array('user1', 'user2', ...),
#   ...
# );
# [ default: false ]
#$conf['auth']['shib']['customgroups'] = true;


# 'customgroups_file' - Specify a path for the custom groups file 
# [ default: DOKU_CONF/custom_groups.php ]
#$conf['auth']['shib']['customgroups_file'] = '/path/to/custom_groups.php';

# 'var_entitlement' - The name of the attribute containing the user entitlement (eduPersonEntitlement)
#$conf['auth']['shib']['var_entitlement'] = 'entitlement';

# 'entitlement_groups' - maps entitlements to user groups, 'var_entitlement' must be set
#$conf['auth']['shib']['entitlement_groups'] = array(
#    'https://www.example.org/special' => 'special_group',
#    'https://www.example.org/internal' => 'internal_group'
#);

# 'debug' - Enable/disable debug. If enabled some info is written to he PHP log.
# [ default: false ]
#$conf['auth']['shib']['debug'] = true;


?>
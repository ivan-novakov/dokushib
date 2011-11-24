<?php

# Example configuration for the shiblogin plugin

# By default, the plugin constructs the login location based on default values, for example:
#     https://<hostname>/Shibboleth.sso/Login?target=<referer_url>
# where <hostname> is the hostname of the server and the <referer_url> is the original
# page, where the login process has been initiated. To specify different values or even to specify 
# the whole URL, use the options below. 

# You can set the parameters individually:

# Shibboleth base handler URL [ default: 'Shibboleth.sso' ]
$conf['plugin']['shiblogin']['sso_handler'] = 'mySite/Shibboleth.sso';

# Shibboleth session initiation handler to use [ default: 'Login' ]
$conf['plugin']['shiblogin']['login_handler'] = 'WAYF';

# Target URL to redirect to after login [ default: current page ]
#$conf['plugin']['shiblogin']['target'] = 'https://www.example.org/page/to/show/after/login';


# ... or you can set the whole URL

# Sets complete login redirect URL, no generation
#$conf['plugin']['shiblogin']['login_handler_location'] = 'https://www.example.org/Shibboleth.sso/Login?target=https://www.example.org/';

?>
Shibboleth authentication backend for DokuWiki
==============================================

* Homepage: [GitHub project][homepage]
* License: [FreeBSD][license]
* Author: [Ivan Novakov][contact]

[DokuWiki][dokuwiki] is a flexible and simple wiki system written in PHP. [Shibboleth][shibboleth] is widely used 
open-source implementation of SAML. DokuWiki supports different authentication backends. It is very easy to write 
an alternative authentication backend and integrate it into DokuWiki.

This backend uses a Shibboleth session to authenticate users. It just takes all required information from the 
environment variables injected by Shibboleth (user attributes sent by the identity provider).

Requirements
------------

* PHP 5.x
* Shibboleth SP 2.x (mostly as Apache module)

Installation
------------

1. Clone the repository and place it anywhere on your system.
2. Copy auth/backend/shib.class.php into DOKUWIKI_HOME/inc/auth

DokuWiki configuration
-------------

The only required part is to put the following line into your conf/local.php configuration file:

    $conf['authtype'] = 'shib';

But you would probably want to specify more parameters. Look into the attached example configuration 
dokushib/auth/conf/example-shibauth-conf.php, all directives are listed and explained there. 
You may put your configuration right into your conf/local.php or in a separate file and include it in conf/local.php.

Shibboleth configuration
------------------------

You need Shibboleth SP 2.x installed. In Apache you have to configure Shibboleth to protect your DokuWiki directory:

    <Directory "/var/www/site/dokuwiki/">
      AuthType shibboleth
      ShibRequireSession On
      require valid-user
    </Directory>

If you want to use lazy sessions (optional login, thus allowing anonymous access), you'll use this instead of the above:

    <Directory "/var/www/sites/dokuwiki/">
      AuthType shibboleth
      require shibboleth
    </Directory>

And of course, you need to allow lazy sessions in your configuration, see the example configuration file. Now, your 
site doesn't require authentication by default. To authenticate a user, an explicit session initiation is required. 
You need to replace the standard DokuWiki login link with the Shibboleth login handler link 
(something like /Shibboleth.sso/Login?target=...). Or you may use the Shibboleth login plugin, which does that for you.

Shibboleth login plugin
-----------------------

It's a simple plugin, which intercepts the DokuWiki login action and fires Shibboleh session initiation instead. 
To install it, just copy dokushib/plugin/shiblogin directory into DOKUWIKI_HOME/lib/plugins. By default, the plugin 
will call this link:

    https://HOSTNAME/Shibboleth.sso/Login?target=REFERER_URL

You can modify this by setting some of the configuration directives, see the example configuration in 
dokushib/plugin/conf/example-shibplugin-conf.php.


[dokuwiki]: http://www.dokuwiki.org/dokuwiki
[shibboleth]: shibboleth
[homepage]: https://github.com/ivan-novakov/dokushib
[license]: http://debug.cz/license/freebsd
[contact]: mailto:ivan.novakov[at]debug.cz
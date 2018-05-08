# reCAPTCHA Plugin #

## Description ##

This plugin provides a Google reCAPTCHA field on subscribe forms. See https://www.google.com/recaptcha/intro/index.html
for information on how reCAPTCHA works.

## Installation ##

### Dependencies ###

The plugin requires phplist version 3.3.0 or later.

Requires php version 5.4 or later.

The plugin also requires CommonPlugin to be installed, see https://resources.phplist.com/plugin/common

At least one of the curl extension, the openssl extension, or the ini setting 'allow_url_fopen' must be enabled.

You must also create an API key to use reCAPTCHA, then enter the site key and the secret key into the plugin's settings.

### Install through phplist ###
Install on the Manage Plugins page (menu Config > Plugins) using the package URL
`https://github.com/bramley/phplist-plugin-recaptcha/archive/master.zip`

### Usage ###

For guidance on configuring and using the plugin see the documentation page https://resources.phplist.com/plugin/recaptcha

## Version history ##

    version         Description
    1.4.0+20180508  Add server and client-side validation
    1.3.0+20170609  Use select input for recaptcha request method.
    1.2.0+20161129  Allow configuration of the colour theme and size of the widget
    1.1.0+20161125  Allow recaptcha to be optionally included on each subscribe page
    1.0.1+20161122  Fix problem with settings not being displayed
                    Display recaptcha in the language of the subscribe page
    1.0.0+20161118  First release

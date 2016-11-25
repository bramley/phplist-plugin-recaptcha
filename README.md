# reCAPTCHA Plugin #

## Description ##

This plugin provides a Google reCAPTCHA field on subscribe forms. See https://www.google.com/recaptcha/intro/index.html
for information on how reCAPTCHA works.

## Installation ##

### Dependencies ###

Requires php version 5.4 or later.

At least one of the curl extension, the openssl extension, or the ini setting 'allow_url_fopen' must be enabled.

You must also create an API key to use reCAPTCHA, then enter the site key and the secret key into the plugin's settings.

### Install through phplist ###
Install on the Plugins page (menu Config > Plugins) using the package URL
`https://github.com/bramley/phplist-plugin-recaptcha/archive/master.zip`

###Settings###

On the Settings page you must specify:

* The reCAPTCHA site key and secret key
* The method to be used for requests to the reCAPTCHA service


## Version history ##

    version         Description
    1.0.1+20161122  Fix problem with settings not being displayed
                    Display recaptcha in the language of the subscribe page
    1.0.0+20161118  First release

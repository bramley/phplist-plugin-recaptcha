<?php
/**
 * RecaptchaPlugin for phplist.
 *
 * This file is a part of RecaptchaPlugin.
 *
 * @author    Duncan Cameron
 * @copyright 2016-2017 Duncan Cameron
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
 * @see       https://developers.google.com/recaptcha/intro
 */

/**
 * This class registers the plugin with phplist and hooks into the display and validation
 * of subscribe pages.
 */
class RecaptchaPlugin extends phplistPlugin
{
    /** @var string the name of the version file */
    const VERSION_FILE = 'version.txt';

    /** @var array the available request methods */
    private $requestMethods = array();

    /** @var string the site key */
    private $siteKey;

    /** @var string the secret key */
    private $secretKey;

    /** @var bool whether reCAPTCHA keys have been entered */
    private $keysEntered;

    /** @var string warning to display when captcha not completed */
    private $incompleteWarning;

    /*
     *  Inherited from phplistPlugin
     */
    public $name = 'reCAPTCHA Plugin';
    public $enabled = true;
    public $description = 'Adds a reCAPTCHA field to subscribe forms';
    public $documentationUrl = 'https://resources.phplist.com/plugin/recaptcha';
    public $authors = 'Duncan Cameron';
    public $coderoot = __DIR__ . '/' . __CLASS__ . '/';
    public $settings = array(
        'recaptcha_sitekey' => array(
            'description' => 'reCAPTCHA site key',
            'type' => 'text',
            'value' => '',
            'allowempty' => false,
            'category' => 'Recaptcha',
        ),
        'recaptcha_secretkey' => array(
            'description' => 'reCAPTCHA secret key',
            'type' => 'text',
            'value' => '',
            'allowempty' => false,
            'category' => 'Recaptcha',
        ),
        'recaptcha_request_method' => array(
            'description' => 'The method to use for sending reCAPTCHA requests',
            'type' => 'select',
            'value' => '',
            'values' => array(),
            'allowempty' => false,
            'category' => 'Recaptcha',
        ),
    );

    /**
     * Creates an instance of the request method. Use the entered config
     * value if valid, otherwise use the first method.
     *
     * @return \ReCaptcha\RequestMethod
     */
    private function createRequestMethod()
    {
        $configValue = getConfig('recaptcha_request_method');
        $method = (isset($this->requestMethods[$configValue]))
            ? $this->requestMethods[$configValue]
            : reset($this->requestMethods);
        $class = $method['class'];

        return new $class();
    }

    /**
     * Derive the language code from the subscribe page language file name.
     *
     * @see https://developers.google.com/recaptcha/docs/language
     *
     * @param string $languageFile the language file name
     *
     * @return string the language code, or an empty string when it cannot
     *                be derived
     */
    private function languageCode($languageFile)
    {
        $fileToCode = array(
            'afrikaans.inc' => 'af',
            'arabic.inc' => 'ar',
            'belgianflemish.inc' => '',
            'bulgarian.inc' => 'bg',
            'catalan.inc' => 'ca',
            'croatian.inc' => 'hr',
            'czech.inc' => 'cs',
            'danish.inc' => 'da',
            'dutch.inc' => 'nl',
            'english-gaelic.inc' => 'en-GB',
            'english.inc' => 'en-GB',
            'english-usa.inc' => 'en',
            'estonian.inc' => 'et',
            'finnish.inc' => 'fi',
            'french.inc' => 'fr',
            'german.inc' => 'de',
            'greek.inc' => 'el',
            'hebrew.inc' => 'iw',
            'hungarian.inc' => 'hu',
            'indonesian.inc' => 'id',
            'italian.inc' => 'it',
            'japanese.inc' => 'ja',
            'latinamerican.inc' => 'es',
            'norwegian.inc' => 'no',
            'persian.inc' => 'fa',
            'polish.inc' => 'pl',
            'portuguese.inc' => 'pt',
            'portuguese_pt.inc' => 'pt-PT',
            'romanian.inc' => 'ro',
            'russian.inc' => 'ru',
            'serbian.inc' => 'sr',
            'slovenian.inc' => 'sl',
            'spanish.inc' => 'es',
            'swedish.inc' => 'sv',
            'swissgerman.inc' => 'de-CH',
            'tchinese.inc' => 'zh-TW',
            'turkish.inc' => 'tr',
            'ukrainian.inc' => 'uk',
            'usa.inc' => 'en',
            'vietnamese.inc' => 'vi',
        );

        return isset($fileToCode[$languageFile]) ? $fileToCode[$languageFile] : '';
    }

    /**
     * Class constructor.
     * Initialises some dynamic variables.
     */
    public function __construct()
    {
        parent::__construct();
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        $this->requestMethods = array();

        if (ini_get('allow_url_fopen') == '1') {
            $this->requestMethods['fopen'] = array(
                'method' => 'fopen',
                'description' => 'http wrapper',
                'class' => '\ReCaptcha\RequestMethod\Post',
            );
        }

        if (extension_loaded('curl')) {
            $this->requestMethods['curl'] = array(
                'method' => 'curl',
                'description' => 'curl extension',
                'class' => '\ReCaptcha\RequestMethod\CurlPost',
            );
        }

        if (extension_loaded('openssl')) {
            $this->requestMethods['openssl'] = array(
                'method' => 'openssl',
                'description' => 'openssl extension',
                'class' => '\ReCaptcha\RequestMethod\SocketPost',
            );
        }
    }

    /**
     * Provide the dependencies for enabling this plugin.
     *
     * @return array
     */
    public function dependencyCheck()
    {
        return array(
            'curl extension, openssl extension or http wrapper available' => count($this->requestMethods) > 0,
            'Common Plugin installed' => phpListPlugin::isEnabled('CommonPlugin'),
            'phpList version 3.3.0 or later' => version_compare(VERSION, '3.3') > 0,
        );
    }

    /**
     * Add a configuration setting for the request method.
     * Cache the plugin's config settings.
     * Recaptcha will be used only when both the site key and secrety key have
     * been entered.
     */
    public function activate()
    {
        $methods = array_column($this->requestMethods, 'description', 'method');
        $firstMethod = reset($this->requestMethods);
        $this->settings['recaptcha_request_method']['value'] = $firstMethod['method'];
        $this->settings['recaptcha_request_method']['values'] = $methods;

        parent::activate();

        $this->siteKey = getConfig('recaptcha_sitekey');
        $this->secretKey = getConfig('recaptcha_secretkey');
        $this->keysEntered = $this->siteKey !== '' && $this->secretKey !== '';
        $this->incompleteWarning = s('Please complete the reCAPTCHA');
    }

    /**
     * Provide the recaptcha html to be included in a subscription page.
     *
     * @param array $pageData subscribe page fields
     * @param int   $userId   user id
     *
     * @return string
     */
    public function displaySubscriptionChoice($pageData, $userID = 0)
    {
        if (empty($pageData['recaptcha_include'])) {
            return '';
        }

        if (!$this->keysEntered) {
            return '';
        }
        $apiUrl = 'https://www.google.com/recaptcha/api.js';

        if (isset($pageData['language_file'])) {
            $languageCode = $this->languageCode($pageData['language_file']);

            if ($languageCode !== '') {
                $apiUrl .= "?hl=$languageCode";
            }
        }
        $format = <<<'END'
<div class="g-recaptcha" data-sitekey="%s" data-size="%s" data-theme="%s"></div>
<script type="text/javascript" src="%s"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script type="text/javascript">
$( document ).ready(function() {
    $("form[name=subscribeform]").submit(function(ev) {
        if (grecaptcha.getResponse() != "") {
            return true;
        }
        alert("%s");
        return false;
    });
});
</script>
END;

        return sprintf($format, $this->siteKey, $pageData['recaptcha_size'], $pageData['recaptcha_theme'], $apiUrl, $this->incompleteWarning);
    }

    /**
     * Provide additional validation when a subscribe page has been submitted.
     *
     * @param array $pageData subscribe page fields
     *
     * @return string an error message to be displayed or an empty string
     *                when validation is successful
     */
    public function validateSubscriptionPage($pageData)
    {
        require $this->coderoot . 'src/autoload.php';

        if (empty($pageData['recaptcha_include'])) {
            return '';
        }

        if (!$this->keysEntered) {
            return '';
        }

        if (empty($_POST['g-recaptcha-response'])) {
            return $this->incompleteWarning;
        }
        $recaptcha = new \ReCaptcha\ReCaptcha($this->secretKey, $this->createRequestMethod());
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

        return $resp->isSuccess()
            ? ''
            : implode(', ', $resp->getErrorCodes());
    }

    /**
     * Provide html for the reCAPTCHA options when editing a subscribe page.
     *
     * @param array $pageData subscribe page fields
     *
     * @return string additional html
     */
    public function displaySubscribepageEdit($pageData)
    {
        $include = isset($pageData['recaptcha_include']) ? (bool) $pageData['recaptcha_include'] : false;
        $theme = isset($pageData['recaptcha_theme']) ? $pageData['recaptcha_theme'] : 'light';
        $size = isset($pageData['recaptcha_size']) ? $pageData['recaptcha_size'] : 'normal';
        $html =
            CHtml::label(s('Include reCAPTCHA in the subscribe page'), 'recaptcha_include')
            . CHtml::checkBox('recaptcha_include', $include, array('value' => 1, 'uncheckValue' => 0))
            . '<p></p>'
            . CHtml::label(s('The colour theme of the reCAPTCHA widget'), 'recaptcha_theme')
            . CHtml::dropDownList('recaptcha_theme', $theme, array('light' => 'light', 'dark' => 'dark'))
            . CHtml::label(s('The size of the reCAPTCHA widget'), 'recaptcha_size')
            . CHtml::dropDownList('recaptcha_size', $size, array('normal' => 'normal', 'compact' => 'compact'));

        return $html;
    }

    /**
     * Save the reCAPTCHA settings.
     *
     * @param int $id subscribe page id
     */
    public function processSubscribePageEdit($id)
    {
        global $tables;

        Sql_Query(
            sprintf('
                REPLACE INTO %s
                (id, name, data)
                VALUES
                (%d, "recaptcha_include", "%s"),
                (%d, "recaptcha_theme", "%s"),
                (%d, "recaptcha_size", "%s")
                ',
                $tables['subscribepage_data'],
                $id,
                $_POST['recaptcha_include'],
                $id,
                $_POST['recaptcha_theme'],
                $id,
                $_POST['recaptcha_size']
            )
        );
    }
}

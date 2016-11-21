<?php

/**
 * RecaptchaPlugin for phplist.
 * 
 * This file is a part of RecaptchaPlugin.
 * 
 * @author    Duncan Cameron
 * @copyright 2016 Duncan Cameron
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
    const VERSION_FILE = 'version.txt';

    /*
     *  Inherited variables
     */
    public $name = 'reCAPTCHA Plugin';
    public $enabled = true;
    public $description = 'Adds a reCAPTCHA field to subscribe forms';
    public $authors = 'Duncan Cameron';
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
    );

    /**
     * Derive the language code from the subscribe page language file name.
     *
     * @see https://developers.google.com/recaptcha/docs/language
     * 
     * @param string $languageFile the language file name
     * 
     * @return string the language code, or an empty string when it cannot
     *                be derived.
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
     */
    public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
    }

    /**
     * Provide the dependencies for enabling this plugin.
     *
     * @return array
     */
    public function dependencyCheck()
    {
        return array(
            'curl or http wrapper available' => (
                extension_loaded('curl') || ini_get('allow_url_fopen') == '1'
            ),
        );
    }

    /**
     * Use this hook to cache the plugin's config settings.
     * Recaptcha will be used only when both the site key and secrety key have
     * been entered.
     */
    public function activate()
    {
        $this->siteKey = getConfig('recaptcha_sitekey');
        $this->secretKey = getConfig('recaptcha_secretkey');
        $this->recaptchaEnabled = $this->siteKey !== '' && $this->secretKey !== '';
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
        if (!$this->recaptchaEnabled) {
            return '';
        }
        $apiUrl = 'https://www.google.com/recaptcha/api.js';

        if (isset($pageData['language_file'])) {
            $languageCode = $this->languageCode($pageData['language_file']);

            if ($languageCode !== '') {
                $apiUrl .= "?hl=$languageCode";
            }
        }
        $html = <<<END
<div class="g-recaptcha" data-sitekey="{$this->siteKey}"></div>
    <script type="text/javascript" src="$apiUrl">
    </script>
END;

        return $html;
    }

    /**
     * Provide additional validation when a subscribe page has been submitted.
     *
     * @param array $pageData subscribe page fields
     * 
     * @return string an error message to be displayed or an empty string
     *                when validation is successful.
     */
    public function validateSubscriptionPage($pageData)
    {
        require __DIR__ . '/RecaptchaPlugin/src/autoload.php';

        if (!$this->recaptchaEnabled) {
            return '';
        }

        if (!isset($_POST['g-recaptcha-response'])) {
            return 'reCAPTCHA must be used';
        }

        $requestMethod = extension_loaded('curl')
            ? new \ReCaptcha\RequestMethod\CurlPost()
            : new \ReCaptcha\RequestMethod\Post();
        $recaptcha = new \ReCaptcha\ReCaptcha($this->secretKey, $requestMethod);
        $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

        return $resp->isSuccess()
            ? ''
            : implode(', ', $resp->getErrorCodes());
    }
}
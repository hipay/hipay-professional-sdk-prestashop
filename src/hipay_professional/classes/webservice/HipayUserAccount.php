<?php
/**
 * 2016 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2016 HiPay
 * @license   https://github.com/hipay/hipay-wallet-sdk-prestashop/blob/master/LICENSE.md
 */

require_once(dirname(__FILE__) . '/HipayREST.php');

class HipayUserAccount extends HipayREST
{
    protected $accounts_currencies = array();
    protected $client_url = 'user-account';
    protected $client_tools = 'tools';
    protected $module = false;
    protected static $email_available = null;
    protected $business_lines = 18;
    protected $website_topic = 175;

    public function __construct($module_instance)
    {
        parent::__construct($module_instance);

        $this->accounts_currencies = array(
            'CHF' => $this->module->l('Swiss Franc', 'HipayUserAccount'),
            'EUR' => $this->module->l('Euro', 'HipayUserAccount'),
            'GBP' => $this->module->l('British Pound', 'HipayUserAccount'),
            'SEK' => $this->module->l('Swedish Krona', 'HipayUserAccount'),
        );
    }

    /**
     * Get ID and image for the security code by CAPTCHA
     */
    public function getCaptcha()
    {
        $params = [];
        $result = $this->sendApiRequest($this->client_tools . '/captcha', 'get', false, $params, false, true);

        if ($result->code == 0) {
            return $result;
        } else {
            throw new Exception(print_r($result, true));
        }
    }

    /**
     * Check code to activate account merchant
     */
    public function checkCodeValidation($code, $currency_code)
    {
        // init val for webservice
        $params = [
            'validation_code' => $code,
        ];
        $result = $this->sendApiRequest($this->client_url . '/check/code', 'post', true, $params, false, false);

        if ($result->code == 0) {
            return $result;
        } else {
            throw new Exception(print_r($result, true));
        }
    }

    /**
     * Create an account in production
     */
    public function createAccount($params)
    {
        // get currency default
        $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $currency_code = Tools::strtoupper($currency->iso_code);
        // get country code default
        $country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'));
        $country_code = Tools::strtolower($country->iso_code);
        // get code iso
        $language = new Language(Configuration::get('PS_LANG_DEFAULT'));
        $language_code = Tools::strtoupper($language->iso_code);

        $data = array(
            'email' => $params['email'],
            'controle_type' => 'CAPTCHA',
            'captcha' =>
                [
                    'id' => $params['captcha_id'],
                    'phrase' => $params['captcha_code'],
                ],
            'firstname' => $params['first_name'],
            'lastname' => $params['last_name'],
            'currency' => $currency_code,
            'locale' => $country_code . '_' . $language_code,
            'activation_type' => true,
        );

        $this->module->logs->logsHipay(print_r($data, true));

        $result = $this->sendApiRequest($this->client_url, 'post', false, $data, false, false);

        $this->module->logs->logsHipay(print_r($result, true));

        if ($result->code == 0) {
            return $result;
        } else {
            throw new Exception(print_r($result, true));
        }
    }

    /**
     * Create an account in production
     */
    public function createWebsite($currency, $account_id = 0, $parent_id = 0, $parent_currency = '', $sandbox = 0)
    {
        // init params web service
        $email = Configuration::get('PS_SHOP_EMAIL');

        // get infos
        $config = $sandbox ? $this->module->configHipay->sandbox : $this->module->configHipay->production;

        // object to array fix
        $config = $this->module->objectToArray($config);

        $this->module->logs->logsHipay('currency en input = ' . $currency);
        $this->module->logs->logsHipay('account_id en input = ' . $account_id);
        $this->module->logs->logsHipay('parent currency en input = ' . $parent_currency);
        $this->module->logs->logsHipay('parent account_id en input = ' . $parent_id);

        // subaccount add website
        if ((int)$account_id > 0 && (int)$parent_id > 0) {
            $objCur = $config[$parent_currency];

            $this->module->logs->logsHipay('parent_id = ' . $parent_id . ' account_id != 0 ');

            $objAcc = $objCur[$parent_id];
            $email = $objAcc[0]['user_mail'];

            $this->module->logs->logsHipay('treatment subaccount with id = ' . $account_id);
        } else {
            // account add website
            $this->module->logs->logsHipay('account_id == 0 ');

            $objCur = $config[$currency];
            foreach ($objCur as $key => $val) {
                $objKey = $objCur[$key];
                $account_id = $key;
                $email = $objKey[0]['user_mail'];
                break;
            }
            $this->module->logs->logsHipay('treatment account with id = ' . $account_id);
        }

        $sandbox_mode = ($sandbox == 1 ? 'sandbox' : 'production');
        $login = $sandbox_mode . '_ws_login';
        $password = $sandbox_mode . '_ws_password';
        $ws_login = $this->module->configHipay->$login;
        $ws_password = $this->module->configHipay->$password;

        $params = [
            'name' => Configuration::get('PS_SHOP_NAME'),
            'url' => Tools::getShopDomainSsl(true),
            'contact_email' => $email,
            'business_line' => $this->business_lines,
            'topic' => $this->website_topic,
            'php-auth-subaccount-id' => $account_id,
            'ws_login' => $ws_login,
            'ws_password' => $ws_password,
        ];

        $this->module->logs->logsHipay(print_r($params, true));
        // call api and execute create website
        $result = $this->sendApiRequest($this->client_url . '/website', 'post', false, $params, $sandbox, false);
        return $result;
    }

    /**
     * Check code to activate account merchant
     */
    public function duplicateByCurrency($params = [], $sandbox = false)
    {
        $sandbox_mode = ($sandbox == 1 ? 'sandbox' : 'production');
        $login = $sandbox_mode . '_ws_login';
        $password = $sandbox_mode . '_ws_password';
        $ws_login = $this->module->configHipay->$login;
        $ws_password = $this->module->configHipay->$password;

        $params['ws_login'] = $ws_login;
        $params['ws_password'] = $ws_password;

        $result = $this->sendApiRequest($this->client_url . '/duplicate', 'post', false, $params, $sandbox, false);
        return $result;
    }

    /**
     * get user informations saved in HiPay Direct / Wallet with WSlogin and WSpassword
     */
    public function getAccountInfos($params = [], $needLogin = true, $needSandboxLogin = false)
    {
        $result = $this->sendApiRequest($this->client_url, 'get', $needLogin, $params, $needSandboxLogin);
        return $result;
    }
}

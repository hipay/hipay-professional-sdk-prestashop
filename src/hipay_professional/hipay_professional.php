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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Hipay_Professional extends PaymentModule
{
    protected $config_form = false;
    public $_errors = [];
    protected $_successes = [];
    protected $_warnings = [];
    public $currencies_titles = [];
    public $limited_countries = [];
    public $limited_currencies = [];
    public $configHipay;
    public $hipay_rating = [];
    public $create_account = false;
    public $min_amount = 1;
    public static $available_rates_links = [
        'EN', 'FR', 'ES', 'DE',
        'IT', 'NL', 'PL', 'PT'
    ];
    public static $refund_available = ['CB', 'VISA', 'MASTERCARD'];
    public $logs;

    const URL_TEST_HIPAY_DIRECT = 'https://test-www.hipaydirect.com/';
    const URL_PROD_HIPAY_DIRECT = 'https://www.hipaydirect.com/';
    const URL_TEST_HIPAY_WALLET = 'https://test-www.hipaywallet.com/';
    const URL_PROD_HIPAY_WALLET = 'https://www.hipaywallet.com/';

    public function __construct()
    {
        $this->name = 'hipay_professional';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->module_key = 'ab188f639335535838c7ee492a2e89f8';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->controllers = array('validation');
        $this->author = 'HiPay';
        $this->is_eu_compatible = 1;

        $this->bootstrap = true;
        $this->display = 'view';

        parent::__construct();

        // init log object
        $this->logs = new HipayLogs($this);

        $this->displayName = $this->l('HiPay Professional');
        $this->description = $this->l('Accept payments by credit card and other local methods with HiPay Professional. Very competitive rates, no configuration required!');

        // Compliancy
        $this->limited_countries = [
            'AT', 'BE', 'CH', 'CY', 'CZ', 'DE', 'DK',
            'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HK',
            'HR', 'HU', 'IE', 'IT', 'LI', 'LT', 'LU',
            'LV', 'MC', 'MT', 'NL', 'NO', 'PL', 'PT',
            'RO', 'RU', 'SE', 'SI', 'SK', 'TR'
        ];

        $this->currencies_titles = [
            'AUD' => $this->l('Australian dollar'),
            'CAD' => $this->l('Canadian dollar'),
            'CHF' => $this->l('Swiss franc'),
            'EUR' => $this->l('Euro'),
            'GBP' => $this->l('Pound sterling'),
            'PLN' => $this->l('Polish złoty'),
            'SEK' => $this->l('Swedish krona'),
            'USD' => $this->l('United States dollar'),
        ];

        $this->hipay_rating = [
            ['key' => 'ALL', 'name' => $this->l('For all ages')],
            ['key' => '+12', 'name' => $this->l('For ages 12 and over')],
            ['key' => '+16', 'name' => $this->l('For ages 16 and over')],
            ['key' => '+18', 'name' => $this->l('For ages 18 and over')],
        ];

        $this->limited_currencies = array_keys($this->currencies_titles);

        if (!Configuration::get('HIPAY_CONFIG')) {
            $this->warning = $this->l('Please, do not forget to configure your module');
        }
        $this->configHipay = $this->getConfigHiPay();
    }

    /**
     * Functions installation HiPay module or uninstall
     */
    public function install()
    {
        if (extension_loaded('soap') == false) {
            $this->_errors[] = $this->l('You have to enable the SOAP extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module cannot work in your country');
            return false;
        }

        return parent::install() &&
        $this->installHipay();
    }

    public function uninstall()
    {
        return $this->uninstallAdminTab() &&
        parent::uninstall() &&
        $this->clearAccountData();
    }

    public function installAdminTab()
    {
        $class_names = [
            'AdminHiPayCapture',
            'AdminHiPayRefund',
            'AdminHiPayConfig',
        ];
        return $this->createTabAdmin($class_names);
    }

    protected function createTabAdmin($class_names)
    {
        foreach ($class_names as $class_name) {
            $tab = new Tab();

            $tab->active = 1;
            $tab->module = $this->name;
            $tab->class_name = $class_name;
            $tab->id_parent = -1;

            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $this->name;
            }
            if (!$tab->add()) {
                return false;
            }
        }
        return true;
    }

    public function uninstallAdminTab()
    {
        $class_names = [
            'AdminHiPayCapture',
            'AdminHiPayRefund',
            'AdminHiPayConfig',
        ];
        foreach ($class_names as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);

            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }
        return true;
    }

    public function installHipay()
    {
        $return = $this->setCurrencies() &&
            $this->insertConfigHiPay() &&
            $this->installAdminTab() &&
            $this->updateHiPayOrderStates() &&
            $this->registerHook('header') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('paymentTop') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayAdminOrderLeft');

        if (_PS_VERSION_ >= '1.7') {
            $return17 = $this->registerHook('paymentOptions');
            $return = $return && $return17;
        } elseif (_PS_VERSION_ < '1.7' && _PS_VERSION_ >= '1.6') {
            $return16 = $this->registerHook('payment') &&
                $this->registerHook('displayPaymentEU');
            $return = $return && $return16;
        }
        return $return;
    }

    /**
     * Store the currencies list the module should work with
     * @return boolean
     */
    public function setCurrencies()
    {
        $shops = Shop::getShops(true, null, true);

        foreach ($shops as $shop) {
            $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'module_currency` (`id_module`, `id_shop`, `id_currency`)
                    SELECT ' . (int)$this->id . ', "' . (int)$shop . '", `id_currency`
                    FROM `' . _DB_PREFIX_ . 'currency`
                    WHERE `deleted` = \'0\' AND `iso_code` IN (\'' . implode($this->limited_currencies, '\',\'') . '\')';

            return (bool)Db::getInstance()->execute($sql);
        }

        return true;
    }

    protected function getCurrencies()
    {
        // get currencies
        $currencies = Currency::getCurrenciesByIdShop((int)$this->context->shop->id);
        $selectedCurrencies = [];
        foreach ($currencies as $currency) {
            $selectedCurrencies[$currency['iso_code']] = '';
        }
        return $selectedCurrencies;
    }

    /**
     * Hook available for HiPay
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') != $this->name) {
            return false;
        }

        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        return '<script type="text/javascript">
            var email_error_message = "' . $this->l('Please, enter a valid email address') . '.";
        </script>';
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        $order = new Order((int)$params['id_order']);

        if ((!$order->id) || ($order->module != $this->name)) {
            return false;
        }

        if ((int)$order->getCurrentState() == (int)Configuration::get('HIPAY_OS_WAITING')) {
            // template Capture Order
            $messages = Message::getMessagesByOrderId($order->id, true);
            $message = array_pop($messages);
            $details = Tools::jsonDecode($message['message']);
            $params = http_build_query([
                'id_order' => $order->id,
                'sandbox' => (isset($details->Environment) && ($details->Environment != 'PRODUCTION') ? 1 : 0),
            ]);
            $this->smarty->assign([
                'ajax_url' => $this->context->link->getAdminLink('AdminHiPayCapture&' . $params, true),
                'details' => $details,
            ]);
            return $this->display(dirname(__FILE__), 'views/templates/hook/capture_manual.tpl');
        } else {
            // template Refund Order
            $details = $this->getAdminOrderRefundBlockDetails($order);
            $this->context->controller->addCSS($this->_path . 'views/css/refund.css');
            if ($this->orderAlreadyRefunded($order)) {
                return $this->display(dirname(__FILE__), 'views/templates/hook/already_refunded.tpl');
            } elseif (!$this->isRefundAvailable($details)) {
                return $this->display(dirname(__FILE__), 'views/templates/hook/cannot_be_refunded.tpl');
            } elseif ($this->isProductionOrder($details)) {
                $min_date = date('Y-m-d H:i:s', strtotime($order->date_add . ' +1 day'));
                if ($min_date > date('Y-m-d H:i:s')) {
                    return $this->display(dirname(__FILE__), 'views/templates/hook/cannot_refund_yet.tpl');
                }
            }
            $this->context->controller->addJS($this->_path . 'views/js/order.js');
            return $this->display(dirname(__FILE__), 'views/templates/hook/refund.tpl');
        }
    }

    public function hookHeader()
    {
        return $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookPayment($params)
    {
        $currency_id = $params['cart']->id_currency;
        $currency = new Currency((int)$currency_id);
        $isocode_currency = $currency->iso_code;

        $config_hipay = $this->configHipay;
        if ((!$this->configHipay->sandbox_mode && $this->configHipay->selected->currencies->production->$isocode_currency->accountID)
            || ($this->configHipay->sandbox_mode && $this->configHipay->selected->currencies->sandbox->$isocode_currency->accountID)
        ) {
            if (in_array($isocode_currency, $this->limited_currencies) == false) {
                return false;
            }

            $this->smarty->assign(array(
                'domain' => Tools::getShopDomainSSL(true),
                'module_dir' => $this->_path,
                'payment_button' => $this->getPaymentButton(),
                'min_amount' => $this->min_amount,
                'configHipay' => $this->objectToArray($config_hipay),
                'lang' => Tools::strtolower($this->context->language->iso_code),
            ));

            $this->smarty->assign('hipay_prod', !(bool)$this->configHipay->sandbox_mode);

            return $this->display(dirname(__FILE__), 'views/templates/hook/payment.tpl');
        }

        return false;
    }

    public function hookPaymentReturn($params)
    {
        if (_PS_VERSION_ >= '1.7') {
            $hipay17 = new HipayProfessionalNew();
            $hipay17->hipayPaymentReturnNew($params);
        } elseif (_PS_VERSION_ < '1.7' && _PS_VERSION_ >= '1.6') {
            $this->hipayPaymentReturn($params);

            return $this->display(dirname(__FILE__), 'views/templates/hook/confirmation.tpl');
        }
    }

    private function hipayPaymentReturn($params)
    {
        // Payment Return for PS1.6
        if ($this->active == false) {
            return;
        }

        $order = $params['objOrder'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(
            array(
                'id_order' => $order->id,
                'reference' => $order->reference,
                'params' => $params,
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                'shop_name' => $this->context->shop->name,
            )
        );
    }

    public function hookPaymentTop()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    /*
    * VERSION PS 1.7
    *
    */
    public function hookPaymentOptions($params)
    {
        $hipay17 = new HipayProfessionalNew();
        return $hipay17->hipayPaymentOptions($params);
    }

    /**
     * Load configuration page
     * @return string
     */
    public function getContent()
    {
        $this->logs->logsHipay('##########################');
        $this->logs->logsHipay('---- START function getContent');
        $form = new HipayForm($this);
        $user_account = new HipayUserAccount($this);

        $config_hipay = $this->configHipay;

        $this->postProcess($user_account);

        // Generate configuration forms
        if (!empty($this->configHipay->production_ws_login) && !$this->create_account && $this->configHipay->production_status) {
            // get currencies
            $selectedCurrencies = $this->getCurrencies();

            // get button images
            $images = $this->getImageButtons();
            array_push($images, 'no_image');
            $url_img = $this->_path . 'views/img/payment_buttons/';

            $this->context->smarty->assign(array(
                'is_logged' => true,
                'logs' => $this->getLogFiles(),
                'rating' => $this->hipay_rating,
                'selectedCurrencies' => $selectedCurrencies,
                'limitedCurrencies' => $this->currencies_titles,
                'button_images' => $images,
                'url_images' => $url_img,
            ));
            // init warning
            $this->getWarningHiPayStatus();
        } else {
            // get captcha by api
            // only if first step create account, not validation account
            if ($this->create_account) {
                try {
                    $user_account = new HipayUserAccount($this);
                    $captcha = $user_account->getCaptcha();
                } catch (Exception $e) {
                    $this->logs->errorLogsHipay($e->getMessage());
                    $this->_errors[] = $this->l('error - method captcha not allowed.');
                }
            } else {
                $captcha = false;
            }
            $this->context->smarty->assign(array(
                'is_logged' => false,
                'login_form' => $form->getLoginForm(),
                'register_form' => $form->getRegisterForm($captcha),
            ));
        }

        // Set alert messages
        $this->context->smarty->assign(array(
            'form_errors' => $this->_errors,
            'form_successes' => $this->_successes,
            'form_infos' => $this->_warnings,
        ));

        // Define templates paths
        $alerts = $this->local_path . 'views/templates/admin/alerts.tpl';
        $configuration = $this->local_path . 'views/templates/admin/configuration.tpl';

        $this->context->smarty->assign(array(
            'alerts' => $this->context->smarty->fetch($alerts),
            'module_dir' => $this->_path,
            'config_hipay' => $this->objectToArray($config_hipay),
            'url_test_hipay_direct' => Hipay_Professional::URL_TEST_HIPAY_DIRECT,
            'url_prod_hipay_direct' => Hipay_Professional::URL_PROD_HIPAY_DIRECT,
            'url_test_hipay_wallet' => Hipay_Professional::URL_TEST_HIPAY_WALLET,
            'url_prod_hipay_wallet' => Hipay_Professional::URL_PROD_HIPAY_WALLET,
            'ajax_url' => $this->context->link->getAdminLink('AdminHiPayConfig'),
        ));
        $this->logs->logsHipay('---- END function getContent');
        $this->logs->logsHipay('##########################');
        return $this->context->smarty->fetch($configuration);
    }

    protected function postProcess($user_account)
    {
        $ur_redirection = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $this->logs->logsHipay('---- >> function postProcess');

        if (Tools::isSubmit('submitReset')) {
            $this->logs->logsHipay('---- >> submitReset');
            // reset in login
            $this->context->smarty->assign('active_tab', 'login_form');
            $this->create_account = true;
            $this->clearAccountData();
            Tools::redirectAdmin($ur_redirection);
        } elseif (Tools::isSubmit('submitLogin')) {
            $this->logs->logsHipay('---- >> submitLogin');
            // execute login
            $this->context->smarty->assign('active_tab', 'login_form');
            if ($this->login($user_account)) {
                Tools::redirectAdmin($ur_redirection);
            } else {
                return false;
            }
        } elseif (Tools::isSubmit('submitSettings')) {
            $this->logs->logsHipay('---- >> submitSettings');
            // save the settings form
            $this->context->smarty->assign('active_tab', 'settings_form');
            if ($this->saveSettingsConfiguration()) {
                return $this->majConfigurationByApi($user_account);
            } else {
                return false;
            }
        } elseif (Tools::isSubmit('submitCancel')) {
            $this->logs->logsHipay('---- >> submitCancel');
            // discard in settings form
            $this->context->smarty->assign('active_tab', 'settings_form');
            $this->majConfigurationByApi($user_account);
            Tools::redirectAdmin($ur_redirection);
        } elseif (Tools::isSubmit('submitSandboxConnection')) {
            $this->logs->logsHipay('---- >> submitSandboxConnection');
            // execute login sandbox
            $this->context->smarty->assign('active_tab', 'settings_form');
            if ($this->loginSandbox($user_account)) {
                Tools::redirectAdmin($ur_redirection);
            } else {
                return false;
            }
        } elseif (Tools::isSubmit('submitPaymentbutton')) {
            $this->logs->logsHipay('---- >> submitPaymentbutton');
            // save the payment buttons form
            $this->context->smarty->assign('active_tab', 'button_form');
            if ($this->savePaymentButtonConfiguration()) {
                return $this->majConfigurationByApi($user_account);
            } else {
                return false;
            }
        } elseif (Tools::isSubmit('reloadCaptcha')) {
            $this->logs->logsHipay('---- >> reloadCaptcha');
            // reload a new captcha because it's hard to read it
            $this->context->smarty->assign('active_tab', 'register_form');
            return Tools::redirectAdmin($ur_redirection);
        } elseif (Tools::isSubmit('submitRegister')) {
            $this->logs->logsHipay('---- >> submitRegister');
            // create an account in production
            $this->context->smarty->assign('active_tab', 'register_form');
            if ($this->createMerchantAccount()) {
                // captcha and create account are ok
                // display second screen to validate code validator
                $this->logs->logsHipay('---- >> Display Validator form and validate account');
                $this->context->smarty->assign('validator', true);
                $this->context->smarty->assign('email', Tools::getValue('register_user_email'));
                $this->create_account = false;
            } else {
                // error captcha or create an account
                $this->logs->logsHipay('---- >> Display captcha form because error');
                $this->context->smarty->assign('validator', false);
                $this->create_account = true;
                return false;
            }
            return true;
        } elseif (Tools::isSubmit('submitValidator')) {
            $this->logs->logsHipay('---- >> submitValidator');
            if ($this->checkCodeValidation()) {
                Tools::redirectAdmin($ur_redirection);
            } else {
                $this->logs->logsHipay('---- >> Display Validator form because error');
                $this->context->smarty->assign('active_tab', 'register_form');
                if ($this->create_account && !$this->configHipay->production_status) {
                    $this->context->smarty->assign('validator', false);
                    $this->create_account = true;
                } else {
                    $this->context->smarty->assign('validator', true);
                    $this->create_account = false;
                }
                return false;
            }
        } else {
            $this->logs->logsHipay('---- >> default action');
            // default action
            if (!empty($this->configHipay->production_ws_login)) {
                $this->context->smarty->assign('active_tab', 'settings_form');
            } else {
                $this->context->smarty->assign('active_tab', 'login_form');
                $this->create_account = true;
            }
            // update by api hipay
            return $this->majConfigurationByApi($user_account);
        }
    }

    /**
     * Get the appropriate payment button's image
     * @return string
     */
    protected function getPaymentButton()
    {
        $img_selected = $this->configHipay->button_images;
        if (!empty($img_selected) && file_exists(dirname(__FILE__) . '/views/img/payment_buttons/' . $img_selected)) {
            return $this->_path . 'views/img/payment_buttons/' . $img_selected;
        }
        // image by default
        return $this->_path . 'views/img/payment_buttons/default.png';
    }

    /**
     * Get the appropriate logs
     * @return string
     */
    protected function getLogFiles()
    {
        // scan log dir
        $dir = _PS_MODULE_DIR_ . '/hipay_professional/logs/';
        $files = scandir($dir, 1);
        // init array files
        $error_files = [];
        $info_files = [];
        $callback_files = [];
        $request_files = [];
        $refund_files = [];
        // dispatch files
        foreach ($files as $file) {
            if (preg_match("/error/i", $file) && count($error_files) < 10) {
                $error_files[] = $file;
            }
            if (preg_match("/callback/i", $file) && count($callback_files) < 10) {
                $callback_files[] = $file;
            }
            if (preg_match("/infos/i", $file) && count($info_files) < 10) {
                $info_files[] = $file;
            }
            if (preg_match("/request/i", $file) && count($request_files) < 10) {
                $request_files[] = $file;
            }
            if (preg_match("/refund/i", $file) && count($refund_files) < 10) {
                $refund_files[] = $file;
            }
        }
        return [
            'error' => $error_files,
            'infos' => $info_files,
            'callback' => $callback_files,
            'request' => $request_files,
            'refund' => $refund_files
        ];
    }

    /**
     * Check if the given currency is supported by the provider
     * @param string $iso_code currency iso code
     * @return boolean
     */
    public function isSupportedCurrency($iso_code)
    {
        return in_array(Tools::strtoupper($iso_code), $this->limited_currencies);
    }

    /**
     * Load warning information if account merchant
     * is not activated, identified or bank infos status
     */
    protected function getWarningHiPayStatus()
    {
        // get config info
        $activated = $this->configHipay->production_status;
        $bank_info_validated = $this->configHipay->bank_info_validated;
        $identified = $this->configHipay->identified;
        $ws_login = $this->configHipay->production_ws_login;
        // account activated
        // @ return 0 or 1 (0 = not activated) (1 = activated
        if (!$activated && $ws_login) {
            $this->_warnings[] = $this->l('Your account is not activated, if you encounter a problem contact the support HiPay at support.direct@hipay.com');
        }

        // bank status
        // @ return 0 or 1 (0 = empty / waiting validation) (1 = validated)
        if (!$bank_info_validated && $ws_login) {
            $this->_warnings[] = $this->l('Please provide your bank information on your HiPay merchant back office.');
        }

        // identified
        // @ return 0 or 1 (0 = empty / waiting identification) (1 = identified)
        if (!$identified && $ws_login) {
            $this->_warnings[] = $this->l('In order to identify yourself, we invite you to upload the following documents and to send them to us for validation on your HiPay merchant back office.');
        }
    }

    /**
     * Save configuration about account merchant
     * Login production and sandbox
     *
     */
    protected function loginSandbox($user_account)
    {
        $this->logs->logsHipay('---- >> function loginSandbox');
        // get values sandbox login and password
        $ws_login = Tools::getValue('modal_ws_login');
        $ws_password = Tools::getValue('modal_ws_password');

        if ($ws_login && $ws_password) {
            try {
                // ctrl if login and password are crypted to md5
                $is_valid_login = (bool)Validate::isMd5($ws_login);
                $is_valid_password = (bool)Validate::isMd5($ws_password);

                if ($is_valid_login && $is_valid_password) {
                    $params = [
                        'ws_login' => $ws_login,
                        'ws_password' => $ws_password,
                    ];
                    $user_account = new HipayUserAccount($this);
                    $account = $user_account->getAccountInfos($params, false, true);

                    if (isset($account->code) && ($account->code == 0)) {
                        if ($this->registerExistingAccount($account, $params, true)) {
                            $this->preloadConfig(true);
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        $this->_errors[] = $this->l('Authentication failed!');
                    }
                } else {
                    $this->_warnings[] = $this->l('The credentials you have entered are invalid. Please try again.');
                    $this->_warnings[] = $this->l('If you have lost these details, please log in to your HiPay account to retrieve it');
                }
            } catch (Exception $e) {
                // LOGS
                $this->logs->errorLogsHipay($e->getMessage());
                $this->_errors[] = $this->l('error on the webservice, try again later or contact the support HiPay');
            }
        } else {
            $this->_warnings[] = $this->l('The credentials you have entered are invalid. Please try again.');
            $this->_warnings[] = $this->l('If you have lost these details, please log in to your HiPay account to retrieve it');
        }
        return false;
    }

    protected function login($user_account)
    {
        $this->logs->logsHipay('---- >> function login');
        // get values login and password
        $ws_login = Tools::getValue('install_ws_login');
        $ws_password = Tools::getValue('install_ws_password');

        if ($ws_login && $ws_password) {
            try {
                // ctrl if login and password are crypted to md5
                $is_valid_login = (bool)Validate::isMd5($ws_login);
                $is_valid_password = (bool)Validate::isMd5($ws_password);

                if ($is_valid_login && $is_valid_password) {
                    $params = [
                        'ws_login' => $ws_login,
                        'ws_password' => $ws_password,
                    ];
                    $user_account = new HipayUserAccount($this);
                    $account = $user_account->getAccountInfos($params, false);
                    if (isset($account->code) && ($account->code == 0)) {
                        $this->setConfigHiPay('sandbox_mode', 0);
                        $this->setConfigHiPay('welcome_message_shown', 1);
                        if ($this->registerExistingAccount($account, $params)) {
                            $this->preloadConfig();
                            return true;
                        } else {
                            return false;
                        }
                    } else {
                        $this->_errors[] = $this->l('Authentication failed!');
                        $this->clearAccountData();
                        return false;
                    }
                } else {
                    $this->_warnings[] = $this->l('The credentials you have entered are invalid. Please try again.');
                    $this->_warnings[] = $this->l('If you have lost these details, please log in to your HiPay account to retrieve it');
                }
            } catch (Exception $e) {
                // LOGS
                $this->logs->errorLogsHipay($e->getMessage());
                $this->_errors[] = $this->l('error on the webservice, try again later or contact the support HiPay');
            }
        } else {
            $this->_warnings[] = $this->l('The credentials you have entered are invalid. Please try again.');
            $this->_warnings[] = $this->l('If you have lost these details, please log in to your HiPay account to retrieve it');
        }
        return false;
    }

    protected function majConfigurationByApi($user_account, $sandbox = 0)
    {
        $this->logs->logsHipay('---- >> function majConfigurationByApi');
        if (isset($this->configHipay->production_ws_login) && isset($this->configHipay->production_ws_password)) {
            // get values sandbox login and password
            $ws_login = (!$sandbox ? $this->configHipay->production_ws_login : $this->configHipay->sandbox_ws_login);
            $ws_password = (!$sandbox ? $this->configHipay->production_ws_password : $this->configHipay->sandbox_ws_password);
            $params = [];
            // true by default but to get user account info sandbox init to false
            $needLogin = true;

            if (!empty($ws_login)) {
                try {
                    $params = [
                        'ws_login' => $ws_login,
                        'ws_password' => $ws_password,
                    ];
                    $needLogin = false;

                    $user_account = new HipayUserAccount($this);
                    $account = $user_account->getAccountInfos($params, $needLogin, $sandbox);

                    if (isset($account->code) && ($account->code == 0)) {
                        if ($this->registerExistingAccount($account, $params, $sandbox)) {
                            // after get user info production go to get user info sandbox
                            if (!$sandbox) {
                                $this->majConfigurationByApi($user_account, true);
                            } else {
                                return true;
                            }
                        }
                    }
                } catch (Exception $e) {
                    // LOGS
                    $this->logs->errorLogsHipay($e->getMessage());
                    $this->_errors[] = $this->l('error on the webservice, try again later or contact the support HiPay');
                }
            }
            return false;
        } else {
            return true;
        }
    }

    protected function registerExistingAccount($account, $params = [], $sandbox = false)
    {
        $this->logs->logsHipay('---- >> function registerExistingAccount');
        // init variables
        $prefix = $sandbox ? 'sandbox' : 'production';
        $user_mail = '';
        $data = [];

        // init array config values by currency
        foreach ($account->websites as $websiteDefault) {
            $user_mail[$account->currency][$websiteDefault->website_id] = $websiteDefault->website_email;
            $data[$account->currency][$account->user_account_id][] = [
                'user_account_id' => $account->user_account_id,
                'website_id' => $websiteDefault->website_id,
                'user_mail' => $websiteDefault->website_email,
                'callback_url' => !empty($account->callback_url) ? $account->callback_url : '',
                'callback_salt' => !empty($account->callback_salt) ? $account->callback_salt : '',
            ];
        }
        if (isset($account->sub_accounts) && count($account->sub_accounts) > 0) {
            foreach ($account->sub_accounts as $sub_account) {
                if (isset($sub_account->websites) && count($sub_account->websites) > 0) {
                    foreach ($sub_account->websites as $website) {
                        $user_mail[$sub_account->currency][$website->website_id] = $website->website_email;
                        $data[$sub_account->currency][$sub_account->user_account_id][] = [
                            'user_account_id' => $sub_account->user_account_id,
                            'website_id' => $website->website_id,
                            'user_mail' => $website->website_email,
                            'callback_url' => !empty($sub_account->callback_url) ? $sub_account->callback_url : '',
                            'callback_salt' => !empty($sub_account->callback_salt) ? $sub_account->callback_salt : '',
                        ];
                    }
                }
            }
        }

        // init details for save configuration hipay in database
        $details = [
            $prefix => $data,
            'user_mail' => $user_mail,
            $prefix . '_ws_login' => $params['ws_login'],
            $prefix . '_ws_password' => $params['ws_password'],
            $prefix . '_status' => $account->activated,
            'identified' => $account->identified,
            'bank_info_validated' => $account->bank_info_validated,
            $prefix . '_entity' => $account->entity,
            'welcome_message_shown' => 1,
        ];

        if ($sandbox) {
            unset($details['user_mail']);
            unset($details[$prefix . '_status']);
            unset($details['identified']);
            unset($details['bank_info_validated']);
            unset($details[$prefix . '_entity']);
        }

        // save configuration hipay in database
        if (!$this->saveConfigurationDetails($details)) {
            // not clear if connection sandbox account
            if (!$sandbox) {
                $this->clearAccountData();
            }
            return false;
        }
        return true;
    }

    protected function saveConfigurationDetails($details)
    {
        foreach ($details as $name => $value) {
            $this->configHipay->$name = $value;
        }
        return $this->setAllConfigHiPay();
    }

    /**
     * Functions to init the configuration HiPay
     */
    public function getConfigHiPay()
    {
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();
        $confHipay = Configuration::get('HIPAY_CONFIG', null, $id_shop_group, $id_shop);

        // if config exist but empty, init new object for configHipay
        if (!$confHipay || empty($confHipay)) {
            $this->insertConfigHiPay();
        }

        // not empty in bdd and the config is stacked in JSON
        $result = Tools::jsonDecode(Configuration::get('HIPAY_CONFIG', null, $id_shop_group, $id_shop));
        return (object)$result;
    }

    public function setConfigHiPay($key, $value)
    {
        $this->logs->logsHipay('---- >> function setConfigHiPay');
        // Use this function only if you have just one variable to update
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();
        // the config is stacked in JSON
        $this->configHipay->$key = $value;
        if (Configuration::updateValue('HIPAY_CONFIG', Tools::jsonEncode($this->configHipay), false, $id_shop_group, $id_shop)) {
            return true;
        } else {
            throw new Exception($this->l('Update failed, try again.'));
        }
    }

    public function setAllConfigHiPay($objHipay = null)
    {
        $this->logs->logsHipay('---- >> function setAllConfigHiPay');
        // use this function if you have a few variables to update
        if ($objHipay != null) {
            $for_json_hipay = $objHipay;
        } else {
            $for_json_hipay = $this->configHipay;
        }
        // init multistore
        $id_shop = (int)$this->context->shop->id;
        $id_shop_group = (int)Shop::getContextShopGroupID();
        // the config is stacked in JSON
        if (Configuration::updateValue('HIPAY_CONFIG', Tools::jsonEncode($for_json_hipay), false, $id_shop_group, $id_shop)) {
            return true;
        } else {
            throw new Exception($this->l('Update failed, try again.'));
        }
    }

    public function insertConfigHiPay()
    {
        $this->logs->logsHipay('---- >> function insertConfigHiPay');
        // init objet config for HiPay
        $objHipay = new StdClass();

        // settings configuration
        $objHipay->user_mail = '';
        $objHipay->sandbox_mode = 0;
        $objHipay->sandbox_ws_login = '';
        $objHipay->sandbox_ws_password = '';
        $objHipay->production_ws_login = '';
        $objHipay->production_ws_password = '';
        $objHipay->welcome_message_shown = 0;
        $objHipay->proxyUrl = '';
        $objHipay->proxyLogin = '';
        $objHipay->proxyPassword = '';
        $objHipay->sandbox = '';
        $objHipay->production = '';
        $objHipay->selected = '';

        // payment button configuration
        $objHipay->payment_form_type = 1;
        $objHipay->manual_capture = 0;
        $objHipay->button_text_fr = 'Payer par carte bancaire';
        $objHipay->button_text_en = 'Pay by credit or debit card';
        $objHipay->button_images = 'default.png';
        $objHipay->mode_debug = 1;

        // information about the account
        $objHipay->production_entity = '';
        $objHipay->bank_info_validated = 0;
        $objHipay->identified = 0;
        $objHipay->production_status = 0;

        return $this->setAllConfigHiPay($objHipay);
    }

    /**
     * Preload configuration
     */
    protected function preloadConfig($sandbox = false)
    {
        $this->logs->logsHipay('-----> Start preloadConfig');
        $config = $this->configHipay;
        $prefix = (!$sandbox ? 'production' : 'sandbox');
        $rating = (!$sandbox ? 'rating_prod' : 'rating_sandbox');
        $login = $prefix . '_ws_login';
        $pwd = $prefix . '_ws_password';
        // get currencies
        $psCurrencies = $this->getCurrencies();

        // check if the config must be to reload
        if (!empty($config->$login)
            && !empty($config->$pwd)
            && isset($config->$prefix)
            && count($config->$prefix) > 0
            && isset($config->selected)
            && count($config->selected->currencies->$prefix) == 0
        ) {
            // preload selected informations
            $config->selected->$rating = 'ALL';

            foreach ($config->$prefix as $currency => $line) {
                if (array_key_exists($currency, $psCurrencies)) {
                    foreach ($line as $data) {
                        $config->selected->currencies->$prefix->$currency->accountID = $data[0]['user_account_id'];
                        $config->selected->currencies->$prefix->$currency->websiteID = $data[0]['website_id'];
                        break 1;
                    }
                }
            }
            // register in database
            $this->setConfigHiPay('selected', $config->selected);
        }

        $this->logs->logsHipay('-----> End preloadConfig');
    }

    /**
     * Save forms in tabs settings and payment buttons
     *
     */
    protected function saveSettingsConfiguration()
    {
        $this->logs->logsHipay('---- >> function saveSettingsConfiguration');
        /*
         * GET VALUES FORM
         */
        try {
            $sandbox_mode = Tools::getValue('settings_switchmode');
            $selected_prod_rating = Tools::getValue('settings_production_rating');
            $selected_sandbox_rating = Tools::getValue('settings_sandbox_rating');
            $selected_config = '';

            // get currencies
            $getCurrencies = $this->getCurrencies();

            // init dynamic values by currency
            $selectedCurrenciesProd = '';
            $selectedCurrenciesSandbox = '';
            foreach ($getCurrencies as $key => $value) {
                // production
                $getProductionAccountId = Tools::getValue('settings_production_' . $key . '_user_account_id');
                $getProductionWebsiteId = Tools::getValue('settings_production_' . $key . '_website_id');
                $selectedCurrenciesProd[$key] = [
                    'accountID' => (int)$getProductionAccountId,
                    'websiteID' => (int)$getProductionWebsiteId,
                ];

                if (Tools::getValue('settings_sandbox_' . $key . '_user_account_id')) {
                    // sandbox
                    $getSandboxAccountId = Tools::getValue('settings_sandbox_' . $key . '_user_account_id');
                    $getSandboxWebsiteId = Tools::getValue('settings_sandbox_' . $key . '_website_id');
                    $selectedCurrenciesSandbox[$key] = [
                        'accountID' => (int)$getSandboxAccountId,
                        'websiteID' => (int)$getSandboxWebsiteId,
                    ];
                }
            }

            // init array with all selected informations
            $selected_config = [
                'rating_prod' => $selected_prod_rating,
                'rating_sandbox' => $selected_sandbox_rating,
                'currencies' => [
                    'production' => $selectedCurrenciesProd,
                    'sandbox' => $selectedCurrenciesSandbox,
                ]
            ];

            // save configuration sandbox mode and select informations
            $this->setConfigHiPay('sandbox_mode', ($sandbox_mode ? 1 : 0));
            $this->setConfigHiPay('selected', $selected_config);

            $this->_successes[] = $this->l('Settings configuration saved successfully.');
            $this->logs->logsHipay(print_r($this->configHipay, true));
            return true;
        } catch (Exception $e) {
            // LOGS
            $this->logs->errorLogsHipay($e->getMessage());
            $this->_errors[] = $this->l($e->getMessage());
        }
        return false;
    }

    protected function savePaymentButtonConfiguration()
    {
        $this->logs->logsHipay('---- >> function savePaymentButtonConfiguration');
        /*
         * GET VALUES FORM
         */
        try {
            $button_text_fr = Tools::getValue('button_text_fr');
            $button_text_en = Tools::getValue('button_text_en');

            $this->configHipay->payment_form_type = (bool)Tools::getValue('payment_form_type');
            $this->configHipay->manual_capture = (bool)Tools::getValue('manual_capture');
            $this->configHipay->button_text_fr = (!empty($button_text_fr) ? $button_text_fr : 'Payer par carte bancaire');
            $this->configHipay->button_text_en = (!empty($button_text_en) ? $button_text_en : 'Pay by credit or debit card');
            $this->configHipay->button_images = Tools::getValue('button_images');

            if ($this->setAllConfigHiPay()) {
                $this->_successes[] = $this->l('Payment button configuration saved successfully.');
                $this->logs->logsHipay(print_r($this->configHipay, true));
                return true;
            }
        } catch (Exception $e) {
            // LOGS
            $this->logs->errorLogsHipay($e->getMessage());
            $this->_errors[] = $this->l($e->getMessage());
        }
        return false;
    }

    /**
     * Create an account merchant in production HiPay Direct
     * Create an account merchant in sandbox if production is ok
     */
    protected function createMerchantAccount()
    {
        $this->logs->logsHipay('---- >> function createMerchantAccount');

        try {
            // get email & control
            $email = Tools::getValue('register_user_email');
            $is_email = (bool)Validate::isEmail($email);

            // get firstname & name + control
            $first_name = Tools::getValue('register_firstname');
            $last_name = Tools::getValue('register_lastname');
            $is_valid_name = (bool)Validate::isName($first_name);
            $is_valid_name &= (bool)Validate::isName($last_name);

            // get captcha + control
            $captcha_id = (int)Tools::getValue('register_captcha_id');
            $captcha_code = Tools::getValue('register_captcha_img');
            $is_valid_code = (!preg_match('/^[a-zA-Z0-9]+$/', $captcha_code) ? false : true);

            // get cgv accept
            $cgv = Tools::getValue('register_cgv_on');

            // control validation fields
            if (empty($is_email) || !$is_email) {
                $this->_errors[] = $this->l('Email is incorrect');
            }
            if (empty($first_name) || empty($last_name) || !$is_valid_name) {
                $this->_errors[] = $this->l('Firstname and lastname are incorrect');
            }
            if (empty($captcha_code) || !$is_valid_code) {
                $this->_errors[] = $this->l('Security Code must be alphanumeric');
            }
            if ($cgv == null || empty($cgv)) {
                $this->_errors[] = $this->l('You have to accept with the terms and conditions');
            }
            if (is_array($this->_errors) && count($this->_errors) > 0) {
                $this->logs->errorLogsHipay(print_r($this->_errors, true));
                return false;
            }
            // init params
            $params = [
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'captcha_id' => $captcha_id,
                'captcha_code' => $captcha_code,

            ];
            // create account by API REST HiPay Direct
            $user_account = new HipayUserAccount($this);
            $result = $user_account->createAccount($params);
            if ($result) {
                // get currency default
                $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
                $currency_code = Tools::strtoupper($currency->iso_code);

                // Get infos WS and setting config
                $acc_id = $result->account_id;
                $this->configHipay->production[$currency_code][$acc_id][] = [
                    'user_account_id' => $result->account_id,
                    'website_id' => '',
                    'user_mail' => $result->email,
                ];
                $this->configHipay->sandbox_mode = 0;
                $this->configHipay->production_status = $result->status;
                $this->configHipay->production_ws_login = $result->wslogin;
                $this->configHipay->production_ws_password = $result->wspassword;
                $this->configHipay->production_entity = 'direct';

                $this->logs->logsHipay(print_r($this->configHipay, true));
                // save configuration
                return $this->setAllConfigHiPay();
            }
        } catch (Exception $e) {
            // LOGS
            $this->logs->errorLogsHipay($e->getMessage());
            $this->_errors[] = $this->l('error - captcha is not valid.');
        }
        return false;
    }

    protected function checkCodeValidation()
    {
        // init variables
        $user_mail = [];
        $data = [];

        try {
            $this->logs->logsHipay('---- >> function checkCodeValidation');
            // get currency default
            $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $currency_code = Tools::strtoupper($currency->iso_code);
            // get validation code
            $code = (int)Tools::getValue('code_validator');
            // this action active the account merchant
            $user_account = new HipayUserAccount($this);
            $result = $user_account->checkCodeValidation($code, $currency_code);

            if ($result && $result->status == 1) {
                $this->logs->logsHipay('---- >> account validated');
                // if activation ok, creation website_id
                $website = $user_account->createWebsite($currency_code);

                if ($website) {
                    $this->logs->logsHipay('---- >> website created for account_id ' . $website->account_id . ' with ID = ' . $website->website_id);
                    // init config with website
                    $email = Configuration::get('PS_SHOP_EMAIL');
                    $user_mail[$currency_code][$website->website_id] = $email;
                    $data[$currency_code][$website->account_id][] = [
                        'user_account_id' => $website->account_id,
                        'website_id' => $website->website_id,
                        'user_mail' => $email,
                    ];
                    $details = [
                        'production' => $data,
                        'user_mail' => $user_mail,
                    ];

                    // save configuration hipay in database
                    if ($this->saveConfigurationDetails($details)) {
                        $this->logs->logsHipay('---- >> save main account et website ');
                        // If multi currencies duplicate the account
                        $currencies = $this->getCurrencies();
                        $this->logs->logsHipay('---- >> create sub-account et website ');
                        foreach ($currencies as $key => $val) {
                            if ($currency_code != $key) {
                                $this->logs->logsHipay('---- >> duplicate main account to subaccount for the currency ' . $key);
                                // duplicate the account / website
                                $currency = [
                                    'currency' => $key,
                                ];
                                $sub_account = $user_account->duplicateByCurrency($currency);
                                if (!$sub_account) {
                                    $this->_errors[] = $this->l('error on the duplication of the account for the currency ') . $key;
                                } else {
                                    // add website for subaccount
                                    $website_sub = $user_account->createWebsite($key, $sub_account->subaccount_id, $sub_account->parent_account_id, $currency_code);
                                    if ($website_sub) {
                                        $user_mail[$key][$website_sub->website_id] = $email;
                                        $data[$key][$website_sub->account_id][] = [
                                            'user_account_id' => $website_sub->account_id,
                                            'website_id' => $website_sub->website_id,
                                            'user_mail' => $email,
                                        ];
                                        // save configuration hipay in database
                                        if (!$this->saveConfigurationDetails($details)) {
                                            $this->_errors[] = $this->l('error on the insert of the website for the currency ') . $key;
                                        }
                                    }
                                }
                            }
                        }
                        // active account
                        $this->setConfigHiPay('production_status', 1);
                        $this->preloadConfig();
                        return true;
                    } else {
                        $this->_errors[] = $this->l('error on the save of the configuration for the currency ') . $currency_code;
                        return false;
                    }
                } else {
                    $this->_errors[] = $this->l('error on the insert of the website for the currency ') . $currency_code;
                    return false;
                }
            } else {
                // error validation code is incorrect
                foreach ($this->configHipay->production->$currency_code as $key => $val) {
                    $this->context->smarty->assign('email', $this->configHipay->production->$currency_code->$key[0]['user_email']);
                    break;
                }
                $this->_errors[] = $this->l('Validation code is incorrect, try again.');
                return false;
            }
        } catch (Exception $e) {
            $this->logs->errorLogsHipay($e->getMessage());
            $this->_errors[] = $this->l('Error API - problem on the control validation code');
            return false;
        }
    }

    /**
     * Clear every single merchant account data
     * @return boolean
     */
    protected function clearAccountData()
    {
        $this->logs->logsHipay('---- >> function clearAccountData');
        Configuration::deleteByName('HIPAY_CONFIG');
        return true;
    }

    /**
     * various functions
     */
    public function objectToArray($data)
    {
        // convert the config object to array config
        // used for the templates for example
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = $this->objectToArray($value);
            }
            return $result;
        }
        return $data;
    }

    public function getImageButtons()
    {
        // init variables
        $files = [];

        // Get the button's list
        $dir = dirname(__FILE__) . '/views/img/payment_buttons';
        if (file_exists($dir)) {
            $dh = opendir($dir);
            while (false !== ($filename = readdir($dh))) {
                $files[] = $filename;
            }
            $images = preg_grep('/\.(jpg|jpeg|png|gif)(?:[\?\#].*)?$/i', $files);
            return $images;
        }
    }

    /**
     * Function for save / update / refund an order
     */
    public function getAdminOrderRefundBlockDetails($order)
    {
        $currency = new Currency($order->id_currency);
        $messages = Message::getMessagesByOrderId($order->id, true);
        $message = array_pop($messages);
        $details = Tools::jsonDecode($message['message']);
        $id_transaction = $this->getTransactionId($details);

        $params = http_build_query([
            'id_order' => $order->id,
            'id_transaction' => $id_transaction,
            'sandbox' => (isset($details->Environment) && ($details->Environment != 'PRODUCTION')),
        ]);

        $this->smarty->assign([
            'currency' => $currency,
            'details' => $details,
            'order' => $order,
            'transaction_id' => $id_transaction,
            'refund_link' => $this->context->link->getAdminLink('AdminHiPayRefund&' . $params, true),
        ]);

        return $details;
    }

    protected function saveOrderState($config, $color, $names, $setup)
    {
        $state_id = Configuration::get($config);

        if ((bool)$state_id == true) {
            $order_state = new OrderState($state_id);
        } else {
            $order_state = new OrderState();
        }

        $order_state->name = $names;
        $order_state->color = $color;

        foreach ($setup as $param => $value) {
            $order_state->{$param} = $value;
        }

        if ((bool)$state_id == true) {
            return $order_state->save();
        } elseif ($order_state->add() == true) {
            Configuration::updateValue($config, $order_state->id);
            @copy($this->local_path . 'logo.gif', _PS_ORDER_STATE_IMG_DIR_ . (int)$order_state->id . '.gif');

            return true;
        }
        return false;
    }

    public function getTransactionId($details)
    {
        foreach ($details as $key => $value) {
            $tmp_key = Tools::strtolower(str_replace(' ', false, $key));

            if (in_array($tmp_key, ['transactionid', 'idtransaction'])) {
                return $value;
            }
        }
        return false;
    }

    protected function isProductionOrder($details)
    {
        return (isset($details->Environment) && ($details->Environment == 'PRODUCTION'));
    }

    protected function isRefundAvailable($details)
    {
        $stack = array_values((array)$details);
        $refund_available = array_intersect($stack, static::$refund_available);

        return !empty($refund_available);
    }

    protected function orderAlreadyRefunded($order)
    {
        $history_states = $order->getHistory($this->context->language->id);

        $states = Configuration::getMultiple([
            'HIPAY_OS_PARTIALLY_REFUNDED',
            'HIPAY_OS_TOTALLY_REFUNDED',
        ]);

        foreach ($history_states as $state) {
            if ($key = array_search($state['id_order_state'], $states)) {
                $this->smarty->assign('state', $key);
                return $state;
            }
        }
        return false;
    }

    public function updateHiPayOrderStates()
    {
        $waiting_state_config = 'HIPAY_OS_WAITING';
        $waiting_state_color = '#4169E1';
        $waiting_state_names = [];

        $setup = [
            'delivery' => false,
            'hidden' => false,
            'invoice' => false,
            'logable' => false,
            'module_name' => $this->name,
            'send_email' => false,
        ];

        foreach (Language::getLanguages(false) as $language) {
            if (Tools::strtolower($language['iso_code']) == 'fr') {
                $waiting_state_names[(int)$language['id_lang']] = 'En attente d\'autorisation';
            } else {
                $waiting_state_names[(int)$language['id_lang']] = 'Waiting for authorization';
            }
        }

        $this->saveOrderState($waiting_state_config, $waiting_state_color, $waiting_state_names, $setup);

        $partial_state_config = 'HIPAY_OS_PARTIALLY_REFUNDED';
        $partial_state_color = '#EC2E15';
        $partial_state_names = [];

        foreach (Language::getLanguages(false) as $language) {
            if (Tools::strtolower($language['iso_code']) == 'fr') {
                $partial_state_names[(int)$language['id_lang']] = 'Partiellement remboursé';
            } else {
                $partial_state_names[(int)$language['id_lang']] = 'Partially refunded';
            }
        }

        $this->saveOrderState($partial_state_config, $partial_state_color, $partial_state_names, $setup);

        $total_state_config = 'HIPAY_OS_TOTALLY_REFUNDED';
        $total_state_color = '#EC2E15';
        $total_state_names = [];

        foreach (Language::getLanguages(false) as $language) {
            if (Tools::strtolower($language['iso_code']) == 'fr') {
                $total_state_names[(int)$language['id_lang']] = 'Totalement remboursé';
            } else {
                $total_state_names[(int)$language['id_lang']] = 'Totally refunded';
            }
        }

        $this->saveOrderState($total_state_config, $total_state_color, $total_state_names, $setup);
        return true;
    }
}

if (_PS_VERSION_ >= '1.7') {
    // version 1.7
    require_once(_PS_ROOT_DIR_ . '/modules/hipay_professional/hipay_professional-17.php');
} elseif (_PS_VERSION_ < '1.6') {
    // Version < 1.6
    Tools::displayError('The module HiPay Professional is not compatible with your PrestaShop');
}


require_once(dirname(__FILE__) . '/classes/forms/HipayForm.php');
require_once(dirname(__FILE__) . '/classes/webservice/HipayUserAccount.php');
require_once(dirname(__FILE__) . '/classes/webservice/HipayLogs.php');
require_once(dirname(__FILE__) . '/classes/webservice/HipayREST.php');

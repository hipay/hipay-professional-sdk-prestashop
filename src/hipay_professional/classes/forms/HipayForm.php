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

require_once(dirname(__FILE__) . '/HipayFormInputs.php');

class HipayForm extends HipayFormInputs
{
    protected $context = false;
    protected $helper = false;
    protected $module = false;

    public $name = false;
    public $configHipay;

    public $url_cgv = 'https://www.hipaydirect.com/terms/CGU_hipay_fr.pdf';

    public function __construct($module_instance)
    {
        // Requirements
        $this->context = Context::getContext();
        $this->module = $module_instance;
        $this->name = $module_instance->name;
        // init config hipay
        $this->configHipay = $module_instance->configHipay;

        // Form
        $this->helper = new HelperForm();

        $this->helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false);
        $this->helper->currentIndex .= '&' . http_build_query(array(
                'configure' => $this->module->name,
                'tab_module' => 'payments_gateways',
                'module_name' => $this->module->name,
            ));

        $this->helper->module = $this;
        $this->helper->show_toolbar = false;
        $this->helper->token = Tools::getAdminTokenLite('AdminModules');

        $this->helper->tpl_vars = array(
            'id_language' => $this->context->language->id,
            'languages' => $this->context->controller->getLanguages()
        );

        return $this->helper;
    }

    public function generateForm($form)
    {
        return $this->helper->generateForm($form);
    }

    /**
     * Login form
     */
    public function getLoginForm()
    {
        $form = array();
        $this->helper->tpl_vars['fields_value'] = $this->getLoginFormValues();

        // WS Login
        $text = 'You can find it on your HiPay account, section "Integration > API", under "Webservice access"';
        $form['form']['input'][] = $this->generateInputText(
            'install_ws_login',
            $this->module->l('WS Login', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl',
                'hint' => $this->module->l($text, 'HipayForm'),
                'required' => true,
            )
        );
        // WS Password
        $form['form']['input'][] = $this->generateInputText(
            'install_ws_password',
            $this->module->l('WS Password', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl',
                'hint' => $this->module->l($text, 'HipayForm'),
                'required' => true,
            )
        );
        // Button actions
        $form['form']['buttons'][] = $this->generateSubmitButton(
            $this->module->l('Reset', 'HipayForm'),
            array(
                'class' => 'pull-left',
                'name' => 'submitReset',
                'icon' => 'process-icon-eraser',
            )
        );
        $form['form']['buttons'][] = $this->generateSubmitButton(
            $this->module->l('Log in', 'HipayForm'),
            array(
                'name' => 'submitLogin',
                'icon' => 'process-icon-next',
            )
        );

        return $this->helper->generateForm(array($form));
    }

    public function getLoginFormValues()
    {
        $values = array();
        $values['install_ws_login'] = Tools::getValue('install_ws_login');
        $values['install_ws_password'] = Tools::getValue('install_ws_password');
    }

    /**
     * register form
     */
    public function getRegisterForm($captcha)
    {
        $form = array();
        $this->helper->tpl_vars['fields_value'] = $this->getRegisterFormValues($captcha);

        // email
        $form['form']['input'][] = $this->generateInputEmail(
            'register_user_email',
            $this->module->l('Email', 'HipayForm'),
            $this->module->l('Please, enter your email address in the field above', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl',
            )
        );
        // First name
        $form['form']['input'][] = $this->generateInputText(
            'register_firstname',
            $this->module->l('Firstname', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl',
                'hint' => $this->module->l('Please, enter your firstname in the field above', 'HipayForm'),
                'required' => true,
            )
        );
        // Last name
        $form['form']['input'][] = $this->generateInputText(
            'register_lastname',
            $this->module->l('Lastname', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl',
                'hint' => $this->module->l('Please, enter your lastname in the field above', 'HipayForm'),
                'required' => true,
            )
        );
        // CAPTCHA
        // init hidden field contain captcha_id
        $form['form']['input'][] = array(
            'type' => 'hidden',
            'value' => (!empty($captcha) ? $captcha->captcha_id : ''),
            'name' => 'register_captcha_id',
        );
        // init the field contains the captcha answer
        $html_suffix = '<div id="img-captcha">' . (!empty($captcha) ? $captcha->captcha_img : '');
        $html_suffix.= '</div><button type="button" class="btn captcha" name="reloadCaptcha" id="reload-captcha">';
        $html_suffix.= $this->module->l('New captcha', 'HipayForm') . '</button>';

        $form['form']['input'][] = $this->generateInputText(
            'register_captcha_img',
            $this->module->l('Insert the security code:', 'HipayForm'),
            array(
                'class' => 'fixed-width-xxl captcha-form',
                'hint' => $this->module->l('You must fill this captcha to validate the form', 'HipayForm'),
                'required' => true,
                'suffix' =>  $html_suffix,
            )
        );
        // init terms & conditions
        $label_cgv = '<a href="' . $this->url_cgv . '" target="_blank">' .
                        $this->module->l('I agree with the terms and conditions', 'HipayForm') . '</a>';
        $form['form']['input'][] = array(
            'type' => 'checkbox',
            'name' => 'register_cgv',
            'values' => array(
                'query' => array(
                    array(
                        'id' => 'on',
                        'name' => $label_cgv,
                        'val' => '1'
                    ),
                ),
                'id' => 'id',
                'name' => 'name'
            )
        );

        // BUTTON Reset & Save
        $form['form']['buttons'][] = $this->generateSubmitButton(
            $this->module->l('Reset', 'HipayForm'),
            array(
                'class' => 'pull-left',
                'name' => 'submitReset',
                'icon' => 'process-icon-eraser',
            )
        );
        $form['form']['buttons'][] = $this->generateSubmitButton(
            $this->module->l('Sign up', 'HipayForm'),
            array(
                'name' => 'submitRegister',
                'icon' => 'process-icon-next',
            )
        );

        return $this->helper->generateForm(array($form));
    }

    public function getRegisterFormValues($captcha)
    {
        $values = array();
        $email = Configuration::get('PS_SHOP_EMAIL');
        $values = array(
            'register_user_email' => Tools::getValue('register_user_email', $email),
            'register_firstname' => Tools::getValue('register_firstname'),
            'register_lastname' => Tools::getValue('register_lastname'),
            'register_captcha_id' => (!empty($captcha) ? $captcha->captcha_id : ''),
            'register_captcha_img' => '',
        );
        return $values;
    }

    /**
     * Global refund form
     */
    public function getRefundForm($order)
    {
        $form = array();
        $this->helper->tpl_vars['fields_value'] = $this->getRefundFormValues();

        $form = [
            'form' => [
                'buttons' => [
                    $this->generateSubmitButton(
                        $this->module->l('Refund', 'HipayForm'),
                        [
                            'name' => 'submitTotalRefund',
                            'icon' => 'process-icon-undo',
                            'value' => 'refresh',
                        ]
                    ),
                ],
            ]
        ];

        return $this->helper->generateForm([$form]);
    }

    public function getRefundFormValues()
    {
        return [];
    }
}

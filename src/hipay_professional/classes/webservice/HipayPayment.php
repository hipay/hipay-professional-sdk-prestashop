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

require_once(dirname(__FILE__) . '/HipayWS.php');
require_once(dirname(__FILE__) . '/HipayLocale.php');

class HipayPayment extends HipayWS
{
    protected $categories_domain = 'https://payment.hipay.com/';
    protected $categories_test_domain = 'https://test-payment.hipay.com/';
    protected $categories_url = 'order/list-categories/id/';
    protected $logs;

    protected $client_url = '/soap/payment-v2';

    /* SOAP method: codes */
    public function generate(&$results)
    {
        // init config
        $configHipay = $this->module->configHipay;
        $currency_id = $this->context->cart->id_currency;
        $currency = new Currency($currency_id);
        $isocode_currency = $currency->iso_code;

        // init logs
        $this->logs = new HipayLogs($this->module);

        $this->logs->requestLogs('##########################');
        $this->logs->requestLogs('# Début Request New Order');
        $this->logs->requestLogs('##########################');

        // control if auth ws is ok or not
        if ((!$configHipay->sandbox_mode
                && !$configHipay->selected->currencies->production->$isocode_currency->accountID)
            || ($configHipay->sandbox_mode
                && !$configHipay->selected->currencies->sandbox->$isocode_currency->accountID)
        ) {
            $this->logs->errorLogsHipay('ERROR : An error occurred while redirecting to the payment processor');
            die(Tools::displayError('An error occurred while redirecting to the payment processor'));
        }

        $this->logs->requestLogs('---- init auth ws production or sandbox');
        // init auth ws production or sandbox
        $sandboxOrNot = $configHipay->sandbox_mode;
        if (!$sandboxOrNot) {
            $website_account_id = $configHipay->selected->currencies->production->$isocode_currency->accountID;
            $website_id = $configHipay->selected->currencies->production->$isocode_currency->websiteID;
            $rating = $configHipay->selected->rating_prod;
            $website_email = $configHipay->user_mail->$isocode_currency->$website_id;
        } else {
            $this->logs->requestLogs('---- sandbox mode');
            $website_account_id = $configHipay->selected->currencies->sandbox->$isocode_currency->accountID;
            $website_id = $configHipay->selected->currencies->sandbox->$isocode_currency->websiteID;
            $rating = $configHipay->selected->rating_sandbox;
            $objAccountId = $configHipay->sandbox->$isocode_currency->$website_account_id;
            $website_email = $objAccountId[0]->user_mail;
        }

        // if no website return error
        if ($website_id == false || empty($website_id)) {
            die(Tools::displayError('An error occurred while redirecting to the payment processor'));
        }

        $locale = new HipayLocale($this->module);
        $free_data = $this->getFreeData();

        $cart_id = $this->context->cart->id;
        $secure_key = $this->context->customer->secure_key;
        $accept_url = $this->context->link->getModuleLink($this->module->name, 'confirmation', array('cart_id' => $cart_id, 'secure_key' => $secure_key), true);
        $callback_url = $this->context->link->getModuleLink($this->module->name, 'validation', array('cart_id' => $cart_id, 'secure_key' => $secure_key), true);
        $cancel_url = $this->context->link->getPageLink('order', null, null, array('step' => '3'), true);
        $decline_url = $this->context->link->getModuleLink($this->module->name, 'confirmation', array('cart_id' => $cart_id, 'failure' => true, 'secure_key' => $secure_key), true);
        $logo_url = $this->context->link->getMediaLink(_PS_IMG_ . Configuration::get('PS_LOGO'));

        $params = [
            'websiteId' => (int)$website_id,
            'amount' => $this->context->cart->getOrderTotal(),
            'categoryId' => $this->getCategory(),
            'currency' => $this->context->currency->iso_code,
            'customerEmail' => $this->context->customer->email,
            'customerIpAddress' => Tools::getRemoteAddr(),
            'description' => $this->context->cart->id,
            'merchantReference' => $this->context->cart->id,
            'emailCallback' => $website_email,
            'executionDate' => date('Y-m-d\TH:i:s'),
            'locale' => $locale->getCurrentLocaleCode(),
            'manualCapture' => ($configHipay->manual_capture ? 1 : 0),
            'rating' => $rating,
            'wsSubAccountId' => $website_account_id,
            'method' => (!$configHipay->payment_form_type ? 'iframe' : ''),

            // URLs
            'urlAccept' => $accept_url,
            'urlCallback' => $callback_url,
            'urlCancel' => $cancel_url,
            'urlDecline' => $decline_url,
            'urlLogo' => $logo_url,

            'freeData' => $free_data,
        ];

        $this->logs->requestLogs(print_r($params, true));

        $results = $this->executeQuery('generate', $params);

        $this->logs->requestLogs('##########################');
        $this->logs->requestLogs('# Fin Request New Order');
        $this->logs->requestLogs('##########################');

        // if method hosted redirection to hipay hosted page
        if ($configHipay->payment_form_type) {
            $this->logs->requestLogs('Redirection mode Hosted page');
            return ($results->generateResult->code === 0) ? Tools::redirect($results->generateResult->redirectUrl) : false;
        } else {
            // else method iframe
            $this->logs->requestLogs('Redirection mode iFrame');
            return ($results->generateResult->code === 0) ? $results->generateResult : false;
        }
    }

    protected function getFreeData()
    {
        $sandbox_mode = (bool)$this->module->configHipay->sandbox_mode;

        if ($sandbox_mode) {
            $ws_login = $this->module->configHipay->sandbox_ws_login;
        } else {
            $ws_login = $this->module->configHipay->production_ws_login;
        }

        return array(
            'item' => array(
                array('key' => 'cart_id', 'value' => $this->context->cart->id),
                array('key' => 'customer_id', 'value' => $this->context->customer->id),
                array('key' => 'secure_key', 'value' => $this->context->customer->secure_key),
                array('key' => 'token', 'value' => Tools::encrypt($ws_login . $this->context->cart->id)),
            ),
        );
    }

    protected function getCategory()
    {
        return 0;
    }
}

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

define('MAX_SIZE', 300000);  // max weight
define('WIDTH_MAX', 400);    // max width
define('HEIGHT_MAX', 400);   // max height

class AdminHiPayConfigController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->module->active) {
            $this->sendErrorRequest('Invalid request.');
        }
    }

    /**
     * Upload ajax images payment button
     */
    public function ajaxProcessImageButtons()
    {
        $tabExt = array('jpg', 'gif', 'png', 'jpeg');
        $infosImg = array();
        // Variables
        $extension = '';
        $message = '';
        $nomImage = '';
        $return = [];

        if ($_FILES != null) {
            $image = $_FILES['file'];
            // control if not empty
            if (!empty($image['name'])) {
                // get image extension
                $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                // check if extension is valid
                if (in_array(Tools::strtolower($extension), $tabExt)) {
                    // get resolution image
                    $infosImg = getimagesize($image['tmp_name']);
                    // check image type
                    if ($infosImg[2] >= 1 && $infosImg[2] <= 14) {
                        // check resolution and weight
                        if (($infosImg[0] <= WIDTH_MAX) && ($infosImg[1] <= HEIGHT_MAX) && (filesize($image['tmp_name']) <= MAX_SIZE)) {
                            // if error
                            if (isset($image['error'])
                                && UPLOAD_ERR_OK === $image['error']
                            ) {
                                // rename image
                                $nomImage = md5(uniqid()) . '.' . $extension;
                                // if ok execute upload
                                if (move_uploaded_file($image['tmp_name'], _PS_MODULE_DIR_ . 'hipay_professional/views/img/payment_buttons/' . $nomImage)) {
                                    $return = [
                                        'status' => true,
                                        'image' => $nomImage,
                                    ];
                                } else {
                                    $message = $this->module->l('Upload has failed, try again.', 'HipayConfig');
                                    $return = [
                                        'status' => false,
                                        'message' => $message,
                                    ];
                                }
                            } else {
                                $message = $this->module->l('Internal error - Upload has failed, try again.', 'HipayConfig');
                                $return = [
                                    'status' => false,
                                    'message' => $message,
                                ];
                            }
                        } else {
                            $message = $this->module->l('The dimensions or the size of the picture are not correct.', 'HipayConfig');
                            $return = [
                                'status' => false,
                                'message' => $message,
                            ];
                        }
                    } else {
                        $message = $this->module->l('Error on the type of the picture.', 'HipayConfig');
                        $return = [
                            'status' => false,
                            'message' => $message,
                        ];
                    }
                } else {
                    $message = $this->module->l('Error on the extension of the picture.', 'HipayConfig');
                    $return = [
                        'status' => false,
                        'message' => $message,
                    ];
                }
            } else {
                $message = $this->module->l('Sorry, no payment button selected !', 'HipayConfig');
                $return = [
                    'status' => false,
                    'message' => $message,
                ];
            }
        } else {
            $message = $this->module->l('Sorry, no payment button selected !', 'HipayConfig');
            $return = [
                'status' => false,
                'message' => $message,
            ];
        }
        die(Tools::jsonEncode($return));
    }

    /**
     * Duplicate an account HiPay + add new website
     */
    public function ajaxProcessDuplicate()
    {
        $return = [];
        try {
            // get currency default
            $currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
            $currency_code = Tools::strtoupper($currency->iso_code);

            // get val
            $currency = Tools::getValue('currency');
            $sandbox = (bool)Tools::getValue('sandbox');


            // duplicate the account / website
            $user_account = new HipayUserAccount($this->module);
            if ($sandbox) {
                $params = [
                    'currency' => $currency,
                    'ws_login' => $this->module->configHipay->sandbox_ws_login,
                    'ws_password' => $this->module->configHipay->sandbox_ws_password,
                ];
                $sub_account = $user_account->duplicateByCurrency($params, $sandbox);
            } else {
                $params = [
                    'currency' => $currency,
                ];
                $sub_account = $user_account->duplicateByCurrency($params);
            }

            if (!$sub_account) {
                $this->_errors[] = $this->module->l('error on the duplication of the account for the currency ', 'HipayConfig') . $currency;
            } else {
                // add website for subaccount
                $website_sub = $user_account->createWebsite($currency, $sub_account->subaccount_id, $sub_account->parent_account_id, $currency_code, $sandbox);
                if ($website_sub->code == 0) {
                    // reinit Currency permissions for the module HiPay
                    $this->module->setCurrencies();
                    $return = [
                        'status' => 1,
                        'message' => $this->module->l('Subaccount created for the currency ', 'HipayConfig') . $currency,
                    ];
                } else {
                    $return = [
                        'status' => 0,
                        'message' => $website_sub->message,
                    ];
                }
            }
        } catch (Exception $e) {
            $return = [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
        die(Tools::jsonEncode($return));
    }

    /**
     * Reload captcha
     */
    public function ajaxProcessReloadCaptcha()
    {
        $captcha = new HipayUserAccount($this->module);
        $return = $captcha->getCaptcha();
        // return the new captcha
        die(Tools::jsonEncode($return));
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}

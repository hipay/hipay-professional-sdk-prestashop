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

class AdminHiPayCaptureController extends ModuleAdminController
{
    protected $logs;
    protected $configHipay;

    public function __construct()
    {
        parent::__construct();

        if (!$this->module->active) {
            $this->sendErrorRequest('Invalid request.');
        }

        // init logs
        $this->logs = new HipayLogs($this->module);
        // init config
        $this->configHipay = $this->module->configHipay;

        require_once _PS_ROOT_DIR_ . _MODULE_DIR_ . $this->module->name . '/classes/webservice/HipayCapture.php';
    }

    public function init()
    {
        $return = [];
        try {
            $this->logs->logsHipay('##################');
            $this->logs->logsHipay('# START Capture');
            $this->logs->logsHipay('##################');

            // get values
            $sandbox = (bool)Tools::getValue('sandbox');
            $id_order = (int)Tools::getValue('id_order');
            $this->logs->logsHipay('---- sandbox : ' . (int)$sandbox);
            $this->logs->logsHipay('---- id_order: ' . $id_order);

            // init order object
            $order = new Order($id_order);

            // init transaction and id_cart
            $messages = Message::getMessagesByOrderId($order->id, true);
            $message = array_pop($messages);
            $details = Tools::jsonDecode($message['message']);
            $this->logs->logsHipay('---- details : ');
            $this->logs->logsHipay(print_r($details, true));

            // init Account ID and WS Login/password
            $currency = new Currency($order->id_currency);
            $iso_code = $currency->iso_code;
            $sandbox_mode = ($sandbox == 1 ? 'sandbox' : 'production');
            $accountID = $this->configHipay->selected->currencies->$sandbox_mode->$iso_code->accountID;
            $login = $sandbox_mode . '_ws_login';
            $password = $sandbox_mode . '_ws_password';
            $ws_login = $this->configHipay->$login;
            $ws_password = $this->configHipay->$password;

            // init params for capture
            $transaction_id = $this->module->getTransactionId($details);
            $merchant_ref = $order->id_cart;
            $params = [
                'transaction_public_id' => $transaction_id,
                'merchant_reference' => $merchant_ref,
                'php-auth-subaccount-id' => $accountID,
                'ws_login' => $ws_login,
                'ws_password' => $ws_password,
            ];
            $this->logs->logsHipay('---- params send to capture : ');
            $this->logs->logsHipay(print_r($params, true));

            // request to confirm a capture order
            $capture = new HipayCapture($this->module);
            $response = $capture->captureOrder($params, false, $sandbox);

            $this->logs->logsHipay('---- response : ');
            $this->logs->logsHipay(print_r($response, true));

            // return the response
            if ($response->code == 0) {
                $return = [
                    'status' => 1,
                    'message' => $this->module->l('Capture ok, your order status is in progress. Please, reload your page after few seconds.'),
                ];
            } else {
                $return = [
                    'status' => 0,
                    'message' => $response->message,
                ];
            }
        } catch (Exception $e) {
            $return = [
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
        die(Tools::jsonEncode($return));
    }

    protected function sendErrorRequest($response)
    {
        http_response_code(406);

        $output = Tools::jsonEncode($response);

        die($output);
    }
}

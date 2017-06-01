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

class Hipay_ProfessionalValidationModuleFrontController extends ModuleFrontController
{
    public $configHipay;
    public $logs;

    public function postProcess()
    {
        if ($this->module->active == false) {
            die;
        }

        $this->logs = new HipayLogs($this->module);

        $this->logs->callbackLogs('##########################');
        $this->logs->callbackLogs('START Validation Callback');
        $this->logs->callbackLogs('##########################');

        if (Tools::getValue('xml')) {
            // init and treatment value xml
            $xml = Tools::getValue('xml');
            $order = Tools::jsonDecode(Tools::jsonEncode((array)simplexml_load_string($xml)), 1);
            $cart_id = (int)$order['result']['merchantDatas']['_aKey_cart_id'];
            $customer_id = (int)$order['result']['merchantDatas']['_aKey_customer_id'];
            $secure_key = $order['result']['merchantDatas']['_aKey_secure_key'];
            $amount = (float)$order['result']['origAmount'];

            // Lock SQL - SELECT FOR UPDATE in cart_id
            #################################################################
            #################################################################
            #################################################################
            $sql = 'begin;';
            $sql .= 'SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = ' . (int)$cart_id . ' FOR UPDATE;';

            if (!Db::getInstance()->execute($sql)) {
                $this->logs->errorLogsHipay('----> Bad LockSQL initiated, Lock could not be initiated for id_cart = ' . $cart_id);
                die('Lock not initiated');
            } else {
                $this->logs->callbackLogs('----> Treatment is locked for the id_cart = ' . $cart_id);
            }

            // Log data send to validation
            $this->logs->callbackLogs('data for validation');
            $this->logs->callbackLogs(print_r(
                array(
                    $xml,
                    $order,
                    $cart_id,
                    $customer_id,
                    $secure_key,
                    $amount,
                ),
                true
            ));

            $this->context->cart = new Cart((int)$cart_id);
            $this->context->customer = new Customer((int)$customer_id);
            $this->context->currency = new Currency((int)$this->context->cart->id_currency);
            $this->context->language = new Language((int)$this->context->customer->id_lang);

            $return = $this->registerOrder($order, $cart_id, $amount, $secure_key);
            $this->logs->callbackLogs('----> END registerOrder()');

            // FIN du lock SQL - par un commit SQL
            #################################################################
            #################################################################
            #################################################################
            $sql = 'commit;';
            if (!Db::getInstance()->execute($sql)) {
                $this->logs->errorLogsHipay('----> Bad LockSQL initiated, Lock could not be initiated for id_cart = ' . $cart_id);
                die('Lock not initiated');
            } else {
                $this->logs->callbackLogs('----> Treatment is unlocked for the id_cart = ' . $cart_id);
            }

            return $return;
        }

        return $this->displayError('An error occurred while processing payment');
    }

    protected function displayError($message)
    {
        $html = '<a href="' . $this->context->link->getPageLink('order', null, null, 'step=3') . '">';
        $html .= $this->module->l('Order') . '</a>';
        $html .= '<span class="navigation-pipe">&gt;</span>' . $this->module->l('Error');

        $this->context->smarty->assign(
            'path',
            $html
        );

        $this->errors[] = $this->module->l($message);

        $this->context->smarty->assign([
            'errors' => $this->errors,
        ]);

        return $this->setTemplate((_PS_VERSION_ >= '1.7' ? 'module:' . $this->module->name . '/views/templates/front/' : '') . 'error.tpl');
    }

    protected function registerOrder($order, $cart_id, $amount, $secure_key)
    {
        // LOGS
        $this->logs->callbackLogs('----> START registerOrder()');
        // ----
        if ($this->isValidSignature($order) === true) {
            $status = trim(Tools::strtolower($order['result']['status']));
            $operation = trim(Tools::strtolower($order['result']['operation']));
            $currency = $this->context->currency;

            // LOGS
            $this->logs->callbackLogs('Status = ' . $status);
            $this->logs->callbackLogs('$currency = ' . $currency->iso_code);
            // ----

            switch ($status) {
                case 'ok':
                    if ($operation == 'authorization') {
                        $id_order_state = (int)Configuration::get('HIPAY_OS_WAITING');
                    } else {
                        $id_order_state = (int)Configuration::get('PS_OS_PAYMENT');
                    }
                    break;
                case 'nok':
                    $id_order_state = (int)Configuration::get('PS_OS_ERROR');
                    break;
                case 'cancel':
                    $id_order_state = (int)Configuration::get('PS_OS_CANCELED');
                    break;
                case 'waiting':
                    $id_order_state = (int)Configuration::get('HIPAY_OS_WAITING');
                    break;
                default:
                    $id_order_state = (int)Configuration::get('PS_OS_ERROR');
                    break;
            }

            // LOGS
            $this->logs->callbackLogs('id_order_state = ' . $id_order_state);
            // ----

            $return = $this->placeOrder($order['result'], $id_order_state, $cart_id, $currency, $amount, $secure_key);

            // LOGS
            $this->logs->callbackLogs('----> END placeOrder()');
            // ----

            return $return;
        } else {
            // LOGS
            $this->logs->errorLogsHipay('Token or signature are not valid');
            // ----
            return false;
        }
    }

    protected function isValidSignature($order)
    {
        // init variables
        $callback_salt = '';
        $accountId = '';
        $websiteId = '';
        $accountInfo = '';

        if (isset($order['result']) == false) {
            return false;
        } elseif ((isset($order['result']['status']) == false) || (isset($order['result']['merchantDatas']) == false)) {
            return false;
        }

        // init config HiPay
        $this->configHipay = $this->module->configHipay;

        $xml = new SimpleXMLElement(Tools::getValue('xml'));
        $currency = $order['result']['origCurrency'];
        $sandbox_mode = (bool)$this->configHipay->sandbox_mode;
        // get callback_salt for the accountID, websiteID and the currency of the transaction
        if ($sandbox_mode) {
            $accountId = $this->configHipay->selected->currencies->sandbox->$currency->accountID;
            $websiteId = $this->configHipay->selected->currencies->sandbox->$currency->websiteID;
            $accountInfo = $this->configHipay->sandbox->$currency->$accountId;
        } else {
            $accountId = $this->configHipay->selected->currencies->production->$currency->accountID;
            $websiteId = $this->configHipay->selected->currencies->production->$currency->websiteID;
            $accountInfo = $this->configHipay->production->$currency->$accountId;
        }
        $this->logs->callbackLogs('accountID:' . $accountId . ' / websiteId:' . $websiteId);
        foreach ($accountInfo as $value) {
            if ($value->website_id == $websiteId) {
                $callback_salt = $value->callback_salt;
                break;
            }
        }

        // init MD5
        $md5 = hash('md5', $xml->result->asXml() . $callback_salt);

        // Logs
        $this->logs->callbackLogs($xml->result->asXml() . $callback_salt);
        $this->logs->callbackLogs('currency : ' . $currency);
        $this->logs->callbackLogs('accountID:' . $accountId . ' / websiteId:' . $websiteId);
        $this->logs->callbackLogs('callback_salt : ' . $callback_salt);
        $this->logs->callbackLogs('md5 : ' . $md5 . ' == md5content : ' . $order['md5content']);

        if ($md5 == $order['md5content']) {
            return true;
        }

        return false;
    }

    protected function placeOrder($result, $id_order_state, $cart_id, $currency, $amount, $secure_key)
    {
        // LOGS
        $this->logs->callbackLogs('----> START placeOrder()');
        // ----

        $order_id = (int)Order::getOrderByCartId($cart_id);

        if ((bool)$order_id != false) {
            // LOGS
            $this->logs->callbackLogs('Treatment an existing order');
            // ----

            $order = new Order($order_id);

            if ((int)$order->getCurrentState() == (int)Configuration::get('HIPAY_OS_WAITING')) {
                // LOGS
                $this->logs->callbackLogs('If current status order = HIPAY_OS_WAITING Then change status with this id_status = ' . $id_order_state);
                // ----
                $this->logs->callbackLogs(
                    print_r(
                        $result,
                        true
                    )
                );

                $details = [
                    'operation' => $result['operation'],
                    'status' => $result['status'],
                    'transaction_id' => $result['transid'],
                    'date' => $result['date'] . ' ' . $result['time'],
                    'amount' => $result['origAmount'] . ' ' . $result['origCurrency'],
                ];

                $message = new Message();
                $message->message = Tools::jsonEncode($details);
                $message->id_order = (int)$order_id;
                $message->private = 1;
                $message->add();

                $order_history = new OrderHistory();

                $order_history->id_order = $order_id;
                $order_history->id_order_state = $id_order_state;
                $order_history->changeIdOrderState($id_order_state, $order_id);

                if (!$order_history->add()) {
                    //LOG
                    $this->logs->callbackLogs('--------------- Error changeIdOrderState');
                    return false;
                } else {
                    $sql = "
				    UPDATE `" . _DB_PREFIX_ . "order_payment` SET
	                    `transaction_id` = '" . $result['transid'] . "',
	                    `card_brand` = '" . $result['paymentMethod'] . "'
                    WHERE `order_reference`= '" . $order->reference . "'
                    AND `payment_method` = '" . $this->module->displayName . "';";

                    if (!Db::getInstance()->execute($sql)) {
                        //LOG
                        $this->logs->callbackLogs('--------------- Update transaction Error');
                        return false;
                    }
                    return true;
                }
            }

            // LOGS
            $this->logs->callbackLogs('getCurrentState ' . $order->getCurrentState() . ' != HIPAY_OS_WAITING ' . Configuration::get('HIPAY_OS_WAITING'));
            // ----
            return $this->displayError('An error occurred while saving transaction details');
        } else {
            if ($id_order_state != (int)Configuration::get('PS_OS_ERROR')) {
                // LOGS
                $this->logs->callbackLogs('Treatment status = ' . $id_order_state);
                // ----

                $payment_method = $result['paymentMethod'];
                $transaction_id = $result['transid'];

                $extra_vars = ['transaction_id' => Tools::safeOutput($transaction_id)];

                // init config HiPay
                $this->configHipay = $this->module->configHipay;

                $sandbox_mode = (bool)$this->configHipay->sandbox_mode;

                $message = Tools::jsonEncode([
                    "Environment" => $sandbox_mode ? 'SANDBOX' : 'PRODUCTION',
                    "Payment method" => Tools::safeOutput($payment_method),
                    "Transaction ID" => Tools::safeOutput($transaction_id),
                ]);
            } else {
                // LOGS
                $this->logs->callbackLogs('Treatment status = ERROR');
                // ----
                $error_details = Tools::safeOutput(print_r($result, true));
                $this->logs->callbackLogs(
                    print_r(
                        $error_details,
                        true
                    )
                );
                $message = Tools::jsonEncode($error_details);
                return $this->displayError('An error occurred while saving transaction details');
            }

            // LOGS
            $this->logs->callbackLogs('Validate order');
            $this->logs->callbackLogs(
                print_r(
                    array(
                        $cart_id,
                        $id_order_state,
                        $amount,
                        $this->module->displayName,
                        $message,
                        $extra_vars,
                        (int)$currency->id,
                        $secure_key
                    ),
                    true
                )
            );
            // ----

            // force cart's shop for treatment
            $shop_id = $this->context->cart->id_shop;
            $shop = new Shop($shop_id);
            Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);
            $this->logs->callbackLogs('ID Shop = ' . $shop_id);

            try {
                $this->module->validateOrder(
                    $this->context->cart->id,
                    (int)$id_order_state,
                    (float)$amount,
                    $this->module->displayName,
                    $message,
                    $extra_vars,
                    $this->context->cart->id_currency,
                    false,
                    $secure_key,
                    $shop
                );
                $this->logs->callbackLogs('Order created');
            } catch (Exception $e) {
                $this->logs->callbackLogs('Order is not created');
                $this->logs->callbackLogs($e->getMessage());
                return false;
            }
            return true;
        }
    }
}

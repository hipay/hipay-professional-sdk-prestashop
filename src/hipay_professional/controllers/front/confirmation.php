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

class Hipay_ProfessionalConfirmationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $failure = Tools::getIsset('failure');
        $cart_id = Tools::getValue('cart_id');
        $secure_key = Tools::getValue('secure_key');

        // Lock SQL - SELECT FOR UPDATE in cart_id
        #################################################################
        #################################################################
        #################################################################
        $sql = 'begin;';
        $sql .= 'SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart = ' . (int)$cart_id . ' FOR UPDATE;';

        if (!Db::getInstance()->execute($sql)) {
            die('Lock not initiated');
        }

        if ($failure) {
            Tools::redirect('index.php?controller=order&step=1');
        } else {
            $cart = new Cart((int)$cart_id);

            if (_PS_VERSION_ >= '1.7.1.0') {
                $order_id = Order::getIdByCartId($cart->id);
            } else {
                $order_id = Order::getOrderByCartId($cart->id);
            }

            $customer = new Customer($cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                Tools::redirect('index.php?controller=order&step=1');
            }

            if (($order_id) && ($secure_key == $customer->secure_key)) {
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . (int)$order_id . '&key=' . $customer->secure_key);
            } else {
                $currency = $this->context->currency;
                $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

                $shop_id = $this->context->cart->id_shop;
                $shop = new Shop($shop_id);
                Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);

                $this->module->validateOrder(
                    (int)$cart->id,
                    (int)Configuration::get('HIPAY_OS_WAITING'),
                    $total,
                    $this->module->displayName,
                    $this->module->l('Order created by HiPay after success payment.'),
                    array(),
                    (int)$currency->id,
                    false,
                    $customer->secure_key,
                    $shop
                );

                // FIN du lock SQL - par un commit SQL
                #################################################################
                #################################################################
                #################################################################
                $sql = 'commit;';
                if (!Db::getInstance()->execute($sql)) {
                    die('Lock not initiated');
                }

                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            }
        }
    }
}

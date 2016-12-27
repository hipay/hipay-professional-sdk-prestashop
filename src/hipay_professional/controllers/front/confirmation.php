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
    public function postProcess()
    {
        if ((Tools::isSubmit('cart_id') == false) || (Tools::isSubmit('secure_key') == false)) {
            return $this->displayConfirmationError();
        }

        $failure = Tools::getIsset('failure');

        $cart_id = Tools::getValue('cart_id');
        $secure_key = Tools::getValue('secure_key');

        $cart = new Cart((int)$cart_id);
        $order_id = Order::getOrderByCartId((int)$cart->id);

        if ($failure) {
            return $this->displayConfirmationError();
        } elseif ($order_id) {
            return $this->displayConfirmation($cart, $order_id, $secure_key);
        } else {
            return $this->waitForConfirmation($cart_id, $secure_key);
        }
    }

    protected function displayConfirmation($cart, $order_id, $secure_key)
    {
        $customer = new Customer((int)$cart->id_customer);

        if (($order_id) && ($secure_key == $customer->secure_key)) {
            $params = http_build_query([
                'id_cart' => $cart->id,
                'id_module' => $this->module->id,
                'id_order' => $order_id,
                'key' => $customer->secure_key,
            ]);

            return Tools::redirect('index.php?controller=order-confirmation&' . $params);
        }
    }

    protected function displayConfirmationError()
    {
        $this->errors = Tools::displayError($this->module->l('An error occurred. Please contact the merchant for more details.', 'HipayConfig'));

        return Tools::redirect($this->context->link->getPageLink('order', null, null, array('step' => '3'), true));
    }

    protected function waitForConfirmation($cart_id, $secure_key)
    {
        $params = ['cart_id' => $cart_id, 'secure_key' => $secure_key];

        $this->context->controller->addJS(_MODULE_DIR_ . '/' . $this->module->name . '/views/js/confirmation.js');

        $this->context->smarty->assign([
            'img_dir' => _MODULE_DIR_ . '/' . $this->module->name . '/views/img',
            'ajax_url' => $this->context->link->getModuleLink($this->module->name, 'check', $params, true),
        ]);

        return $this->setTemplate((_PS_VERSION_ >= '1.7' ? 'module:' . $this->module->name . '/views/templates/front/17' : '16') . '_waiting_validation.tpl');
    }
}

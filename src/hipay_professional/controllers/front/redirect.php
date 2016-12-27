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

class Hipay_ProfessionalRedirectModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $currency = $this->context->currency;

        if ($this->module->isSupportedCurrency($currency->iso_code) == false) {
            return $this->displayError('The currency is not supported');
        }

        $this->generatePayment();
    }

    protected function generatePayment()
    {
        require_once(dirname(__FILE__) . '/../../classes/webservice/HipayPayment.php');

        $results = null;
        $payment = new HipayPayment($this->module);

        if ($payment->generate($results) == false) {
            $description = $results->generateResult->description;
            $this->displayError('An error occurred while getting transaction informations', $description);
        } else {
            // ctrl if iframe
            if (!$this->module->configHipay->payment_form_type) {
                $this->context->smarty->assign(array(
                    'iframe_url' => $results->generateResult->redirectUrl,
                    'cart_id' => $this->context->cart->id,
                    'currency' => $this->context->currency->iso_code,
                    'amount' => $this->context->cart->getOrderTotal(true, Cart::BOTH),
                    'nbProducts' => $this->context->cart->nbProducts(),
                ));
                // show the iframe page in Prestashop
                $path = (_PS_VERSION_ >= '1.7' ? 'module:' . $this->module->name . '/views/templates/front/17' : '16') . '_iframe.tpl';
                return $this->setTemplate($path);
            }
        }
    }

    protected function displayError($message, $description = false)
    {
        $this->context->smarty->assign('path', '
        <a href="' . $this->context->link->getPageLink('order', null, null, 'step=3') . '">' . $this->module->l('Order') . '</a>
        <span class="navigation-pipe">&gt;</span>' . $this->module->l('Error'));

        $this->errors[] = $this->module->l($message);

        if ($description != false) {
            $this->errors[] = $description;
        }

        $this->context->smarty->assign([
            'errors' => $this->errors,
        ]);

        return $this->setTemplate((_PS_VERSION_ >= '1.7' ? 'module:' . $this->module->name . '/views/templates/front/' : '') . 'error.tpl');
    }
}

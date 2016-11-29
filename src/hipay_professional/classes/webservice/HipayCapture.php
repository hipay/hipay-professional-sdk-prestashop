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

class HipayCapture extends HipayREST
{
    protected $client_url = 'transaction/confirm';

    public function __construct($module_instance)
    {
        parent::__construct($module_instance);
    }

    /* REST method: capture */
    public function captureOrder($params = [], $needLogin = true, $needSandboxLogin = false)
    {
        $result = $this->sendApiRequest($this->client_url, 'post', $needLogin, $params, $needSandboxLogin);

        return $result;
    }
}

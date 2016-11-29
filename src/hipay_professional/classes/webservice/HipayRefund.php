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

class HipayRefund extends HipayWS
{
    protected $client_url = '/soap/refund-v2';

    /* SOAP method: card */
    public function process($params, $sandbox = false)
    {
        // $params = [
        //     'amount'                => 4,
        //     'transactionPublicId'   => $id_transaction,
        // ];

        return $this->executeQuery('card', $params, $sandbox);
    }
}

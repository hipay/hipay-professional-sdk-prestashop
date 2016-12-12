{**
* 2016 HiPay
*
* NOTICE OF LICENSE
*
*
* @author    HiPay <support.wallet@hipay.com>
* @copyright 2016 HiPay
* @license   https://github.com/hipay/hipay-wallet-sdk-prestashop/blob/master/LICENSE.md
*
*}

<section>
    <p>
    {if $configHipay.button_image != 'no_image'}
        <img src="{$smarty.server.HTTP_HOST}{$smarty.server.REQUEST_URI}{$configHipay.button_image}" />
    {/if}
    </p>
</section>

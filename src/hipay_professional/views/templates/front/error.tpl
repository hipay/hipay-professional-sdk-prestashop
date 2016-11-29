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

<div>
    <h3>{l s='An error occurred' mod='hipay_professional'}:</h3>
    <ul class="alert alert-danger">
        {foreach from=$errors item='error'}
            <li>{$error|escape:'htmlall':'UTF-8'}.</li>
        {/foreach}
    </ul>
</div>

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

<form action="{$action|escape:'htmlall':'UTF-8'}" id="payment-form">

    <p>
        <label>{l s='Card number' mod='hipay_professional'}</label>
        <input type="text" size="20" autocomplete="off" name="card-number">
    </p>

    <p>
        <label>{l s='Firstname' mod='hipay_professional'}</label>
        <input type="text" autocomplete="off" name="firstname">
    </p>

    <p>
        <label>{l s='Lastname' mod='hipay_professional'}</label>
        <input type="text" autocomplete="off" name="lastname">
    </p>

    <p>
        <label>{l s='CVC' mod='hipay_professional'}</label>
        <input type="text" size="4" autocomplete="off" name="card-cvc">
    </p>

    <p>
        <label>{l s='Expiration (MM/AAAA)' mod='hipay_professional'}</label>
        <select id="month" name="card-expiry-month">
            {foreach from=$months item=month}
                <option value="{$month|escape:'htmlall':'UTF-8'}">{$month|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
        <span> / </span>
        <select id="year" name="card-expiry-year">
            {foreach from=$years item=year}
                <option value="{$year|escape:'htmlall':'UTF-8'}">{$year|escape:'htmlall':'UTF-8'}</option>
            {/foreach}
        </select>
    </p>
</form>

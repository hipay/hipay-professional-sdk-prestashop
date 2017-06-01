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
{capture name=path}{l s='HiPay payment.' mod='hipay_professional'}{/capture}

<h2>{l s='Order summary' mod='hipay_professional'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="warning">{l s='Your shopping cart is empty.' mod='hipay_professional'}</p>
{else}
    <h3>{l s='HiPay payment.' mod='hipay_professional'}</h3>
    <section>
        <iframe src="{$iframe_url|escape:'html':'UTF-8'}" width="100%" height="650"></iframe>
    </section>
{/if}
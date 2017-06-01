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

<div id="refund" class="panel">
    <div class="panel-heading">
        <i class="icon-undo"></i> {l s='Refund' mod='hipay_professional'}
    </div>

    {if ($details)}
        <div class="panel-body well well-sm">
            <dl class="dl-horizontal">
                {foreach from=$details key=key item=value}
                    <dt>{$key|escape:'htmlall':'UTF-8'}:</dt>
                    <dd>{$value|escape:'htmlall':'UTF-8'}</dd>
                {/foreach}
            </dl>
        </div>
    {/if}

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success">
                {if ($state == 'HIPAY_OS_PARTIALLY_REFUNDED')}
                    {l s='Order already partially refunded.' mod='hipay_professional'}
                {else}
                    {l s='Order already totally refunded.' mod='hipay_professional'}
                {/if}
            </div>
        </div>
    </div>
</div>

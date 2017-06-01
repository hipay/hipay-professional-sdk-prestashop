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

    <input type="hidden" name="refund_amount_max" id="refund-amount-max"
           value="{$order->total_paid_tax_incl|escape:'htmlall':'UTF-8'|string_format:'%.2f'}">

    <span class="hidden" id="refund-link">{$refund_link|urlencode}</span>

    <span class="hidden"
          id="partial-refund-confirmation-msg">{l s='You are about to refund this order partially. Are you sure?' mod='hipay_professional'}</span>
    <span class="hidden"
          id="total-refund-confirmation-msg">{l s='You are about to refund this order totally. Are you sure?' mod='hipay_professional'}</span>

    <span class="hidden"
          id="refund-amount-max-alert-msg">{l s='The refund amount cannot be greater than the total amount of the order.' mod='hipay_professional'}</span>
    <span class="hidden"
          id="refund-amount-empty-msg">{l s='The amount cannot be empty or equal to "0".' mod='hipay_professional'}</span>

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
        <div class="col-xs-12">
            <div class="alert alert-warning">
                {l s='Please, be careful, only one refund is possible per order.' mod='hipay_professional'}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-5 text-left">
            <a href="javascript:;" class="btn btn-primary" id="partial-refund-button">
                <i class="icon icon-undo"></i> {l s='Partial refund' mod='hipay_professional'}
            </a>
        </div>

        <div id="partial-refund-details" class="col-xs-6 col-xs-offset-1 collapse">
            <div class="col-xs-8">
                <div class="input-group">
                    <input type="text" name="patial_refund_amount" id="partial-refund-amount">
                    <span class="input-group-addon">
                        {$currency->sign|escape:'htmlall':'UTF-8'} ({$currency->iso_code|escape:'htmlall':'UTF-8'})
                    </span>
                </div>
            </div>

            <div class="col-xs-4">
                <div class="input-group">
                    <a href="javascript:;" class="btn btn-default" id="partial-refund-process">
                        <i class="icon icon-check"></i> Process
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <br/>
    </div>

    <div class="row">
        <div class="col-xs-5 text-left">
            <a href="javascript:;" class="btn btn-primary" id="total-refund-button">
                <i class="icon icon-exchange"></i> {l s='Total refund' mod='hipay_professional'}
            </a>
        </div>
    </div>
</div>

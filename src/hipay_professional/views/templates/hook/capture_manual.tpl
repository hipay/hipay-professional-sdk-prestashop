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
        <i class="icon-undo"></i> {l s='Capture HiPay Professional' mod='hipay_professional'}
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
        <div class="col-xs-12">
            <div class="alert alert-warning" id="hipay-warning" style="display:none;">
                {l s='Your capture is in progress, please check your email for more informations.' mod='hipay_professional'}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="input-group">
                <button type="button" class="btn btn-success btn-lg"
                        id="capture">{l s='Capture the transaction' mod='hipay_professional'}</button>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="input-group">
                <div id="hipay-loading" style="display:none;"><img
                            src="/modules/hipay_professional/views/img/loading.gif"/></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-danger" id="hipay-error" style="display:none;"></div>
            <div class="alert alert-success" id="hipay-success" style="display:none;"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        $('#capture').on('click', function () {
            $('#capture').hide();
            $('#hipay-loading').show();
            $('#hipay-error').hide();

            $.ajax({
                url: '{$ajax_url}', // point to server-side PHP script
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                type: 'post',
                success: function (response) {
                    if (response.status == 0) {
                        $('#hipay-error').html(response.message);
                        $('#hipay-error').show();
                        $('#hipay-loading').hide();
                        $('#capture').show();
                    } else {
                        $('#hipay-error').hide();
                        $('#hipay-loading').hide();
                        $('#hipay-success').html(response.message);
                        $('#hipay-success').show();
                        $('#hipay-warning').show();
                    }
                }
            });
        });
    });
</script>
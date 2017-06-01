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

{if isset($validator) && $validator}
    <div class="panel">
        <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" id="validator_form">
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <!-- VALIDATOR ACCOUNT START -->
                    <h3 class="space-button2">{l s='Validate your account' mod='hipay_professional'}</h3>
                    <div class="row">
                        <div class="col-lg-12">
                            {l s='Thank you ! Your HiPay account has been created. In Order to validate it, please enter the validation code which has been sent to your e-mail address :' mod='hipay_professional'}
                            <br/>
                            <b>{if isset($email)}{$email|escape:'htmlall':'UTF-8'}}{/if}</b>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <label class="control-label col-lg-2">
                                <span class="label-tooltip" data-toggle="tooltip" data-html="true" title=""
                                      data-original-title="{l s='Validation code' mod='hipay_professional'}">
                                    {l s='Validation code:' mod='hipay_professional'}
                                </span>
                            </label>
                            <div class="col-lg-3">
                                <input type="text" name="code_validator" id="code_validator" value="" class="lg">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- PAYMENT BUTTON END -->
            <hr/>
            <div class="row">
                <div class="col-md-12 col-xs-12">
                    <button type="submit" class="btn btn-default btn btn-default pull-right" name="submitValidator"><i
                                class="process-icon-save"></i>{l s='Submit' mod='hipay_professional'}</button>
                </div>
            </div>
        </form>
    </div>
{else}
    {$register_form}
    <script type="text/javascript">
        $(document).ready(function () {
            $('#reload-captcha').on('click', function () {
                var form_data = new FormData();
                form_data.append('controller', 'AdminHiPayConfig');
                form_data.append('action', 'ReloadCaptcha');
                form_data.append('ajax', true);

                $.ajax({
                    url: '{$ajax_url}', // point to server-side PHP script
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function (captcha) {
                        if (captcha.code == 0) {
                            $('#img-captcha').html(captcha.captcha_img);
                            $('#register_captcha_id').val(captcha.captcha_id);
                        }
                    }
                });
            });
        });
    </script>
{/if}

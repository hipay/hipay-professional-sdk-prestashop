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
<!-- Modal -->
<div class="modal fade" id="sandbox-connexion" tabindex="-1" role="dialog" aria-labelledby="sandbox-login"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="sandbox-login">{l s='Connect test account' mod='hipay_professional'}</h4>
            </div>
            <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" id="modal_login_form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 col-xs-12">
                            <div class="row">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 required">
                                        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title=""
                                              data-original-title="{l s='You can find it on your HiPay account, section "Integration > API", under "Webservice access"' mod='hipay_professional'}">{l s='WS Login' mod='hipay_professional'}</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="modal_ws_login" id="modal_ws_login" value=""
                                               class="fixed-width-xxl" required="required">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 col-xs-12">
                            <div class="row">
                                <div class="form-group">
                                    <label class="control-label col-lg-3 required">
                                        <span class="label-tooltip" data-toggle="tooltip" data-html="true" title=""
                                              data-original-title="{l s='You can find it on your HiPay account, section "Integration > API", under "Webservice access"' mod='hipay_professional'}">{l s='WS Password' mod='hipay_professional'}</span>
                                    </label>
                                    <div class="col-lg-9">
                                        <input type="text" name="modal_ws_password" id="modal_ws_password" value=""
                                               class="fixed-width-xxl" required="required">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{l s='Close' mod='hipay_professional'}</button>
                    <button type="submit" class="btn btn-primary"
                            name="submitSandboxConnection">{l s='HiPay Direct test website' mod='hipay_professional'}</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
<div class="panel">
    <div id="setting-image-error" class="img-error"></div>
    <div id="setting-image-success" class="img-success"></div>
    <form method="post" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" id="settings_form">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <!-- SWITCH MODE START -->
                <div class="row">
                    <label class="control-label col-lg-3">
                        <span class="label-tooltip"
                              data-toggle="tooltip"
                              data-html="true"
                              title=""
                              data-original-title="{l s='When in test mode, payment cards are not really charged. Activate this options for testing purposes only.' mod='hipay_professional'}">
                            {l s='Use test mode' mod='hipay_professional'}
                        </span>
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            {if isset($config_hipay.sandbox_ws_login) && !empty($config_hipay.sandbox_ws_login) && isset($config_hipay.sandbox_ws_password) && !empty($config_hipay.sandbox_ws_password)}
                                <input type="radio" name="settings_switchmode" id="settings_switchmode_on" value="1"
                                       {if $config_hipay.sandbox_mode }checked="checked"{/if}>
                            {/if}
                            <label for="settings_switchmode_on">{l s='Yes' mod='hipay_professional'}</label>
                            <input type="radio" name="settings_switchmode" id="settings_switchmode_off" value="0"
                                   {if $config_hipay.sandbox_mode == false}checked="checked"{/if}>
                            <label for="settings_switchmode_off">{l s='No' mod='hipay_professional'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 col-xs-12">
                        <p>
                            {l s='When in test mode, payment cards are not really charged. Activate this options for testing purposes only.' mod='hipay_professional'}
                        </p>
                    </div>
                </div>
                <!-- SWITCH MODE END -->
                <div class="row">
                    <!-- PRODUCTION FORM START -->
                    <div class="col-md-6 trait">
                        <h4>{l s='Production configuration' mod='hipay_professional'}</h4>
                        <div class="row">
                            <label class="control-label col-lg-3">
                                <span class="label-tooltip"
                                      data-toggle="tooltip"
                                      data-html="true"
                                      title=""
                                      data-original-title="{l s='Content rating' mod='hipay_professional'}">
                                {l s='Content rating' mod='hipay_professional'}
                            </label>
                            <div class="col-lg-9">
                                <select id="settings_production_rating" name="settings_production_rating">
                                    <option value="">{l s='--- Select the content rating ---' mod='hipay_professional'}</option>
                                    {foreach from=$rating item=select}
                                        <option value="{$select.key|escape:'htmlall':'UTF-8'}"
                                                {if isset($config_hipay.selected.rating_prod) && $config_hipay.selected.rating_prod == $select.key}selected{/if}>{$select.name|escape:'htmlall':'UTF-8'}</option>
                                        {foreachelse}
                                        <option value="">{l s='No Content rating' mod='hipay_professional'}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <h4>{l s='Accounts and currencies' mod='hipay_professional'}</h4>
                        <!-- TABLE SELECTION PROD START -->
                        <table class="table" id="accounts-currencies">
                            <thead>
                            <tr>
                                <th>{l s='Currency' mod='hipay_professional'}</th>
                                <th>{l s='Account ID' mod='hipay_professional'}</th>
                                <th>{l s='Website' mod='hipay_professional'}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach from=$selectedCurrencies key=currency item=options}
                                <tr>
                                    {if $currency == "0" }
                                        <td colspan="3">{l s='no data' mod='hipay_professional'}</td>
                                    {else}
                                        <td>{$currency|escape:'htmlall':'UTF-8'}</td>
                                        {if !isset($config_hipay.production.$currency) || $config_hipay.production.$currency|@count == 0}
                                            {if !$currency|array_key_exists:$limitedCurrencies }
                                                <td colspan="2">
                                                    <span class="icon icon-warning-sign" aria-hidden="true">
                                                    {l s='This currency is not supported by HiPay' mod='hipay_professional'}
                                                    </span>
                                                </td>
                                            {else}
                                                <td colspan="2">
                                                <span class="icon icon-warning-sign" aria-hidden="true">
                                                    <a href="javascript:void(0);"
                                                       id="production_duplication_{$currency|escape:'htmlall':'UTF-8'}">{l s='Currency not activated. Click here to fix.' mod='hipay_professional'}</a>
                                                </span>
                                                </td>
                                            {/if}
                                        {else}
                                            <td>
                                                <select id="settings_production_{$currency|escape:'htmlall':'UTF-8'}_user_account_id"
                                                        name="settings_production_{$currency|escape:'htmlall':'UTF-8'}_user_account_id">
                                                    <option value="">{l s='--- Account ID ---' mod='hipay_professional'}</option>
                                                    {foreach from=$config_hipay.production.$currency key=aid item=select}
                                                        <option value="{$aid|escape:'htmlall':'UTF-8'}"
                                                                {if isset($config_hipay.selected.currencies) && $config_hipay.selected.currencies.production.$currency.accountID == $aid}selected{/if}>{$aid|escape:'htmlall':'UTF-8'}</option>
                                                        {foreachelse}
                                                        <option value="">{l s='No Account ID' mod='hipay_professional'}</option>
                                                    {/foreach}
                                                </select>
                                            </td>
                                            <td>
                                                <select id="settings_production_{$currency|escape:'htmlall':'UTF-8'}_website_id"
                                                        name="settings_production_{$currency|escape:'htmlall':'UTF-8'}_website_id">
                                                    <option value="">{l s='--- Website ID ---' mod='hipay_professional'}</option>
                                                    {if isset($config_hipay.selected.currencies)}
                                                        {assign var="prod_account_id" value=$config_hipay.selected.currencies.production.$currency.accountID}
                                                    {/if}
                                                    {if isset($prod_account_id)}
                                                        {foreach from=$config_hipay.production.$currency.$prod_account_id item=select}
                                                            <option value="{$select.website_id|escape:'htmlall':'UTF-8'}"
                                                                    {if isset($config_hipay.selected.currencies) && $config_hipay.selected.currencies.production.$currency.websiteID == $select.website_id}selected{/if}>{$select.website_id|escape:'htmlall':'UTF-8'}</option>
                                                            {foreachelse}
                                                            <option value="">{l s='No Website ID' mod='hipay_professional'}</option>
                                                        {/foreach}
                                                    {/if}
                                                </select>
                                            </td>
                                        {/if}
                                    {/if}
                                </tr>
                                {foreachelse}
                                <tr>
                                    <td colspan="3">{l s='no data' mod='hipay_professional'}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                        <!-- TABLE SELECTION PROD END -->
                    </div>
                    <!-- PRODUCTION FORM END -->
                    <!-- SANDBOX FORM START -->
                    <div class="col-md-6">
                        <h4>{l s='Test configuration' mod='hipay_professional'}</h4>
                        {if !empty($config_hipay.sandbox_ws_login)}
                            <div class="row">
                                <label class="control-label col-lg-3">
                                    <span class="label-tooltip"
                                          data-toggle="tooltip"
                                          data-html="true"
                                          title=""
                                          data-original-title="{l s='Content rating' mod='hipay_professional'}">
                                    {l s='Content rating' mod='hipay_professional'}
                                </label>
                                <div class="col-lg-9">
                                    <select id="settings_sandbox_rating" name="settings_sandbox_rating">
                                        <option value="">{l s='--- Select the content rating ---' mod='hipay_professional'}</option>
                                        {foreach from=$rating item=select}
                                            <option value="{$select.key|escape:'htmlall':'UTF-8'}"
                                                    {if  isset($config_hipay.selected.rating_sandbox) && $config_hipay.selected.rating_sandbox == $select.key}selected{/if}>{$select.name|escape:'htmlall':'UTF-8'}</option>
                                            {foreachelse}
                                            <option value="">{l s='No Content rating' mod='hipay_professional'}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        {else}
                            {l s='Your test account is not connected yet. Enter your test account\'s web service login and password in order to use a test' mod='hipay_professional'}
                        {/if}
                        {if !empty($config_hipay.sandbox_ws_login)}
                            <h4>{l s='Accounts and currencies' mod='hipay_professional'}</h4>
                        {/if}
                        {if empty($config_hipay.sandbox_ws_login)}
                            <div class="row">
                                <button type="button" class="btn btn-primary center-block btn-lg space"
                                        data-toggle="modal" data-target="#sandbox-connexion">
                                    {l s='Connect test account' mod='hipay_professional'}
                                </button>
                            </div>
                            <div class="row">
                                <p>
                                    {l s='If you don\'t have a test account yet, you can create one on the' mod='hipay_professional'}
                                    &nbsp;
                                    <a href="{$url_test_hipay_direct|escape:'htmlall':'UTF-8'}" target="_blank">
                                        {l s='HiPay Direct test website' mod='hipay_professional'}
                                    </a>
                                </p>
                            </div>
                        {else}
                            <!-- TABLE SELECTION TEST START -->
                            <table class="table" id="accounts-currencies2">
                                <thead>
                                <tr>
                                    <th>{l s='Currency' mod='hipay_professional'}</th>
                                    <th>{l s='Account ID' mod='hipay_professional'}</th>
                                    <th>{l s='Website' mod='hipay_professional'}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach from=$selectedCurrencies key=currency item=options}
                                    <tr>
                                        {if $currency == "0" }
                                            <td colspan="3">{l s='no data' mod='hipay_professional'}</td>
                                        {else}
                                            <td>{$currency|escape:'htmlall':'UTF-8'}</td>
                                            {if !isset($config_hipay.sandbox.$currency) || $config_hipay.sandbox.$currency|@count == 0}
                                                {if !$currency|array_key_exists:$limitedCurrencies }
                                                    <td colspan="2">
                                                    <span class="icon icon-warning-sign" aria-hidden="true">
                                                    {l s='This currency is not supported by HiPay' mod='hipay_professional'}
                                                    </span>
                                                    </td>
                                                {else}
                                                    <td colspan="2">
                                                <span class="icon icon-warning-sign" aria-hidden="true">
                                                    <a href="javascript:void(0);"
                                                       id="sandbox_duplication_{$currency}">{l s='Currency not activated. Click here to fix.' mod='hipay_professional'}</a>
                                                </span>
                                                    </td>
                                                {/if}
                                            {else}
                                                <td>
                                                    <select id="settings_sandbox_{$currency|escape:'htmlall':'UTF-8'}_user_account_id"
                                                            name="settings_sandbox_{$currency}_user_account_id">
                                                        <option value="">{l s='--- Account ID ---' mod='hipay_professional'}</option>
                                                        {foreach from=$config_hipay.sandbox.$currency key=aid item=select}
                                                            <option value="{$aid|escape:'htmlall':'UTF-8'}"
                                                                    {if isset($config_hipay.selected.currencies) && $config_hipay.selected.currencies.sandbox.$currency.accountID == $aid}selected{/if}>{$aid|escape:'htmlall':'UTF-8'}</option>
                                                            {foreachelse}
                                                            <option value="">{l s='No Account ID' mod='hipay_professional'}</option>
                                                        {/foreach}
                                                    </select>
                                                </td>
                                                <td>
                                                    <select id="settings_sandbox_{$currency|escape:'htmlall':'UTF-8'}_website_id"
                                                            name="settings_sandbox_{$currency}_website_id">
                                                        <option value="">{l s='--- Website ID ---' mod='hipay_professional'}</option>
                                                        {if isset($config_hipay.selected.currencies)}
                                                            {assign var="test_account_id" value=$config_hipay.selected.currencies.sandbox.$currency.accountID}
                                                        {/if}
                                                        {if isset($test_account_id)}
                                                            {foreach from=$config_hipay.sandbox.$currency.$test_account_id item=select}
                                                                <option value="{$select.website_id|escape:'htmlall':'UTF-8'}"
                                                                        {if isset($config_hipay.selected) && $config_hipay.selected.currencies.sandbox.$currency.websiteID == $select.website_id}selected{/if}>{$select.website_id|escape:'htmlall':'UTF-8'}</option>
                                                                {foreachelse}
                                                                <option value="">{l s='No Website ID' mod='hipay_professional'}</option>
                                                            {/foreach}
                                                        {/if}
                                                    </select>
                                                </td>
                                            {/if}
                                        {/if}
                                    </tr>
                                    {foreachelse}
                                    <tr>
                                        <td colspan="3">{l s='no data' mod='hipay_professional'}</td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                            <!-- TABLE SELECTION TEST END -->
                        {/if}
                    </div>
                </div>
                <!-- SANDBOX FORM END -->
                <hr/>
                <div class="row">
                    <div class="col-md-12 col-xs-12">
                        <button type="submit" class="btn btn-default pull-left" name="submitCancel"><i
                                    class="process-icon-eraser"></i>{l s='Discard changes' mod='hipay_professional'}
                        </button>
                        <button type="submit" class="btn btn-default btn btn-default pull-right" name="submitSettings">
                            <i class="process-icon-save"></i>{l s='Save configuration changes' mod='hipay_professional'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{* include file modal-login.tpl *}
{include file='./modal-login.tpl'}

{* modal info *}
<div class="modal fade" id="hipay-info" tabindex="-1" role="dialog" aria-labelledby="hipay-info-title"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="hipay-info-title">{l s='HiPay information' mod='hipay_professional'}</h4>
            </div>
            <div class="modal-body" id="hipay-info-message">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-dismiss="modal">{l s='Close' mod='hipay_professional'}</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function () {

        {* reload page when modal info closed *}

        $('#hipay-info').on('hidden.bs.modal', function (e) {
            location.reload();
        });

        {*
         * init for dynamic selectbox the json website_id by user_account_id
         *}

        var json_prod = {if isset($config_hipay.production) && is_array($config_hipay.production)}{$config_hipay.production|@json_encode}{else}""{/if};
        var json_test = {if isset($config_hipay.sandbox) && is_array($config_hipay.sandbox)}{$config_hipay.sandbox|@json_encode}{else}""{/if} ;

        {*
         * generate function .change jquery for each currency and selectbox user_account_id
         *}
        {foreach from=$selectedCurrencies key=currency item=options}
        $("#settings_production_{$currency}_user_account_id").change(function () {

            var idSelect = "settings_production_{$currency|escape:'htmlall':'UTF-8'}_website_id";
            var idAccount = $(this).val();

            $('#' + idSelect).children('option:not(:first)').remove();
            addOptionsWebsiteId(idSelect, json_prod.{$currency|escape:'htmlall':'UTF-8'}, idAccount);
        });
        $("#settings_sandbox_{$currency|escape:'htmlall':'UTF-8'}_user_account_id").change(function () {
            var idSelect = "settings_sandbox_{$currency|escape:'htmlall':'UTF-8'}_website_id";
            var idAccount = $(this).val();

            $('#' + idSelect).children('option:not(:first)').remove();
            addOptionsWebsiteId(idSelect, json_test.{$currency|escape:'htmlall':'UTF-8'}, idAccount);
        });
        {/foreach}

        {*
         * function load website_id by user_account_id and currency in the selectbox website_id
         *}
        function addOptionsWebsiteId(idSelect, config, idAccount) {
            $.each(config[idAccount], function (key, value) {
                $('#' + idSelect)
                        .append($("<option></option>")
                                .attr("value", value.website_id)
                                .text(value.website_id));
            });
        }

        {foreach from=$selectedCurrencies key=currency item=options}
        {if !isset($config_hipay.production.$currency) || $config_hipay.production.$currency|@count == 0}
        {if $currency|array_key_exists:$limitedCurrencies }
        $("#production_duplication_{$currency}").on('click', function () {
            $.ajax({
                url: '{$ajax_url}',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                data: 'action=Duplicate&ajax=true&sandbox=0&currency={$currency}',
                type: 'get',
                success: function (jsonData) {
                    if (jsonData.status == true) {
                        $("#hipay-info-message").html(jsonData.message);
                        $('#hipay-info').modal('show');
                    } else {
                        $('#setting-image-error').html(jsonData.message);
                        $('#setting-image-error').show();
                        $('#setting-image-success').hide();
                    }
                }
            });
        });
        {/if}
        {/if}
        {if !isset($config_hipay.sandbox.$currency) || $config_hipay.sandbox.$currency|@count == 0}
        {if $currency|array_key_exists:$limitedCurrencies }
        $("#sandbox_duplication_{$currency|escape:'htmlall':'UTF-8'}").on('click', function () {

            $.ajax({
                url: '{$ajax_url}',
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                data: 'action=Duplicate&ajax=true&sandbox=1&currency={$currency}',
                type: 'get',
                success: function (jsonData) {
                    if (jsonData.status == true) {
                        $("#hipay-info-message").html(jsonData.message);
                        $('#hipay-info').modal('show');
                    } else {
                        $('#setting-image-error').html(jsonData.message);
                        $('#setting-image-error').show();
                        $('#setting-image-success').hide();
                    }
                }
            });
        });
        {/if}
        {/if}
        {/foreach}

    });
</script>
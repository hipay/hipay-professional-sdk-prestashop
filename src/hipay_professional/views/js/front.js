/**
 * 2016 HiPay
 *
 * NOTICE OF LICENSE
 *
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2016 HiPay
 * @license   https://github.com/hipay/hipay-wallet-sdk-prestashop/blob/master/LICENSE.md
 *
 */

$(document).ready(function () {
    moveHiPayButtonPosition();

    $(document).on('DOMSubtreeModified', '#HOOK_PAYMENT', function () {
        if ($('#HOOK_PAYMENT > .row:first-child #hipay_payment_button').length == 0) {
            moveHiPayButtonPosition();
        }
    });
});

function moveHiPayButtonPosition() {
    if ($('#hipay_payment_button').length) {
        $payment_button = $('#hipay_payment_button').closest('.row').clone();
        $('#hipay_payment_button').closest('.row').remove();
        $('#HOOK_PAYMENT').prepend($payment_button);
    }
}

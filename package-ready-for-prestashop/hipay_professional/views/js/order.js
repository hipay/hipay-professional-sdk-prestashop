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
    // Partial refunds
    $(this).on('click', '#partial-refund-button', function () {
        $('#partial-refund-details').fadeIn();
    });

    $(this).on('click', '#partial-refund-process', function () {
        var amount = $('#partial-refund-amount').val();
        var amount_max = $('#refund-amount-max').val();

        if (isNaN(parseFloat(amount)) || (parseFloat(amount) == 0.00)) {
            return alert($('#refund-amount-empty-msg').text());
        }

        if (parseFloat(amount) > parseFloat(amount_max)) {
            return alert($('#refund-amount-max-alert-msg').text());
        }

        var confirmation = $('#partial-refund-confirmation-msg').text();

        if (confirm(confirmation) == false) {
            return false;
        }

        processRefund({
            amount: amount,
        });
    });

    $(this).on('keyup', '#partial-refund-amount', function (event) {
        return (event.keyCode === 13) ? $('#partial-refund-process').trigger('click') : false;
    });

    // Total refunds
    $(this).on('click', '#total-refund-button', function () {
        var confirmation = $('#total-refund-confirmation-msg').text();

        if (confirm(confirmation) == false) {
            return false;
        }

        processRefund();
    });
});

function getRefundControllerLink() {
    var link = $('#refund-link').text();

    return decodeURIComponent(link);
}

function processRefund(data) {
    $.ajax({
        url: getRefundControllerLink(),
        data: data
    }).success(function (result) {
        location.reload();
    }).fail(function (error) {
        alert(error.responseText);
    });
}

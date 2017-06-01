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
    iterations = 0;
    timer = false;

    checkOrder();

});

function checkOrder() {
    if (timer == false) {
        timer = setInterval(processCheck, 1000);
    }
}

function processCheck() {
    iterations += 1;

    if (iterations == 10) {
        return redirectError();
    }

    return $.ajax({
        url: ajax_url
    }).success(function (result) {
        if (result != undefined) {
            clearInterval(timer);

            location.reload();
        }
    });
}

function redirectError() {
    clearInterval(timer);

    var url = window.location.href;
    window.location.href = url + '&failure=true';
}

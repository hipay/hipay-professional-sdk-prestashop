<?php
/**
 * 2016 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay <support.wallet@hipay.com>
 * @copyright 2016 HiPay
 * @license   https://github.com/hipay/hipay-wallet-sdk-prestashop/blob/master/LICENSE.md
 */

class HipayLogs
{
    public $enable = true;

    public function __construct($module_instance)
    {
        $this->context = Context::getContext();
        $this->module = $module_instance;
        // init config hipay
        $this->configHipay = $module_instance->configHipay;
        $this->enable = (isset($this->configHipay->mode_debug) ? $this->configHipay->mode_debug : true);
    }

    /**
     *
     * LOG Errors
     *
     */
    public function errorLogsHipay($msg)
    {
        $this->writeLogs(0, $msg);
    }

    /**
     *
     * LOG APP
     *
     */
    public function logsHipay($msg)
    {
        $this->writeLogs(1, $msg);
    }

    public function callbackLogs($msg)
    {
        $this->writeLogs(2, $msg);
    }

    public function requestLogs($msg)
    {
        $this->writeLogs(3, $msg);
    }

    public function refundLogs($msg)
    {
        $this->writeLogs(4, $msg);
    }

    private function writeLogs($code, $msg)
    {
        if ($this->enable) {
            switch ($code) {
                case 0:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-error-logs.txt', 'a+');
                    break;
                case 1:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-infos-logs.txt', 'a+');
                    break;
                case 2:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-callback.txt', 'a+');
                    break;
                case 3:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-request-new-order.txt', 'a+');
                    break;
                case 4:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-refund-order.txt', 'a+');
                    break;
                default:
                    $fp = fopen(_PS_MODULE_DIR_ . '/hipay_professional/logs/' . date('Y-m-d') . '-infos-logs.txt', 'a+');
                    break;
            }
            fseek($fp, SEEK_END);
            fputs($fp, '## ' . date('Y-m-d H:i:s') . ' ##' . PHP_EOL);
            fputs($fp, $msg . PHP_EOL);
            fclose($fp);
        }
    }
}

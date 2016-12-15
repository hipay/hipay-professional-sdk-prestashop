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

require_once(dirname(__FILE__) . '/HipayREST.php');

class HipayLocale extends HipayREST
{

    /* Locales list */
    protected static $locales = array();

    /* SOAP method: codes */
    public function getLocales()
    {
        if (empty(self::$locales)) {
            $results = $this->executeQuery('codes');

            if ($results->codesResult->code === 0) {
                self::$locales = (array)$results->codesResult->locales->item;
            }
        }

        return self::$locales;
    }

    public function getLocale()
    {
        $locale_exists = $this->currentLocaleExists();

        if ($locale_exists === true) {
            return $this->getCurrentLocaleCode();
        }

        $locales = $this->getLocales();
        $country_iso_code = Tools::strtoupper($this->context->country->iso_code);

        foreach ($locales as $locale) {
            if (strstr($locale->code, $country_iso_code) == true) {
                return $locale->code;
            }
        }

        return 'en_GB';
    }

    public function getCurrentLocaleCode()
    {
        $language_iso_code = Tools::strtolower($this->context->language->iso_code);
        $country_iso_code = Tools::strtoupper($this->context->country->iso_code);

        return $language_iso_code . '_' . $country_iso_code;
    }

    public function currentLocaleExists()
    {
        $this->context = context::getContext();
        $locale_code = $this->getCurrentLocaleCode();

        $locales = $this->getLocales();

        foreach ($locales as $locale) {
            if (strcmp($locale->code, $locale_code) === 0) {
                return true;
            }
        }

        return false;
    }
}

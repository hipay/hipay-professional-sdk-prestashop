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

abstract class HipayFormInputs
{
    protected function generateFormSplit()
    {
        $params = array('col' => 12, 'offset' => 0);

        return $this->generateInput('free', 'input_split', null, $params);
    }

    protected function generateInput($type, $name, $label = false, $params = array())
    {
        $input = array(
            'type' => $type,
            'label' => $label,
            'name' => $name,
        );

        if (is_array($params) === true) {
            foreach ($params as $key => $value) {
                $input[$key] = $value;
            }
        }

        return $input;
    }

    protected function generateInputEmail($name, $title, $description)
    {
        return $this->generateInputText($name, $title, array(
            'required' => true,
            'hint' => $description,
            'placeholder' => $this->module->l('email@domain.com'),
            'class' => 'fixed-width-xxl',
        ));
    }

    protected function generateInputFree($name, $label = false, $params = array())
    {
        return $this->generateInput('free', $name, $label, $params);
    }

    protected function generateInputText($name, $label = false, $params = array())
    {
        return $this->generateInput('text', $name, $label, $params);
    }

    protected function generateInputSelect($name, $label = false, $params = array())
    {
        return $this->generateInput('select', $name, $label, $params);
    }

    protected function generateInputCheckbox($name, $label = false, $params = array())
    {
        return $this->generateInput('checkbox', $name, $label, $params);
    }

    protected function generateInputTextarea($name, $label = false, $params = array())
    {
        return $this->generateInput('textarea', $name, $label, $params);
    }

    public function generateLegend($title, $icon = false)
    {
        return array(
            'title' => $title,
            'icon' => $icon,
        );
    }

    protected function generateSubmitButton($title, $params = array())
    {
        $input = array(
            'title' => $title,
            'type' => 'submit',
            'class' => 'btn btn-default pull-right'
        );

        if (is_array($params) === true) {
            foreach ($params as $key => $value) {
                $input[$key] = $value;
            }
        }

        return $input;
    }

    protected function generateSwitchButton($name, $title, $params = array())
    {
        $input = array(
            'type' => 'switch',
            'label' => $title,
            'name' => $name,
            'is_bool' => true,
            'values' => array(
                array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $this->module->l('Enabled')
                ),
                array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $this->module->l('Disabled')
                ),
            ),
        );

        if (is_array($params) === true) {
            foreach ($params as $key => $value) {
                $input[$key] = $value;
            }
        }

        return $input;
    }
}

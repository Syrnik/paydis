<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2022
 * @license Webasyst
 */

declare(strict_types=1);

/**
 * @ControllerAction discounts/save
 */
class shopPaydisPluginDiscountsSaveController extends shopMarketingSettingsJsonController
{
    public function execute()
    {
        $values = (array)waRequest::post('discount', [], waRequest::TYPE_ARRAY_TRIM);

        $discounts = [];
        foreach ($values as $key => $value) {
            $discounts[$key] = ['payment_id' => $key, 'discount' => str_replace(',', '.', trim($value)) ?: ''];
        }

        (new waAppSettingsModel)->set('shop.paydis', 'discounts', waUtils::jsonEncode($discounts, JSON_UNESCAPED_UNICODE));
    }
}

<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2022
 * @license Webasyst
 */

declare(strict_types=1);

/**
 * @ControllerAction backend/default
 */
class shopPaydisPluginBackendAction extends waViewAction
{
    public function execute()
    {
        $payment_methods = (new shopPluginModel)->listPlugins(shopPluginModel::TYPE_PAYMENT);
        $payment_methods = array_column($payment_methods, null, 'id');

        $settings = wa('shop')->getPlugin('paydis')->getSettings();
        $discounts = (array)($settings['discounts'] ?? []);
        $discounts = array_column($discounts, null, 'payment_id');

        // Добавим отсутствующие, новые способы оплаты
        foreach ($payment_methods as $method) {
            if (!isset($discounts[$method['id']]))
                $discounts[$method['id']] = ['payment_id' => $method['id'], 'discount' => ''];
        }

        // Вычистим удалённые
        $discounts = array_filter($discounts, function ($d) use ($payment_methods) {
            return isset($payment_methods[$d['payment_id']]);
        });

        $settings['discounts'] = $discounts;

        $enabled = shopDiscounts::isEnabled('paydis_discount');

        $this->view->assign(compact('payment_methods', 'settings', 'enabled'));
    }
}

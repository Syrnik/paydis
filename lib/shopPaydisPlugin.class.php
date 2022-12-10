<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2022
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */

declare(strict_types=1);

/**
 * Main plugin class
 */
class shopPaydisPlugin extends shopPlugin
{
    /**
     * @EventHandler backend_settings_discount
     *
     * @return array
     * @throws waException
     */
    public function handlerBackendSettingsDiscounts(): array
    {
        return [
            'name'   => _wp('По способу оплаты'),
            'id'     => 'paydis_discount',
            'url'    => '?plugin=paydis',
            'status' => shopDiscounts::isEnabled('paydis_discount')
        ];
    }

    /**
     * @EventHandler order_calculate_discount
     *
     * @param array $params
     * @return array
     */
    public function handlerOrderCalculateDiscount(array $params): array
    {
        try {
            if (!shopDiscounts::isEnabled('paydis_discount')) return [];
            if ($payment_id = intval($params['order']['params']['payment_id'] ?? 0) ?: $this->getPaymentIdFromStorage()) {
                $discounts = array_column((array)$this->getSettings('discounts'), null, 'payment_id');
                if (!($discount = $discounts[$payment_id] ?? []) || !is_array($discount)) return [];
                if (!($discount = trim((string)($discount['discount'] ?? '')))) return [];
                if (!($items = $params['order']['items'] ?? []) || !is_array($items)) return [];
                $message = _wp('Скидка по способу оплаты');
                $discount = floatval($discount) / 100;
                return ['items' => array_map(function ($item) use ($discount, $message) {
                    $total = $item['price'] * $item['quantity'];
                    return [
                        'discount'    => min($total, $total * $discount),
                        'description' => $message
                    ];
                }, $items)];
            }
        } catch (waException $exception) {
            try {
                $message = _wp("Плагин \"Скидка по способу оплаты\". Получено исключение при расчёте скидки: %s");
            } catch (waException $e) {
                $message = "Плагин \"Скидка по способу оплаты\". Получено исключение при расчёте скидки: %s";
            }
            waLog::log(sprintf($message, $exception->getMessage()));
        }

        return [];
    }

    /**
     * @return int
     * @throws waException
     */
    protected function getPaymentIdFromStorage(): int
    {
        $payment_id = 0;

        $request_uri = trim(waRequest::server('REQUEST_URI'), '/');
        $checkout = $request_uri == trim(wa()->getRouteUrl('shop/frontend/checkout', ['step' => 'confirmation']), '/') ||
            $request_uri == trim(wa()->getRouteUrl('shop/frontend/checkout'), '/') ||
            false !== strpos($request_uri, 'buy1step');
        $session = wa()->getStorage()->get('shop/checkout');
        if ($checkout && ($session['payment'] ?? 0))
            $payment_id = intval($session['payment']);
        elseif ($session['order']['payment']['id'] ?? 0)
            $payment_id = intval($session['order']['payment']['id']);

        return $payment_id;
    }
}

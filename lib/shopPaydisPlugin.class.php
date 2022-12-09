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
            if ($payment_id = intval($params['order']['params']['payment_id'] ?? 0) ?: $this->getPaymentIdFromStorage()) {
                $discounts = array_column((array)$this->getSettings('discounts'), null, 'payment_id');
                if (!($discount = $discounts[$payment_id] ?? []) || !is_array($discount)) return [];
                if (!($discount = trim((string)($discount['discount'] ?? '')))) return [];
                if (!($items = $params['order']['items'] ?? []) || !is_array($items)) return [];
                $message = _wp('Скидка по способу оплаты');

                return array_map(function ($item) use ($discount, $message) {
                    $total = $item['price'] * $item['quantity'];
                    return [
                        'discount'    => min($total, $this->simpleFormulaCalculator($discount, $total)),
                        'description' => $message
                    ];
                }, $items);
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

    /**
     * @param string $setting
     * @param float $price
     * @return float
     * @noinspection DuplicatedCode
     */
    protected function simpleFormulaCalculator(string $setting = '', float $price = 0.0): float
    {
        if (!$setting) return 0;

        $cost = 0.0;
        $clear_conditions = preg_replace('/\\s+/', '', $setting);
        $conditions_list = preg_split('/\+|(-)/', $clear_conditions, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($conditions_list as $condition) {
            $float_value = str_replace(',', '.', trim($condition[0]));

            if (strpos($float_value, '%')) {
                $float_value = $price * floatval($float_value) / 100;
                $float_value = round($float_value, 2);
            } else {
                $float_value = floatval($float_value);
            }

            if ($condition[1] && (substr($clear_conditions, $condition[1] - 1, 1) == '-')) {
                $cost -= $float_value;
            } else {
                $cost += $float_value;
            }
        }

        return round(max(0.0, $cost), 2);
    }
}

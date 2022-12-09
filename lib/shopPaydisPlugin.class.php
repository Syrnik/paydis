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
        $payment_id = intval($params['order']['params']['payment_id'] ?? 0) ?: $this->getPaymentIdFromStorage();


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

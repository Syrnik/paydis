<?php
return [
    'name'     => 'Скидка на способ оплаты',
    'img'      => 'img/paydis.gif',
    'version'  => '1.0.0',
    'vendor'   => '670917',
    'handlers' => [
        'backend_settings_discounts' => 'handlerBackendSettingsDiscounts',
        'order_calculate_discount'   => 'handlerOrderCalculateDiscount'
    ],
];

<?php

namespace Veriteworks\Paypal\Model\Source;

class PaymentAction
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'authorize',
                'label' => __('Authorize Only')
            ],
            [
                'value' => 'authorize_capture',
                'label' => __('Authorize and Capture')
            ],
        ];
    }
}

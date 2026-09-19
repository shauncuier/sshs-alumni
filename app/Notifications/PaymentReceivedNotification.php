<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;

class PaymentReceivedNotification extends AppNotification
{
    public function __construct(Payment $payment)
    {
        $amount = number_format((float) $payment->amount, 2);
        $method = strtoupper($payment->method->value ?? 'Cash');

        parent::__construct(
            title: 'Payment Received',
            body: "Your payment of BDT {$amount} ({$method}) has been successfully received and recorded.",
            actionUrl: route('my.payments'),
            actionText: 'View Payments',
            category: 'payment',
            icon: 'Receipt',
        );
    }
}

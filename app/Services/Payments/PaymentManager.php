<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Services\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

/**
 * Resolves a gateway driver by name.
 *
 * Drivers are resolved through the container and remembered, so a driver that
 * holds a connection or a signed client builds it once per request.
 *
 * @see docs/09-payments.md section 2
 */
class PaymentManager
{
    /**
     * @var array<string, PaymentGateway>
     */
    private array $resolved = [];

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= $this->defaultDriver();

        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        /** @var class-string<PaymentGateway>|null $class */
        $class = config("payments.gateways.{$name}.driver");

        if ($class === null) {
            // A typo in config or a half-added gateway should fail loudly at
            // the point of use, not silently fall back to manual and record
            // money as received that nobody has seen.
            throw new InvalidArgumentException("No payment gateway configured under [{$name}].");
        }

        return $this->resolved[$name] = app($class);
    }

    public function defaultDriver(): string
    {
        return (string) config('payments.default', 'manual');
    }

    public function currency(): string
    {
        return (string) config('payments.currency', 'BDT');
    }

    /**
     * @return array<int, string>
     */
    public function available(): array
    {
        /** @var array<string, mixed> $gateways */
        $gateways = config('payments.gateways', []);

        return array_keys($gateways);
    }
}

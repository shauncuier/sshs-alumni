<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Communication\SmsManager;
use Illuminate\Http\JsonResponse;

class SmsBalanceController extends Controller
{
    public function __invoke(SmsManager $smsManager): JsonResponse
    {
        return response()->json([
            'balance' => $smsManager->balance(),
            'enabled' => $smsManager->isEnabled(),
            'sent_today' => $smsManager->sentToday(),
            'remaining_today' => $smsManager->remainingToday(),
        ]);
    }
}

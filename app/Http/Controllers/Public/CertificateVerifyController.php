<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public digital certificate verification endpoint.
 *
 * Scanned from physical or PDF certificates via QR code.
 */
class CertificateVerifyController extends Controller
{
    public function __invoke(Request $request, string $ulid): Response
    {
        $certificate = Certificate::query()
            ->where('ulid', $ulid)
            ->with(['member.batch', 'event'])
            ->first();

        $tokenValid = true;
        if ($request->has('token')) {
            $tokenValid = $certificate !== null
                && hash_equals($certificate->qr_token, (string) $request->query('token'));
        }

        $valid = $certificate !== null && $tokenValid;

        return Inertia::render('public/verify-certificate', [
            'certificate' => $valid ? CertificateResource::make($certificate) : null,
            'is_valid' => $valid,
        ]);
    }
}

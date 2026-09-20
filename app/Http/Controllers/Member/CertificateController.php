<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $member = $request->user()?->member;
        abort_unless($member !== null, 403);

        $certificates = $member->certificates()
            ->with('event')
            ->latest('issue_date')
            ->paginate(12);

        return Inertia::render('member/certificates', [
            'certificates' => CertificateResource::collection($certificates),
        ]);
    }

    public function show(Certificate $certificate): Response
    {
        $this->authorize('view', $certificate);

        $certificate->load(['member.batch', 'event']);

        $verifyUrl = route('verify.certificate', [
            'ulid' => $certificate->ulid,
            'token' => $certificate->qr_token,
        ]);

        return Inertia::render('member/certificate-show', [
            'certificate' => CertificateResource::make($certificate),
            'verify_url' => $verifyUrl,
        ]);
    }
}

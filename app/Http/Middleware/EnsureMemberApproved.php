<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the directory and community on an APPROVED membership.
 *
 * A member whose application is still pending is not an intruder, so this does
 * not throw a bare 403. It redirects to a status page that explains where they
 * are in the verification workflow — the difference between "you are not
 * allowed" and "we have not finished reviewing you yet" matters to a real
 * person waiting on a committee.
 *
 * @see docs/03-routes.md section 2
 */
class EnsureMemberApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->user()?->member;

        if ($member !== null && $member->isApproved()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, __('member.approval_required'));
        }

        return redirect()
            ->route('my.profile')
            ->with('warning', __('member.approval_required'));
    }
}

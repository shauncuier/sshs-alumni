<?php

declare(strict_types=1);

namespace App\Actions\Membership;

use App\Enums\Locale;
use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\MemberLink;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

/**
 * Turns a completed registration draft into a User, a Member and its privacy
 * settings.
 *
 * One transaction: a half-created registration would leave a login with no
 * alumni record, or an alumni record nobody can sign in to.
 *
 * @see docs/05-modules.md section 1
 */
class RegisterMember
{
    /**
     * @param  array<string, mixed>  $draft
     * @param  string  $hashedPassword  ALREADY hashed by the caller, so a
     *                                  plaintext password never reaches the
     *                                  session store. Laravel's `hashed` cast
     *                                  is isHashed()-aware and passes it
     *                                  through rather than hashing it twice.
     */
    public function __invoke(array $draft, string $hashedPassword): Member
    {
        if (! Hash::isHashed($hashedPassword)) {
            throw new InvalidArgumentException(
                'RegisterMember expects an already-hashed password.'
            );
        }

        return DB::transaction(function () use ($draft, $hashedPassword): Member {
            $user = User::query()->create([
                'name' => (string) $draft['full_name'],
                'email' => (string) $draft['email'],
                'password' => $hashedPassword,
                'locale' => Locale::from((string) ($draft['locale'] ?? app()->getLocale())),
                'phone' => $draft['mobile'] ?? null,
            ]);

            // Every approved alumnus is a Member; the role is assigned now so
            // permissions are consistent from the first login, even though
            // directory access still waits on approval.
            $user->assignRole('Member');

            $member = Member::query()->create([
                ...Arr::only($draft, [
                    'full_name', 'full_name_bn', 'date_of_birth', 'gender', 'blood_group',
                    'relation_type', 'batch_id', 'ssc_year', 'student_id', 'admission_year',
                    'group_stream', 'section', 'house', 'higher_education',
                    'occupation', 'organization', 'job_title', 'industry', 'business_info',
                    'country', 'division', 'district', 'city', 'address',
                    'mobile', 'whatsapp', 'email',
                    'emergency_contact_name', 'emergency_contact_phone',
                    'bio', 'bio_bn', 'skills', 'interests',
                ]),
                'user_id' => $user->id,
                // Never trusted from the payload: status, membership number
                // and verification are set by the committee, not the applicant.
                'status' => MemberStatus::Pending,
                'registered_at' => now(),
            ]);

            // MemberObserver has already created the privacy row with
            // privacy-preserving defaults; this applies the applicant's own
            // choices from the review step.
            if (isset($draft['privacy']) && is_array($draft['privacy'])) {
                $member->privacy()->update($this->privacyFlags($draft['privacy']));
            }

            $this->storeLinks($member, $draft['links'] ?? null);

            return $member;
        });
    }

    /**
     * Only the known flags, cast to booleans — an extra key in the payload
     * cannot write an arbitrary column.
     *
     * @param  array<string, mixed>  $privacy
     * @return array<string, bool>
     */
    private function privacyFlags(array $privacy): array
    {
        $allowed = [
            'show_profile', 'show_phone', 'show_email', 'show_workplace',
            'show_location', 'show_date_of_birth', 'show_in_batch_list',
        ];

        $flags = [];

        foreach ($allowed as $flag) {
            if (array_key_exists($flag, $privacy)) {
                $flags[$flag] = (bool) $privacy[$flag];
            }
        }

        return $flags;
    }

    private function storeLinks(Member $member, mixed $links): void
    {
        if (! is_array($links)) {
            return;
        }

        foreach ($links as $index => $link) {
            if (! is_array($link) || blank($link['url'] ?? null)) {
                continue;
            }

            MemberLink::query()->create([
                'member_id' => $member->id,
                'type' => $link['type'],
                'url' => $link['url'],
                'display_order' => $index,
            ]);
        }
    }
}

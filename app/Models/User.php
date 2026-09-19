<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\Auditable;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * A login identity.
 *
 * A User is NOT an alumni record — that is a Member, linked 1:1 but able to
 * exist without a User (alumni entered from paper registers who will never log
 * in) and vice versa (office staff with no alumni record).
 *
 * @see docs/02-database-schema.md section 3
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $avatar_path
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property UserStatus $status
 * @property Carbon|null $last_active_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'email', 'password', 'phone', 'avatar_path', 'status'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Defaults held in memory as well as in the database.
     *
     * The column default only applies on insert, so without this a freshly
     * created User has a null status until it is reloaded — which is exactly
     * the instance `actingAs()` and post-registration redirects use.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'status' => UserStatus::class,
        ];
    }

    /**
     * The alumni record this login belongs to, if any.
     *
     * @return HasOne<Member, $this>
     */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    /**
     * A suspended or disabled user cannot sign in.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}

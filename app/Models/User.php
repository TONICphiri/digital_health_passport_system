<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'job_title',
        'professional_registration_number',
        'facility_id',
        'patient_id',
        'status',
        'must_change_password',
        'notification_preferences',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'notification_preferences' => 'array',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * The user's role as an enum. Every account holds exactly one role.
     */
    public function role(): ?RoleName
    {
        $name = $this->getRoleNames()->first();

        return $name ? RoleName::tryFrom($name) : null;
    }

    public function roleLabel(): string
    {
        return $this->role()?->label() ?? 'No role';
    }

    public function isRole(RoleName $role): bool
    {
        return $this->role() === $role;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * True when this user works at the given facility.
     */
    public function worksAt(?int $facilityId): bool
    {
        return $facilityId !== null && $this->facility_id === $facilityId;
    }

    /**
     * True when this portal user is the patient or the patient's mother.
     */
    public function ownsPatientRecord(Patient $patient): bool
    {
        if (! $this->patient_id) {
            return false;
        }

        return $patient->id === $this->patient_id || $patient->mother_id === $this->patient_id;
    }

    /**
     * Phone number used for text messages. Only returned when the passport
     * holder agreed to receive them.
     */
    public function routeNotificationForSms(): ?string
    {
        return $this->patient?->sms_consent ? $this->phone : null;
    }

    /**
     * Whether the holder wants this kind of reminder by email or text message.
     * Everything is wanted until the holder says otherwise.
     */
    public function allowsExternalNotice(string $channel, ?string $category): bool
    {
        $preferences = $this->notification_preferences ?? [];

        if ($category !== null && ($preferences['categories'][$category] ?? true) === false) {
            return false;
        }

        return $channel !== 'mail' || ($preferences['email'] ?? true) !== false;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        return strtoupper(collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Active);
    }

    public function scopeWithRole(Builder $query, RoleName $role): Builder
    {
        return $query->role($role->value);
    }
}

<?php

namespace App\Models;

use App\Enums\PatientStatus;
use App\Enums\Sex;
use App\Services\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'passport_number',
        'qr_token',
        'national_id',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'sex',
        'phone',
        'email',
        'sms_consent',
        'district_id',
        'traditional_authority',
        'village',
        'physical_address',
        'occupation',
        'blood_group',
        'allergies',
        'chronic_conditions',
        'disabilities',
        'health_notes',
        'mother_id',
        'separated_from_mother_at',
        'registered_facility_id',
        'registered_by',
        'status',
    ];

    protected $hidden = ['qr_token'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'sms_consent' => 'boolean',
            'separated_from_mother_at' => 'datetime',
            'sex' => Sex::class,
            'status' => PatientStatus::class,
            'allergies' => 'encrypted',
            'chronic_conditions' => 'encrypted',
            'disabilities' => 'encrypted',
            'health_notes' => 'encrypted',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' '));
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->date_of_birth?->age);
    }

    /**
     * Age written for display, in months for babies under one year.
     */
    protected function ageLabel(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->date_of_birth) {
                return 'Age not known';
            }

            if ($this->age >= 1) {
                return $this->age.' '.($this->age === 1 ? 'year' : 'years');
            }

            $months = (int) $this->date_of_birth->diffInMonths(now());

            return $months < 1 ? 'Under 1 month' : $months.' '.($months === 1 ? 'month' : 'months');
        });
    }

    /**
     * A patient is a child until they reach the separation age set by the
     * System Administrator (18 by default).
     */
    public function isChild(): bool
    {
        return $this->age !== null && $this->age < app(SettingService::class)->childSeparationAge();
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'mother_id');
    }

    public function registeredFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class, 'registered_facility_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class)->orderByDesc('is_primary');
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class)->latest('recorded_at');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class)->latest('started_at');
    }

    public function openVisit(): ?Visit
    {
        return $this->visits()->open()->first();
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(PassportAccess::class)->latest('opened_at');
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class)->latest('administered_on');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class)->orderBy('due_on');
    }

    /**
     * Search by passport number, National ID, phone number or name.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('passport_number', $term)
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
        });
    }

    protected function maskedNationalId(): Attribute
    {
        $value = $this->national_id;

        if (! is_string($value) || $value === '') {
            return Attribute::get(fn () => 'Not issued');
        }

        $length = strlen($value);

        if ($length <= 4) {
            return Attribute::get(fn () => str_repeat('X', $length));
        }

        return Attribute::get(fn () => str_repeat('X', $length - 4).substr($value, -4));
    }

    protected function maskedPassportNumber(): Attribute
    {
        $value = $this->passport_number;

        if (! is_string($value) || $value === '') {
            return Attribute::get(fn () => 'Not issued');
        }

        $length = strlen($value);

        if ($length <= 6) {
            return Attribute::get(fn () => str_repeat('X', $length));
        }

        return Attribute::get(fn () => substr($value, 0, 3).str_repeat('X', $length - 6).substr($value, -3));
    }
}


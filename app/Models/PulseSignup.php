<?php

namespace App\Models;

use App\Enums\PulseContactMethod;
use App\Enums\PulseSector;
use App\Enums\PulseSignupType;
use App\Support\PulseUnderwriting;
use Database\Factories\PulseSignupFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PulseSignup extends Model
{
    /** @use HasFactory<PulseSignupFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'contact_method',
        'contact',
        'province',
        'district',
        'listed',
        'queue_number',
        'pledge_amount',
        'projected_return',
        'blended_yield',
        'annual_revenue',
        'annual_costs',
        'sector',
        'registered_year',
        'score',
        'qualified_amount',
        'term_months',
        'flat_rate',
        'rating_band',
        'rating_score',
        'loan_number',
        'ip_address',
        'user_agent',
    ];

    /**
     * Scope the query to investor pledges.
     *
     * @param  Builder<PulseSignup>  $query
     */
    public function scopeInvestors(Builder $query): void
    {
        $query->where('type', PulseSignupType::Investor);
    }

    /**
     * Scope the query to business pre-qualifications.
     *
     * @param  Builder<PulseSignup>  $query
     */
    public function scopeBusinesses(Builder $query): void
    {
        $query->where('type', PulseSignupType::Business);
    }

    /**
     * Scope the query to businesses that sized at or above the smallest loan
     * Rozine writes. Anything under it joined the waitlist rather than
     * pre-qualifying, so it is not a loan an investor can back.
     *
     * @param  Builder<PulseSignup>  $query
     */
    public function scopePreQualified(Builder $query): void
    {
        $query->businesses()->where('qualified_amount', '>=', PulseUnderwriting::MIN_LOAN);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PulseSignupType::class,
            'contact_method' => PulseContactMethod::class,
            'listed' => 'boolean',
            'pledge_amount' => 'integer',
            'projected_return' => 'integer',
            'blended_yield' => 'float',
            'sector' => PulseSector::class,
            'annual_revenue' => 'integer',
            'annual_costs' => 'integer',
            'registered_year' => 'integer',
            'score' => 'float',
            'qualified_amount' => 'integer',
            'term_months' => 'integer',
            'flat_rate' => 'float',
            'rating_score' => 'float',
        ];
    }
}

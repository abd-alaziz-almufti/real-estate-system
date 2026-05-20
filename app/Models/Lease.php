<?php
// app/Models/Lease.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Lease extends Model
{
    use SoftDeletes, \App\Traits\HasCompany;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lease) {
            // If company_id is still not set by the trait (e.g. if created outside an Auth session),
            // try to fetch it from the unit's property or property directly.
            if (!$lease->company_id) {
                if ($lease->property_id) {
                    $property = Property::find($lease->property_id);
                    if ($property) {
                        $lease->company_id = $property->company_id;
                    }
                } elseif ($lease->unit_id) {
                    $unit = Unit::with('property')->find($lease->unit_id);
                    if ($unit && $unit->property) {
                        $lease->company_id = $unit->property->company_id;
                    }
                }
            }
        });
    }

    protected $fillable = [
        'company_id',
        'property_id',
        'unit_id',
        'tenant_id',
        'start_date',
        'end_date',
        'rent_amount',
        'deposit_amount',
        'payment_frequency',
        'payment_day',
        'status',
        'termination_date',
        'termination_reason',
        'notes',
        'special_terms',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'termination_date' => 'date',
        'rent_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'payment_day' => 'integer',
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    // Accessors & Helpers
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date || $this->is_expired) {
            return null;
        }
        return now()->diffInDays($this->end_date, false);
    }

    public function getTotalPaidAttribute(): float
    {
        if (array_key_exists('total_paid', $this->attributes)) {
            return (float) $this->attributes['total_paid'];
        }

        return (float) $this->payments()
            ->where('status', '!=',' cancelled')
            ->sum('paid_amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $paid = array_key_exists('total_paid', $this->attributes)
            ? (float) $this->attributes['total_paid']
            : (float) $this->payments()->where('status', '!=', 'cancelled')->sum('paid_amount');

        return max(0, (float) $this->rent_amount - $paid);
    }

    public function getRemainingBalanceAttribute(): float
    {
        if (array_key_exists('total_outstanding', $this->attributes)) {
            return (float) $this->attributes['total_outstanding'];
        }

        return (float) $this->payments()
            ->where('status', '!=', 'cancelled')
            ->sum('remaining_amount');
    }

    public function getDurationInMonthsAttribute(): ?int
    {
        if (!$this->end_date) {
            return null; // Open-ended lease
        }
        return $this->start_date->diffInMonths($this->end_date);
    }

    // Business Logic Methods
    public function terminate(string $reason, ?Carbon $date = null): bool
    {
        $this->update([
            'status' => 'terminated',
            'termination_date' => $date ?? now(),
            'termination_reason' => $reason,
        ]);

        // Update unit status back to available
        $this->unit->update(['status' => 'available']);

        return true;
    }

    public function renew(Carbon $newEndDate, ?float $newRentAmount = null): self
    {
        // Create new lease based on current one
        $newLease = $this->replicate();
        $newLease->start_date = $this->end_date->addDay();
        $newLease->end_date = $newEndDate;
        $newLease->rent_amount = $newRentAmount ?? $this->rent_amount;
        $newLease->status = 'draft';
        $newLease->save();

        // ✅ FIX #5: Auto-generate payment schedule for renewed lease
        if ($newLease->status === 'draft') {
            $newLease->update(['status' => 'active']);
            $newLease->generatePaymentSchedule();
        }

        // Mark current lease as renewed
        $this->update(['status' => 'renewed']);

        return $newLease;
    }

    /**
     * ✅ FIX #1: CRITICAL - Prevent duplicate payment schedule generation
     *
     * Previous implementation used firstOrCreate(['due_date' => $dueDate])
     * which only checked due_date, allowing duplicates when called multiple times.
     *
     * This fixes the bug where calling generatePaymentSchedule() twice
     * would create 24 payments instead of 12.
     */
 public function generatePaymentSchedule(): void
{
    if ($this->status !== 'active') {
        return;
    }

    $startDate = $this->start_date->copy();
    $endDate = $this->end_date ?? $startDate->copy()->addYear();

    $totalMonths = (int) $startDate->diffInMonths($endDate);
    
    // add an extra month for partials
    if ($startDate->copy()->addMonths($totalMonths)->startOfDay()->lt($endDate->copy()->startOfDay())) {
        $totalMonths++;
    }

    $totalMonths = max(1, $totalMonths);

    $freqMap = [
        'monthly' => 1,
        'quarterly' => 3,
        'semi_annually' => 6,
        'yearly' => 12,
    ];
    $frequencyMonths = $freqMap[$this->payment_frequency] ?? 1;

    $totalInstallments = (int) ceil($totalMonths / $frequencyMonths);
    $totalInstallments = max(1, $totalInstallments);

    $totalContractAmount = (float) $this->rent_amount;

    $totalCents = (int) round($totalContractAmount * 100);
    $baseInstallmentCents = intdiv($totalCents, $totalInstallments);
    $remainderCents = $totalCents - ($baseInstallmentCents * $totalInstallments);

    $installmentCentsDistribution = array_fill(0, $totalInstallments, $baseInstallmentCents);
    for ($i = 0; $i < $remainderCents; $i++) {
        $installmentCentsDistribution[$i]++;
    }

    DB::transaction(function () use ($startDate, $endDate, $installmentCentsDistribution, $baseInstallmentCents, $frequencyMonths, $totalInstallments) {
        $currentDate = $this->start_date->copy();
        $installmentIndex = 0;

        while ($installmentIndex < $totalInstallments) {
            $dueDate = $currentDate->copy()->day($this->payment_day);

            // if computed dueDate is before the lease start, move it forward one period
            if ($dueDate->lt($this->start_date)) {
                $dueDate = match($this->payment_frequency) {
                    'monthly' => $currentDate->copy()->addMonth()->day($this->payment_day),
                    'quarterly' => $currentDate->copy()->addMonths(3)->day($this->payment_day),
                    'semi_annually' => $currentDate->copy()->addMonths(6)->day($this->payment_day),
                    'yearly' => $currentDate->copy()->addYear()->day($this->payment_day),
                    default => $currentDate->copy()->addMonth()->day($this->payment_day),
                };
            }

            $exists = \App\Models\Payment::whereDate('due_date', $dueDate->toDateString())
                ->where('lease_id', $this->id)
                ->exists();

            if (!$exists) {
                $amountCents = $installmentCentsDistribution[$installmentIndex] ?? $baseInstallmentCents;
                $amount = $amountCents / 100;

                $this->payments()->create([
                    'amount' => $amount,
                    'paid_amount' => 0,
                    'remaining_amount' => $amount,
                    'status' => 'pending',
                    'company_id' => $this->company_id,
                    'due_date' => $dueDate,
                ]);
            }

            $installmentIndex++;

            // advance currentDate by payment frequency
            $currentDate = match($this->payment_frequency) {
                'monthly' => $currentDate->addMonth(),
                'quarterly' => $currentDate->addMonths(3),
                'semi_annually' => $currentDate->addMonths(6),
                'yearly' => $currentDate->addYear(),
                default => $currentDate->addMonth(),
            };
        }
    });
}
}

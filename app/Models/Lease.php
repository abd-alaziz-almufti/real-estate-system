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
            ->where('type', 'rent')
            ->where('status', '!=', 'cancelled')
            ->sum('paid_amount');
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $paid = array_key_exists('total_paid', $this->attributes)
            ? (float) $this->attributes['total_paid']
            : (float) $this->payments()->where('type', 'rent')->where('status', '!=', 'cancelled')->sum('paid_amount');

        return max(0, (float) $this->rent_amount - $paid);
    }

    public function getRemainingBalanceAttribute(): float
    {
        if (array_key_exists('total_outstanding', $this->attributes)) {
            return (float) $this->attributes['total_outstanding'];
        }

        return (float) $this->payments()
            ->where('type', 'rent')
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
     * Generate the payment schedule for this lease.
     *
     * - Handles deposit_amount: creates a separate 'deposit' payment and
     *   distributes the remaining rent across installments.
     * - Fixes the off-by-one bug where payment_day < start_date->day
     *   caused only 11 payments for a 12-month lease.
     * - Returns a status string so the UI can display feedback.
     *
     * @return string  'created' | 'exists' | 'inactive'
     */
    public function generatePaymentSchedule(): string
    {
        if ($this->status !== 'active') {
            return 'inactive';
        }

        // ── Guard: don't regenerate if schedule already exists ──
        $existingCount = $this->payments()->count();
        if ($existingCount > 0) {
            return 'exists';
        }

        $startDate = $this->start_date->copy()->startOfDay();
        $endDate   = ($this->end_date ?? $startDate->copy()->addYear())->copy()->startOfDay();

        if ($endDate->lte($startDate)) {
            return 'inactive';
        }

        // ── Calculate total months ──
        $totalMonths = (int) $startDate->diffInMonths($endDate);
        if ($startDate->copy()->addMonths($totalMonths)->lt($endDate)) {
            $totalMonths++;
        }
        $totalMonths = max(1, $totalMonths);

        $freqMap = [
            'monthly'       => 1,
            'quarterly'     => 3,
            'semi_annually' => 6,
            'yearly'        => 12,
        ];
        $frequencyMonths = $freqMap[$this->payment_frequency] ?? 1;

        $totalInstallments = (int) ceil($totalMonths / $frequencyMonths);
        $totalInstallments = max(1, $totalInstallments);

        // ── Deposit logic ──
        $depositCents = (int) round((float) ($this->deposit_amount ?? 0) * 100);
        $totalCents   = (int) round((float) $this->rent_amount * 100);

        // ── Distribute full rent amount evenly (integer cents) ──
        // Deposit is a standalone record — it does NOT reduce the installment pool.
        $baseInstallmentCents = intdiv($totalCents, $totalInstallments);
        $remainderCents       = $totalCents - ($baseInstallmentCents * $totalInstallments);

        $distribution = array_fill(0, $totalInstallments, $baseInstallmentCents);
        for ($i = 0; $i < $remainderCents; $i++) {
            $distribution[$i]++;
        }

        // ── Pre-align currentDate so payment_day never collides ──
        // If payment_day has already passed in the start month,
        // begin from next month to avoid skipping a slot.
        $currentDate = $startDate->copy();
        if ($this->payment_day < $startDate->day) {
            $currentDate = $startDate->copy()->startOfMonth()->addMonth();
        }

        DB::transaction(function () use (
            $startDate, $totalInstallments, $distribution,
            $frequencyMonths, $currentDate, $depositCents
        ) {
            // ── 1. Create deposit payment if applicable ──
            if ($depositCents > 0) {
                $depositAmount = round($depositCents / 100, 2);
                $this->payments()->create([
                    'company_id'       => $this->company_id,
                    'type'             => 'deposit',
                    'amount'           => $depositAmount,
                    'paid_amount'      => $depositAmount,
                    'remaining_amount' => 0,
                    'due_date'         => $startDate,
                    'payment_date'     => $startDate,
                    'status'           => 'paid',
                    'notes'            => 'Initial deposit payment',
                ]);
            }

            // ── 2. Create installment payments ──
            for ($i = 0; $i < $totalInstallments; $i++) {
                $dueDate = $currentDate->copy()->day($this->payment_day);
                $amount  = round($distribution[$i] / 100, 2);

                $this->payments()->create([
                    'company_id'       => $this->company_id,
                    'type'             => 'rent',
                    'amount'           => $amount,
                    'paid_amount'      => 0,
                    'remaining_amount' => $amount,
                    'due_date'         => $dueDate,
                    'status'           => 'pending',
                ]);

                $currentDate->addMonths($frequencyMonths);
            }
        });

        return 'created';
    }
public function getIsFullyPaidAttribute(): bool
{
    $paid = array_key_exists('total_paid', $this->attributes)
        ? (float) $this->attributes['total_paid']
        : (float) $this->payments()->where('type', 'rent')->where('status', '!=', 'cancelled')->sum('paid_amount');

    return $paid >= (float) $this->rent_amount && (float) $this->rent_amount > 0;
}
}
<?php
// app/Observers/PaymentObserver.php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        $this->updateLeaseBalanceAndStatus($payment);
    }

    public function deleted(Payment $payment): void
    {
        $this->updateLeaseBalanceAndStatus($payment);
    }

    protected function updateLeaseBalanceAndStatus(Payment $payment): void
    {
        $lease = $payment->lease;
        
        if (!$lease) return;

        // ✅ حساب موحد للشهري والسنوي
        // جمع كل amounts من الدفعات (غير الملغية)
        $totalAmount = $lease->payments()
            ->whereNotIn('status', ['cancelled'])
            ->sum('amount');

        // جمع كل المبالغ المدفوعة
        $totalPaid = $lease->payments()
            ->whereNotIn('status', ['cancelled'])
            ->sum('paid_amount');

        // الرصيد المتبقي = إجمالي المطلوب - إجمالي المدفوع
        $totalRemaining = max(0, $totalAmount - $totalPaid);

        // ✅ تحديد حالة العقد تلقائياً
        $hasOverduePayments = $lease->payments()
            ->where('status', 'overdue')
            ->exists();

        if ($hasOverduePayments) {
            $newStatus = 'defaulted';
        } elseif ($totalRemaining <= 0 && $totalAmount > 0) {
            $newStatus = 'paid';
        } elseif ($lease->status === 'draft') {
            $newStatus = 'draft'; // حافظ على draft إذا كان draft
        } else {
            $newStatus = 'active';
        }

        // ✅ تحديث الرصيد والحالة بدون إطلاق أحداث
        $lease->forceFill([
            'outstanding_balance' => $totalRemaining,
            'status' => $newStatus
        ])->saveQuietly();
    }
}
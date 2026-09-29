<?php

namespace App\Services;

use App\Models\SchoolPayment;
use App\Models\StudentFinanceAccount;
use App\Support\ErpAudit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SchoolPaymentService
{
    // Parse decimal input without binary floating-point arithmetic.
    public static function minor(string $amount): int
    {
        if (! preg_match('/^(-?)(\d{1,9})(?:\.(\d{1,2}))?$/D', $amount, $m)) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount with at most two decimal places.']);
        }

        return ($m[1] === '-' ? -1 : 1) * ((int) $m[2] * 100 + (int) str_pad($m[3] ?? '', 2, '0'));
    }

    public function confirm(SchoolPayment $payment, int $userId): SchoolPayment
    {
        $newlyConfirmed = false;
        $payment = DB::transaction(function () use ($payment, $userId, &$newlyConfirmed) {
            $payment = SchoolPayment::lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'confirmed') {
                return $payment;
            }
            if ($payment->status !== 'pending') {
                throw ValidationException::withMessages(['payment' => 'Only pending payments can be confirmed.']);
            }
            foreach ($payment->items()->where('kind', 'fees')->get() as $item) {
                $account = StudentFinanceAccount::where('student_id', $item->student_id)->first();
                if (! $account || $payment->paid_on->lt($account->opened_on)) {
                    throw ValidationException::withMessages(['payment' => 'Open the student fee ledger first and use a payment date on or after its opening date.']);
                }
            }
            $payment->update(['status' => 'confirmed', 'receipt_number' => 'KWE-'.str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT), 'confirmed_by' => $userId, 'confirmed_at' => now()]);
            $newlyConfirmed = true;
            ErpAudit::record('finance.payment_confirmed', $payment);

            return $payment;
        });
        if ($newlyConfirmed && $payment->payer_email) {
            $this->email($payment);
        }

        return $payment->fresh();
    }

    public function pdf(SchoolPayment $payment)
    {
        abort_if(! $payment->receipt_number, 404);

        return Pdf::loadView('finance.receipt', ['payment' => $payment->load('items', 'cashier')])->setPaper('a4');
    }

    public function email(SchoolPayment $payment): bool
    {
        abort_unless($payment->receipt_number && $payment->payer_email, 422, 'A receipt and payer email are required.');
        try {
            $bytes = $this->pdf($payment)->output();
            Mail::raw("Please find your Kweneng receipt {$payment->receipt_number} attached. Current status: {$payment->status}.", function ($mail) use ($payment, $bytes) {
                $mail->to($payment->payer_email)->subject('Kweneng receipt '.$payment->receipt_number)
                    ->attachData($bytes, $payment->receipt_number.'.pdf', ['mime' => 'application/pdf']);
            });
            $payment->update(['email_status' => 'sent', 'emailed_at' => now()]);
            ErpAudit::record('finance.receipt_emailed', $payment);

            return true;
        } catch (\Throwable $e) {
            report($e);
            $payment->update(['email_status' => 'failed']);

            return false;
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\StaffRequirement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SendStaffDocumentReminders extends Command
{
    protected $signature = 'hr:document-reminders';

    protected $description = 'Send a daily HR digest for missing, unverified, expiring and expired staff documents';

    public function handle(): int
    {
        $requirements = StaffRequirement::with('staff', 'type', 'documents')->where('required', true)
            ->whereHas('staff', fn ($q) => $q->where('status', 'active'))->get()
            ->filter(fn ($r) => ! in_array($r->status(), ['valid', 'not_applicable']));
        if ($requirements->isEmpty()) {
            $this->info('No document follow-up needed.');

            return self::SUCCESS;
        }
        $recipients = User::where('status', 'active')->where(fn ($q) => $q->where('role', 'admin')->orWhere('hr_access', true))->get();
        $failed = false;
        foreach ($recipients as $recipient) {
            $assigned = $requirements->filter(fn ($r) => $recipient->isAdmin() || ! $r->responsible_user_id || $r->responsible_user_id === $recipient->id);
            if ($assigned->isEmpty()) {
                continue;
            }
            $key = 'hr-reminder:'.today()->format('Y-m-d').':'.$recipient->id;
            $lock = Cache::lock($key.':lock', 120);
            if (! $lock->get()) {
                continue;
            }
            try {
                if (Cache::has($key)) {
                    continue;
                }
                Mail::raw($assigned->count().' staff document requirements need follow-up. Sign in to review the confidential HR register: '.route('hr.staff.index'), fn ($mail) => $mail->to($recipient->email)->subject('Kweneng staff document follow-up'));
                Cache::put($key, true, now()->addDays(2));
            } catch (\Throwable $e) {
                report($e);
                $failed = true;
            } finally {
                $lock->release();
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

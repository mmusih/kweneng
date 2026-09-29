<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ErpAudit
{
    public static function record(string $action, Model $subject, array $details = []): void
    {
        DB::table('erp_audit_events')->insert(['user_id' => auth()->id(), 'action' => $action, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->id, 'details' => json_encode($details), 'created_at' => now(), 'updated_at' => now()]);
    }
}

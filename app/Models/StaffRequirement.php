<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffRequirement extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['required' => 'boolean', 'application_date' => 'date'];

    public function staff()
    {
        return $this->belongsTo(StaffProfile::class, 'staff_profile_id');
    }

    public function type()
    {
        return $this->belongsTo(StaffDocumentType::class, 'staff_document_type_id');
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class)->orderByDesc('id');
    }

    public function currentDocument(): ?StaffDocument
    {
        return $this->documents->first(fn ($d) => $d->verified_at !== null);
    }

    public function status(): string
    {
        if (! $this->required) {
            return 'not_applicable';
        }
        $doc = $this->currentDocument();
        if (! $doc) {
            return $this->documents->isEmpty() ? 'missing' : 'awaiting_verification';
        }
        if ($doc->expires_on?->lt(today())) {
            return 'expired';
        }
        if ($doc->expires_on?->lte(today()->addDays($this->type->reminder_days))) {
            return 'expiring_soon';
        }

        return 'valid';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDocumentType extends Model
{
    protected $guarded = ['id'];

    public function appliesTo(StaffProfile $staff): bool
    {
        return match ($this->applies_to) {
            'all' => true, 'teachers' => $staff->is_teacher,
            'citizens' => $staff->is_citizen, 'non_citizens' => ! $staff->is_citizen, default => false,
        };
    }
}

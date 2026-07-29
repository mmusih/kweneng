<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_import_batch_id',
        'excel_row_number',
        'form',
        'surname',
        'student_names',
        'opening_balance',
        'payment',
        'closing_balance',
        'matched_student_id',
        'match_status',
        'match_notes',
        'possible_student_ids',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'payment' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'possible_student_ids' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(FeeImportBatch::class, 'fee_import_batch_id');
    }

    public function matchedStudent()
    {
        return $this->belongsTo(Student::class, 'matched_student_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'academic_year_id',
        'term_id',
        'uploaded_by',
        'status',
        'total_rows',
        'matched_rows',
        'unmatched_rows',
        'ambiguous_rows',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(FeeImportRow::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

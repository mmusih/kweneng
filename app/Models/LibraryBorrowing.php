<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LibraryBorrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_copy_id',
        'student_id',
        'teacher_id',
        'issued_by',
        'issued_at',
        'due_at',
        'returned_at',
        'status',
        'remarks',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'returned_at' => 'date',
    ];

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['borrowed', 'overdue']);
    }

    public function isOverdue(): bool
    {
        return is_null($this->returned_at)
            && in_array($this->status, ['borrowed', 'overdue'], true)
            && Carbon::parse($this->due_at)->isPast();
    }
}

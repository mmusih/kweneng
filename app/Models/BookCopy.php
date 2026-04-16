<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'accession_no',
        'barcode',
        'shelf_location',
        'status',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function borrowings()
    {
        return $this->hasMany(LibraryBorrowing::class);
    }

    public function activeBorrowing()
    {
        return $this->hasOne(LibraryBorrowing::class)->whereIn('status', ['borrowed', 'overdue']);
    }
}

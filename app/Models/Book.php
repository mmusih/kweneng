<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_category_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'publication_year',
        'description',
        'thumbnail_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'publication_year' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function getTotalCopiesAttribute(): int
    {
        return $this->copies()->count();
    }

    public function getAvailableCopiesAttribute(): int
    {
        return $this->copies()->where('is_available', true)->count();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'file_path',
        'original_filename',
        'academic_year_id',
        'uploaded_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function getCategoryLabel(string $category): string
    {
        return match ($category) {
            'timetable'  => 'Timetable',
            'prospectus' => 'School Prospectus',
            'booklist'   => 'Book List',
            'uniform'    => 'Uniform Price List',
            default      => ucfirst($category),
        };
    }

    public static function getCategoryIcon(string $category): string
    {
        return match ($category) {
            'timetable'  => '🗓️',
            'prospectus' => '📖',
            'booklist'   => '📚',
            'uniform'    => '👕',
            default      => '📄',
        };
    }

    public static function getCategoryColor(string $category): string
    {
        return match ($category) {
            'timetable'  => 'blue',
            'prospectus' => 'purple',
            'booklist'   => 'green',
            'uniform'    => 'orange',
            default      => 'gray',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_id',
        'title',
        'description',
        'estimated_periods',
        'sort_order',
    ];

    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    public function subtopics(): HasMany
    {
        return $this->hasMany(SyllabusSubtopic::class)->orderBy('sort_order')->orderBy('id');
    }

    public function schemeItems(): HasMany
    {
        return $this->hasMany(SchemeItem::class);
    }
}

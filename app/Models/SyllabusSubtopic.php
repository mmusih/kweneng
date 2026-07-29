<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusSubtopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_topic_id',
        'title',
        'sort_order',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(SyllabusTopic::class, 'syllabus_topic_id');
    }

    public function schemeItemSubtopics(): HasMany
    {
        return $this->hasMany(SchemeItemSubtopic::class);
    }
}

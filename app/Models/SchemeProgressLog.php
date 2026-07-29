<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeProgressLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id',
        'scheme_item_id',
        'scheme_item_subtopic_id',
        'user_id',
        'action',
        'old_status',
        'new_status',
        'comment',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(SchemeItem::class, 'scheme_item_id');
    }

    public function subtopic(): BelongsTo
    {
        return $this->belongsTo(SchemeItemSubtopic::class, 'scheme_item_subtopic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

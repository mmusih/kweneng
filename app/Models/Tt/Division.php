<?php

namespace App\Models\Tt;

use App\Models\ClassModel;
use Database\Factories\Tt\DivisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use HasFactory;

    protected $table = 'tt_divisions';

    protected $fillable = [
        'class_id',
        'division_tag',
        'name',
        'shared_key',
    ];

    protected static function newFactory(): DivisionFactory
    {
        return DivisionFactory::new();
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'tt_division_id');
    }
}

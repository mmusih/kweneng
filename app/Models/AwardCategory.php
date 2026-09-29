<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AwardCategory extends Model
{
    protected $fillable = ['name', 'type', 'badge_icon', 'badge_color', 'default_citation', 'headmaster_only', 'active'];

    protected $casts = ['headmaster_only' => 'boolean', 'active' => 'boolean'];

    public function runs()
    {
        return $this->hasMany(AwardRun::class);
    }
}

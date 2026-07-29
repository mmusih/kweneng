<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'subject',
        'body',
        'is_read_by_admin',
        'is_read_by_parent',
        'last_reply_at',
    ];

    protected $casts = [
        'is_read_by_admin'  => 'boolean',
        'is_read_by_parent' => 'boolean',
        'last_reply_at'     => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(ParentMessageReply::class, 'message_id')->orderBy('created_at');
    }

    public function latestReply()
    {
        return $this->hasOne(ParentMessageReply::class, 'message_id')->latestOfMany();
    }
}

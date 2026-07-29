<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentMessageReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'sender_role',
        'sender_user_id',
        'body',
    ];

    public function message()
    {
        return $this->belongsTo(ParentMessage::class, 'message_id');
    }

    public function senderUser()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}

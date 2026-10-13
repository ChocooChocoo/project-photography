<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageModel extends Model
{
    protected $table = 'tbl_messages';

    protected $fillable = ['conversation_id', 'sender_id', 'body', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function sender()
    {
        return $this->belongsTo(UserModel::class, 'sender_id');
    }

    public function conversation()
    {
        return $this->belongsTo(ConversationModel::class, 'conversation_id');
    }
}

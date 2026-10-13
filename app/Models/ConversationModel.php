<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationModel extends Model
{
    protected $table = 'tbl_conversations';

    protected $fillable = ['user_one_id', 'user_two_id', 'booking_id', 'booking_scope', 'last_message_at', 'last_notified_at'];

    protected $casts = ['last_message_at' => 'datetime', 'last_notified_at' => 'datetime'];

    public function messages()
    {
        return $this->hasMany(MessageModel::class, 'conversation_id');
    }

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function userOne()
    {
        return $this->belongsTo(UserModel::class, 'user_one_id');
    }

    public function userTwo()
    {
        return $this->belongsTo(UserModel::class, 'user_two_id');
    }

    public function scopeForUser($query, int $id)
    {
        return $query->where(fn ($query) => $query->where('user_one_id', $id)->orWhere('user_two_id', $id));
    }

    public function otherUser(int $id): ?UserModel
    {
        return $this->user_one_id === $id ? $this->userTwo : $this->userOne;
    }
}

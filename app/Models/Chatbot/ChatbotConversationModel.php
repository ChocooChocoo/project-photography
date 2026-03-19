<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserModel;

class ChatbotConversationModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_chatbot_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'owner_id',
        'config_id',
        'status',
        'started_at',
        'ended_at',
        'message_count',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'message_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user (client) in this conversation.
     */
    public function user()
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }

    /**
     * Get the owner whose chatbot is being used.
     */
    public function owner()
    {
        return $this->belongsTo(UserModel::class, 'owner_id');
    }

    /**
     * Get the config used for this conversation.
     */
    public function config()
    {
        return $this->belongsTo(ChatbotConfigModel::class, 'config_id');
    }

    /**
     * Get the messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(ChatbotMessageModel::class, 'conversation_id');
    }

    /**
     * Check if conversation is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * End the conversation.
     */
    public function end()
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }

    /**
     * Generate a unique session ID.
     */
    public static function generateSessionId()
    {
        do {
            $sessionId = 'CHAT-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
        } while (self::where('session_id', $sessionId)->exists());

        return $sessionId;
    }

    /**
     * Scope to get active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get conversations by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get conversations by owner.
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
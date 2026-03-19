<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessageModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_chatbot_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'message',
        'intent_id',
        'was_helpful',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'was_helpful' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(ChatbotConversationModel::class, 'conversation_id');
    }

    /**
     * Get the intent that was matched for this bot response.
     */
    public function intent()
    {
        return $this->belongsTo(ChatbotIntentModel::class, 'intent_id');
    }

    /**
     * Check if message is from user.
     */
    public function isFromUser()
    {
        return $this->sender_type === 'user';
    }

    /**
     * Check if message is from bot.
     */
    public function isFromBot()
    {
        return $this->sender_type === 'bot';
    }

    /**
     * Mark message as helpful.
     */
    public function markAsHelpful()
    {
        $this->update(['was_helpful' => true]);
    }

    /**
     * Mark message as not helpful.
     */
    public function markAsNotHelpful()
    {
        $this->update(['was_helpful' => false]);
    }

    /**
     * Scope to get messages by sender type.
     */
    public function scopeBySender($query, $senderType)
    {
        return $query->where('sender_type', $senderType);
    }

    /**
     * Scope to get messages in chronological order.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('created_at', 'asc');
    }
}
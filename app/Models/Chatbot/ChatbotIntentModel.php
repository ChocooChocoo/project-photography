<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotIntentModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_chatbot_intents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'config_id',
        'intent_name',
        'trigger_keywords',
        'response_text',
        'response_type',
        'image_url',
        'priority',
        'is_active',
        'match_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'trigger_keywords' => 'array',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'match_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the config that owns this intent.
     */
    public function config()
    {
        return $this->belongsTo(ChatbotConfigModel::class, 'config_id');
    }

    /**
     * Get the quick replies for this intent.
     */
    public function quickReplies()
    {
        return $this->hasMany(ChatbotQuickReplyModel::class, 'intent_id');
    }

    /**
     * Get the messages that used this intent.
     */
    public function messages()
    {
        return $this->hasMany(ChatbotMessageModel::class, 'intent_id');
    }

    /**
     * Get active quick replies in order.
     */
    public function activeQuickReplies()
    {
        return $this->quickReplies()
                    ->where('is_active', true)
                    ->orderBy('position');
    }

    /**
     * Increment match count.
     */
    public function incrementMatchCount()
    {
        $this->increment('match_count');
    }

    /**
     * Scope a query to only include active intents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
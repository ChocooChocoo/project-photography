<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotQuickReplyModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_chatbot_quick_replies';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'intent_id',
        'reply_text',
        'action_value',
        'action_type',
        'position',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the intent that owns this quick reply.
     */
    public function intent()
    {
        return $this->belongsTo(ChatbotIntentModel::class, 'intent_id');
    }

    /**
     * Check if this quick reply triggers another intent.
     */
    public function triggersIntent()
    {
        return $this->action_type === 'trigger_intent' && !empty($this->action_value);
    }

    /**
     * Check if this quick reply opens a URL.
     */
    public function opensUrl()
    {
        return $this->action_type === 'open_url' && !empty($this->action_value);
    }

    /**
     * Get the action display label.
     */
    public function getActionLabelAttribute()
    {
        $labels = [
            'trigger_intent' => 'Trigger Intent',
            'open_url' => 'Open URL',
            'none' => 'No Action',
        ];

        return $labels[$this->action_type] ?? $this->action_type;
    }

    /**
     * Scope active quick replies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
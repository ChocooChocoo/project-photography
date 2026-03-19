<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\UserModel;

class ChatbotConfigModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_chatbot_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_id',
        'config_name',
        'welcome_message',
        'fallback_message',
        'is_active',
        'bot_name',
        'bot_avatar',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owner that owns this chatbot config.
     */
    public function owner()
    {
        return $this->belongsTo(UserModel::class, 'owner_id');
    }

    /**
     * Get the intents for this chatbot config.
     */
    public function intents()
    {
        return $this->hasMany(ChatbotIntentModel::class, 'config_id');
    }

    /**
     * Get the conversations using this config.
     */
    public function conversations()
    {
        return $this->hasMany(ChatbotConversationModel::class, 'config_id');
    }

    /**
     * Get active intents.
     */
    public function activeIntents()
    {
        return $this->intents()->where('is_active', true);
    }

    /**
     * Scope a query to only include active configs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by owner.
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
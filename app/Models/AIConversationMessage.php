<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIConversationMessage extends Model
{
    use HasFactory;

    protected $table = 'ai_conversation_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns this message
     */
    public function conversation()
    {
        return $this->belongsTo(AIConversation::class);
    }
}

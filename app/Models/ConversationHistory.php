<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent',
        'message',
        'sender',
        'sess_grade_level' // Added to store grade level from session
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

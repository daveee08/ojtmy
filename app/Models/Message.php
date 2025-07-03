<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'agent_id',
        'user_id',
        'parameter_inputs',
        'sender',
        'topic',
        'message_id', // For threaded messages
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function parameterInput()
    {
        return $this->belongsTo(ParameterInput::class, 'parameter_inputs');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParameterInput extends Model
{
    protected $fillable = [
        'input',
        'parameter_id',
    ];

    public function parameter()
    {
        return $this->belongsTo(AgentParameter::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'parameter_inputs');
    }
}
// This model represents the parameter inputs for an agent.
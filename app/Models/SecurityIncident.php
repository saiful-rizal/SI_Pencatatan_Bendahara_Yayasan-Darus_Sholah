<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityIncident extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'method',
        'path',
        'user_agent',
        'reason',
        'payload_excerpt',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

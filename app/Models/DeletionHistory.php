<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionHistory extends Model
{
    protected $fillable = [
        'menu',
        'entity_type',
        'entity_id',
        'label',
        'deleted_by',
        'deleted_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}

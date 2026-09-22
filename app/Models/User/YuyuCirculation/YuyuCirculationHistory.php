<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;

class YuyuCirculationHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'step_id', 'user_id', 'action', 'comment',
        'created_id', 'created_name', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];
}

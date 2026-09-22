<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationTarget extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['document_id', 'user_id', 'target_source', 'status', 'confirmed_at'];

    protected $casts = ['confirmed_at' => 'datetime'];
}

<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationStep extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = [
        'document_id', 'step_no', 'action_type', 'section_id', 'group_id',
        'approver_user_id', 'status', 'acted_at', 'comment',
    ];

    protected $casts = ['acted_at' => 'datetime'];
}

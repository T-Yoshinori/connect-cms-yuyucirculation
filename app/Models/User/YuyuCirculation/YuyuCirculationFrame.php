<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationFrame extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['circulation_id', 'frame_id', 'view_format', 'view_count'];
}

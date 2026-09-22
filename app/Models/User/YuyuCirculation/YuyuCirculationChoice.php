<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationChoice extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['question_id', 'choice_no', 'choice_text'];
}

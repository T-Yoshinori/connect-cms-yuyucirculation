<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationAnswer extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['document_id', 'question_id', 'user_id', 'answer'];
}

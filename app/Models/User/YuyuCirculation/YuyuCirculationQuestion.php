<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationQuestion extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['document_id', 'question_no', 'question_type', 'question_text', 'required'];
    protected $casts = ['required' => 'boolean'];

    public function choices()
    {
        return $this->hasMany(YuyuCirculationChoice::class, 'question_id')->orderBy('choice_no');
    }
}

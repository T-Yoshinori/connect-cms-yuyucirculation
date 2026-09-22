<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationTemplateStep extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['template_id', 'step_no', 'section_mode', 'section_id', 'group_id', 'action_type'];

    public function template()
    {
        return $this->belongsTo(YuyuCirculationTemplate::class, 'template_id');
    }
}

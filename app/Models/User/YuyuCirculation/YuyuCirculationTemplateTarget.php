<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationTemplateTarget extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['template_id', 'sort_order', 'target_type', 'section_id', 'group_id', 'user_id'];

    public function template()
    {
        return $this->belongsTo(YuyuCirculationTemplate::class, 'template_id');
    }
}

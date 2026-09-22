<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationTemplate extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = [
        'circulation_id', 'name', 'template_code', 'workflow_type',
        'post_circulation_enabled', 'is_active',
    ];

    protected $casts = [
        'post_circulation_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function steps()
    {
        return $this->hasMany(YuyuCirculationTemplateStep::class, 'template_id')->orderBy('step_no');
    }

    public function targets()
    {
        return $this->hasMany(YuyuCirculationTemplateTarget::class, 'template_id')->orderBy('sort_order');
    }
}

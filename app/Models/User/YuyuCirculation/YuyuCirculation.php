<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculation extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = ['bucket_id', 'name', 'mail_notification_enabled'];

    protected $casts = ['mail_notification_enabled' => 'boolean'];

    public function templates()
    {
        return $this->hasMany(YuyuCirculationTemplate::class, 'circulation_id');
    }
}

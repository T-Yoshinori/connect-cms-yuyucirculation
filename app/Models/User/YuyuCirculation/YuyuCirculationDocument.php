<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationDocument extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = [
        'circulation_id', 'template_id', 'document_type', 'title', 'body',
        'applicant_user_id', 'applicant_section_id', 'approval_status',
        'circulation_status', 'response_visibility', 'status', 'submitted_at', 'decided_at',
        'circulation_started_at', 'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decided_at' => 'datetime',
        'circulation_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(YuyuCirculationStep::class, 'document_id')->orderBy('step_no');
    }

    public function histories()
    {
        return $this->hasMany(YuyuCirculationHistory::class, 'document_id')->orderBy('created_at');
    }

    public function files()
    {
        return $this->hasMany(YuyuCirculationFile::class, 'document_id')->orderBy('id');
    }

    public function questions()
    {
        return $this->hasMany(YuyuCirculationQuestion::class, 'document_id')->orderBy('question_no');
    }

    public function answers()
    {
        return $this->hasMany(YuyuCirculationAnswer::class, 'document_id');
    }

    public function targets()
    {
        return $this->hasMany(YuyuCirculationTarget::class, 'document_id');
    }

    public function notifications()
    {
        return $this->hasMany(YuyuCirculationNotification::class, 'document_id');
    }
}

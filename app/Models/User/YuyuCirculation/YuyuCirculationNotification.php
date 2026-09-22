<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;

class YuyuCirculationNotification extends Model
{
    protected $fillable = [
        'user_id', 'document_id', 'notification_type', 'title', 'message', 'detail_url',
        'read_at', 'mail_sent_at', 'mail_error',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'mail_sent_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(YuyuCirculationDocument::class, 'document_id');
    }
}

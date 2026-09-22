<?php

namespace App\Models\User\YuyuCirculation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Userable;

class YuyuCirculationFile extends Model
{
    use SoftDeletes;
    use Userable;

    protected $fillable = [
        'document_id', 'original_name', 'stored_name', 'directory',
        'mime_type', 'file_size', 'uploaded_user_id',
    ];
    public function document()
    {
        return $this->belongsTo(YuyuCirculationDocument::class, 'document_id');
    }
}

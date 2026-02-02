<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionSettings extends Model
{
    use HasFactory;

    public $table = 'admission_settings';
    protected $fillable = [
        'settingId',
        'session',
        'resumptionDate',
        'orientationDate',
        'acceptanceDeadline',
        'registrar',
        'status',
    ];
    protected $primaryKey = 'settingId';

    public function session_details()
    {
        return $this->belongsTo(AcademicSession::class, 'session', 'sessionId');
    }
}

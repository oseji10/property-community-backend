<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admissions extends Model
{
    use HasFactory;

    public $table = 'admissions';
    protected $fillable = [
        'admissionId',
        'applicationId',
        'programmeId',
        'session',
    ];
    protected $primaryKey = 'admissionId';

    public function application()
    {
        return $this->belongsTo(Applications::class, 'applicationId', 'applicationId');
    }

    public function programme()
    {
        return $this->belongsTo(Programmes::class, 'programmeId', 'programmeId');
    }

    public function session_details()
    {
        return $this->belongsTo(AcademicSession::class, 'session', 'sessionId');
    }

    public function admission_setting()
    {
        return $this->hasOne(AdmissionSettings::class, 'session', 'session');
    }
}

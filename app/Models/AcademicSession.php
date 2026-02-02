<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    use HasFactory;

    public $table = 'academic_sessions';
    protected $fillable = [
        'sessionId',
        'sessionName',
        'startDate',
        'endDate',
        'status',
    ];
    protected $primaryKey = 'sessionId';

}

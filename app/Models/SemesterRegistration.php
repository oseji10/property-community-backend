<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SemesterRegistration extends Model
{
    use HasFactory;
  

    public $table = 'semester_registrations';
    protected $fillable = [
        'semesterId',
        'semesterName',
        'status',
    ];
    protected $primaryKey = 'semesterId';

    public function staff_type()
    {
        return $this->belongsTo(StaffType::class, 'staffType', 'typeId');
    } 

 

}

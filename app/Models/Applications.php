<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Applications extends Model
{
    use HasFactory;

    public $table = 'applications';
    protected $fillable = [
        'applicationId',
        'applicationType',
        'userId',
        'alternatePhoneNumber',
        'licenceId',
        'jambId',
        'dateOfBirth',
        'gender',
        'slipPrintCount',
        'admissionPrintCount',
        'isActive',
        'batch',
        'isPresent',
        'status',
        'hall',
        'seatNumber',
        'maritalStatus',
    ];
    protected $primaryKey = 'applicationId';
    public $incrementing = false;
    protected $keyType = 'string';

    public function payments()
    {
        return $this->belongsTo(Payment::class, 'applicationId', 'applicationId');
    } 

  public function batch_relation()
{
    return $this->belongsTo(Batch::class, 'batch', 'batchId');
}

  public function olevelresults()
{
    return $this->hasMany(OlevelResult::class, 'applicationId', 'applicationId');
}


    public function application_type()
    {
        return $this->belongsTo(ApplicationType::class, 'applicationType', 'typeId');
    } 

     public function users()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    } 

     public function jamb()
    {
        return $this->belongsTo(JAMB::class, 'jambId', 'jambId');
    } 

      public function photograph()
    {
        return $this->belongsTo(Photo::class, 'applicationId', 'applicationId');
    } 

    public function hall_info()
    {
        return $this->belongsTo(Halls::class, 'hall', 'hallId');
    } 

    public function hall_assignment()
    {
        return $this->hasOne(HallAssignment::class, 'applicationId', 'applicationId');
    } 
}

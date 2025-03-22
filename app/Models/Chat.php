<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['job_id', 'recruiter_id', 'employee_id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id')
            ->select(['id', 'name', 'image'])
            ->withDefault([
                'name' => 'Unknown Recruiter',
                'image' => url('/uploads/profile_image/avatar.jpg')
            ]);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id')
            ->select(['id', 'name', 'image'])
            ->withDefault([
                'name' => 'Unknown Employee',
                'image' => url('/uploads/profile_image/avatar.jpg')
            ]);
    }

    // Accessor for recruiter image
    public function getRecruiterImageAttribute()
    {
        return $this->recruiter->image
            ? url('/uploads/profile_image/' . $this->recruiter->image)
            : url('/uploads/profile_image/avatar.jpg');
    }

    // Accessor for employee image
    public function getEmployeeImageAttribute()
    {
        return $this->employee->image
            ? url('/uploads/profile_image/' . $this->employee->image)
            : url('/uploads/profile_image/avatar.jpg');
    }
}

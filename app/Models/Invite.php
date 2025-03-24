<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['inviteStatus', 'jobTitle'];

    protected $casts = [
        'status' => 'string',
        'job_id' => 'integer',
    ];

    // Accessor for invite status
    public function getInviteStatusAttribute()
    {
        return match ($this->status) {
            'active', 1 => 'Live',
            'expired', 2 => 'Closed',
            'accepted', 3 => 'Accepted',
            'rejected', 4 => 'Rejected',
            default => 'Draft',
        };
    }

    // Accessor for job title
    public function getJobTitleAttribute()
    {
        return $this->job ? $this->job->title : null;
    }

    // Define relationship with Job model
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $appends = ['inviteStatus'];
    protected $casts = [
        'status' => 'string',

    ];

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
public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

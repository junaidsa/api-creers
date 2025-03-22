<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class JobApplication extends Model
{
    use HasFactory , SoftDeletes;
    protected $guarded = [];
    protected $appends = ['appliedStatus'];
    protected $casts = [
        'applied_status' => 'integer',

    ];

    public function getAppliedStatusAttribute()
    {
        return match ($this->is_verified) {
            1 => 'Live',
            2 => 'Closed',
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

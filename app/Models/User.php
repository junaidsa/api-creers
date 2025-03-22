<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role',
        'company_name',
        'company_role',
        'nearest_landmark',
        'experience_level',
        'recent_job_role',
        'experience',
        'job_role',
        'location',
        'work_mode',
        'image',
        'status',
        'skiles_ids',
        'education',
        'language_id',
        'category_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $appends = ['categoryName','subCategories','languageName'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'skiles_ids' => 'array',
        'language_id' => 'array',
        'working_days' => 'array',
        'category' => 'integer',
    ];

    // User's preferred job role (single category)
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // User's skills (multiple subcategories)
    public function skills()
    {
        return $this->hasMany(Category::class, 'parent_id', 'category_id');
    }
    public function getCategoryNameAttribute()
    {
        return $this->category()->value('name');
    }

    public function getsubCategoriesAttribute()
    {
        $skillsArray = is_string($this->skiles_ids) ? json_decode($this->skiles_ids, true) : $this->skiles_ids;
        if (!is_array($skillsArray) || empty($skillsArray)) {
            return collect([]);
        }
        return Category::whereIn('id', $skillsArray)->get(['id', 'name']);
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function getLanguageNameAttribute()
{
    $languageArray = is_string($this->language_id) ? json_decode($this->language_id, true) : $this->language_id;

    if (!is_array($languageArray) || empty($languageArray)) {
        return collect([]);
    }

    return Language::whereIn('id', $languageArray)->get(['id', 'name']);
}
}

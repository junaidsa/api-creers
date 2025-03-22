<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    protected $fillable = ['id','user_id','title','category_id','skiles_ids','work_place','description','vacancy','location','salary_min','salary_max','salary_type','salary_type','working_hours','working_days','language_id','english_level','gender','interview_type','description','benefits','qualifications','experience','is_verified','status'];
    protected $appends = ['categoryName','subCategories','languageName','status'];
    protected $casts = [
        'skiles_ids' => 'array',
        'working_days' => 'array',
        'category' => 'integer',
        'user_id' => 'integer',
        'language_id' => 'array',
        'status' => 'integer',
    ];

    public function getStatusAttribute()
    {
        return match ($this->is_verified) {
            1 => 'Live',
            2 => 'Closed',
            default => 'Draft',
        };
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function getCategoryNameAttribute()
    {
        return $this->category()->value('name');
    }

    public function getSubCategoriesAttribute()
    {
        $skillsArray = $this->skiles_ids;

        // Ensure it's an array
        if (is_string($skillsArray)) {
            $skillsArray = json_decode($skillsArray, true);
        }

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

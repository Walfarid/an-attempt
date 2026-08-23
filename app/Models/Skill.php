<?php

namespace App\Models;

use App\Enums\SkillCategory;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
    ];

    protected function casts(): array
    {
        return [
            'category' => SkillCategory::class,
        ];
    }
}

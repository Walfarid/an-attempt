<?php

namespace App\Models;

use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'company',
        'location',
        'started_at',
        'ended_at',
        'summary',
        'highlights',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'highlights' => 'array',
        ];
    }
}

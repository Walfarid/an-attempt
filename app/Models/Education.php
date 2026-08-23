<?php

namespace App\Models;

use Database\Factories\EducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    /** @use HasFactory<EducationFactory> */
    use HasFactory;

    /**
     * The table associated with the model — the inflector treats
     * "education" as uncountable.
     */
    protected $table = 'educations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'school',
        'degree',
        'started_at',
        'ended_at',
        'details',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'details' => 'array',
        ];
    }
}

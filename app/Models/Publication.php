<?php

namespace App\Models;

use Database\Factories\PublicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    /** @use HasFactory<PublicationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'citation',
        'venue',
        'year',
        'doi_url',
        'sort_order',
    ];
}

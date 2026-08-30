<?php

namespace App\Models;

use App\Support\Markdown;
use Database\Factories\PrivacyPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $body_html Markdown body rendered to HTML, set before serialization
 */
class PrivacyPolicy extends Model
{
    /** @use HasFactory<PrivacyPolicyFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'body',
    ];

    /**
     * The singleton privacy policy. There is always exactly one row;
     * the seeder creates it and the dashboard edits it. If the row has
     * not been seeded yet (e.g. migration ran but seeder did not), an
     * empty placeholder is created so the page does not 404.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], ['body' => '']);
    }

    /**
     * The policy body rendered from Markdown to HTML.
     */
    public function bodyHtml(): string
    {
        return Markdown::toHtml($this->body);
    }
}

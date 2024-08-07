<?php declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Casts\AddressUrl;
use App\Domain\Casts\Boolean;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property string $uuid
 * @property string $title
 * @property string $address
 * @property string $template
 * @property string $templateFile
 * @property bool $recaptcha
 * @property bool $authorSend
 * @property array $origin
 * @property array $mailto
 * @property string $duplicate
 * @property Collection<FormData> $entries
 */
class Form extends Model
{
    use HasUuids;

    protected $table = 'form';

    protected $primaryKey = 'uuid';

    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'address',
        'template',
        'templateFile',
        'recaptcha',
        'authorSend',
        'origin',
        'mailto',
        'duplicate',
    ];

    protected $guarded = [];

    protected $casts = [
        'title' => 'string',
        'address' => AddressUrl::class,
        'template' => 'string',
        'templateFile' => 'string',
        'recaptcha' => Boolean::class,
        'authorSend' => Boolean::class,
        'origin' => 'string',
        'mailto' => 'string',
        'duplicate' => 'string',
    ];

    protected $attributes = [
        'title' => '',
        'address' => '',
        'template' => '',
        'templateFile' => '',
        'recaptcha' => true,
        'authorSend' => false,
        'origin' => '',
        'mailto' => '',
        'duplicate' => '',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(FormData::class, 'form_uuid', 'uuid');
    }

    protected function origin(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => explode(PHP_EOL, $value),
        );
    }

    protected function mailto(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => explode(PHP_EOL, $value),
        );
    }
}

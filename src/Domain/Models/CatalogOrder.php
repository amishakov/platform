<?php declare(strict_types=1);

namespace App\Domain\Models;

use App\Domain\Casts\Catalog\Order\Delivery;
use App\Domain\Casts\Email;
use App\Domain\Casts\Phone;
use App\Domain\Casts\Uuid;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property string $uuid
 * @property string $serial
 * @property string $user_uuid
 * @property string $status_uuid
 * @property string $payment_uuid
 * @property array $delivery
 * @property string $shipping
 * @property string $comment
 * @property string $phone
 * @property string $email
 * @property \DateTime $date
 * @property string $system
 * @property string $external_id
 * @property string $export
 * @property Collection<CatalogProduct> $products
 * @property Reference $status
 * @property Reference $payment
 */
class CatalogOrder extends Model
{
    use HasUuids;

    protected $table = 'catalog_order';

    protected $primaryKey = 'uuid';

    public const CREATED_AT = 'date';
    public const UPDATED_AT = null;

    protected $fillable = [
        'serial',
        'user_uuid',
        'status_uuid',
        'payment_uuid',
        'delivery',
        'shipping',
        'comment',
        'phone',
        'email',
        'date',
        'system',
        'external_id',
        'export',
    ];

    protected $guarded = [];

    protected $casts = [
        'serial' => 'string',
        'user_uuid' => Uuid::class,
        'status_uuid' => Uuid::class,
        'payment_uuid' => Uuid::class,
        'delivery' => Delivery::class,
        'shipping' => 'datetime',
        'comment' => 'string',
        'phone' => Phone::class,
        'email' => Email::class,
        'date' => 'datetime',
        'system' => 'string',
        'external_id' => 'string',
        'export' => 'string',
    ];

    protected $attributes = [
        'serial' => '',
        'user_uuid' => null,
        'status_uuid' => null,
        'payment_uuid' => null,
        'delivery' => '{}',
        'shipping' => null,
        'comment' => '',
        'phone' => '',
        'email' => '',
        'date' => 'now',
        'system' => '',
        'external_id' => '',
        'export' => 'manual',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogProduct::class,
            'catalog_order_product',
            'order_uuid',
            'product_uuid',
            'uuid',
            'uuid'
        )->withPivot(['price', 'price_type', 'count', 'discount', 'tax', 'tax_included']);
    }

    public function status(): HasOne
    {
        return $this->hasOne(Reference::class, 'uuid', 'status_uuid');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Reference::class, 'uuid', 'payment_uuid');
    }

    public function totalSum(): float
    {
        return $this->products->sum(fn (CatalogProduct $el) => $el->totalSum());
    }

    public function totalDiscount(): float
    {
        return $this->products->sum(fn (CatalogProduct $el) => $el->totalDiscount());
    }

    public function totalTax($precision = 0): float
    {
        return $this->products->sum(fn (CatalogProduct $el) => $el->totalTax($precision));
    }

    public function toArray(): array
    {
        return array_merge(
            parent::toArray(),
            [
                'status' => $this->status?->toArray(),
                'payment' => $this->payment?->toArray(),
                'products' => $this->products()->getResults()->keyBy('uuid')->map(function (CatalogProduct $item) {
                    return [
                        'title' => $item->title,
                        'address' => $item->address,
                        'type' => $item->type,

                        'price' => $item->totalPrice(),
                        'discount' => $item->totalDiscount(),
                        'tax' => $item->totalTax(),

                        'raw' => [
                            'price' => $item->pivot->price ?? 0,
                            'discount' => $item->pivot->discount ?? 0,
                            'tax' => $item->pivot->tax ?? 0,
                            'tax_included' => $item->pivot->tax_included ?? 0,
                        ],

                        'count' => $item->totalCount(),
                        'amount' => $item->totalSum(),

                        'files' => $item->files->map(function (File $file) {
                            return array_intersect_key($file->toArray(), array_flip(['uuid', 'name', 'ext', 'type', 'order', 'link', 'path']));
                        }),
                    ];
                }),
                'calculated' => [
                    'discount' => $this->totalDiscount(),
                    'tax' => $this->totalTax(),
                    'total' => $this->totalSum(),
                ],
            ],
        );
    }
}

<?php declare(strict_types=1);

namespace App\Domain\Entities\Catalog;

use App\Domain\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface as Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(name="catalog_order_product")
 * @ORM\HasLifecycleCallbacks
 */
class OrderProduct extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected \Ramsey\Uuid\UuidInterface $uuid;

    public function getUuid(): \Ramsey\Uuid\UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @ORM\Column(type="uuid")
     */
    protected \Ramsey\Uuid\UuidInterface $order_uuid;

    /**
     * @ORM\ManyToOne(targetEntity="App\Domain\Entities\Catalog\Order")
     * @ORM\JoinColumn(name="order_uuid", referencedColumnName="uuid")
     */
    protected Order $order;

    /**
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        if (is_a($order, Order::class)) {
            $this->order = $order;
            $this->order_uuid = $order->getUuid();
        }

        return $this;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @ORM\Column(type="uuid")
     */
    protected \Ramsey\Uuid\UuidInterface $product_uuid;

    /**
     * @ORM\ManyToOne(targetEntity="App\Domain\Entities\Catalog\Product")
     * @ORM\JoinColumn(name="product_uuid", referencedColumnName="uuid")
     */
    protected Product $product;

    /**
     * @return $this
     */
    public function setProduct(Product $product): self
    {
        if (is_a($product, Product::class)) {
            $this->product = $product;
            $this->product_uuid = $product->getUuid();
        }

        return $this;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    protected string $title = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    protected string $address = '';

    public function getAddress(): string
    {
        return $this->address;
    }

    protected float $priceFirst = .00;

    public function getPriceFirst(): float
    {
        return $this->priceFirst;
    }

    protected float $price = .00;

    public function getPrice(): float
    {
        return $this->price;
    }

    protected float $priceWholesale = .00;

    public function getPriceWholesale(): float
    {
        return $this->priceWholesale;
    }

    protected string $vendorCode = '';

    public function getVendorCode(): string
    {
        return $this->vendorCode;
    }

    protected string $external_id = '';

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    /**
     * @ORM\Column(type="float", scale=2, precision=10, options={"default": 1})
     */
    public float $count = 1;

    /**
     * @return $this
     */
    public function setCount(float $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getCount(): float
    {
        return $this->count;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->product->getUuid(),
            'title' => $this->product->getTitle(),
            'address' => $this->product->getAddress(),
            'priceFirst' => $this->product->getPriceFirst(),
            'price' => $this->product->getPrice(),
            'priceWholesale' => $this->product->getPriceWholesale(),
            'vendorCode' => $this->product->getVendorCode(),
            'external_id' => $this->product->getExternalId(),
            'files' => $this->product->getFiles(),
            'count' => $this->getCount(),
        ];
    }

    /**
     * @ORM\PostLoad
     */
    public function _populate_fields(): void
    {
        $this->title = $this->product->getTitle();
        $this->address = $this->product->getAddress();
        $this->priceFirst = $this->product->getPriceFirst();
        $this->price = $this->product->getPrice();
        $this->priceWholesale = $this->product->getPriceWholesale();
        $this->vendorCode = $this->product->getVendorCode();
        $this->external_id = $this->product->getExternalId();
    }

    // magic
    public function __call($name, $arguments)
    {
        if (method_exists($this->product, $name)) {
            return $this->product->{$name}(...$arguments);
        }

        return null;
    }
}

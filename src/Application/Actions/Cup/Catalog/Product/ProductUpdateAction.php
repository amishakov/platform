<?php declare(strict_types=1);

namespace App\Application\Actions\Cup\Catalog\Product;

use App\Application\Actions\Cup\Catalog\CatalogAction;
use App\Domain\Casts\Reference\Type as ReferenceType;
use App\Domain\Service\Catalog\Exception\AddressAlreadyExistsException;
use App\Domain\Service\Catalog\Exception\AttributeNotFoundException;
use App\Domain\Service\Catalog\Exception\MissingCategoryValueException;
use App\Domain\Service\Catalog\Exception\MissingTitleValueException;
use App\Domain\Service\Catalog\Exception\ProductNotFoundException;
use App\Domain\Service\Catalog\Exception\WrongTitleValueException;

class ProductUpdateAction extends CatalogAction
{
    protected function action(): \Slim\Psr7\Response
    {
        if ($this->resolveArg('product') && \Ramsey\Uuid\Uuid::isValid($this->resolveArg('product'))) {
            try {
                $product = $this->catalogProductService->read([
                    'uuid' => $this->resolveArg('product'),
                    'status' => \App\Domain\Casts\Catalog\Status::WORK,
                ]);

                if ($this->isPost()) {
                    try {
                        $product = $this->catalogProductService->update($product, [
                            'category_uuid' => $this->getParam('category'),
                            'title' => $this->getParam('title'),
                            'type' => $this->getParam('type'),
                            'description' => $this->getParam('description'),
                            'extra' => $this->getParam('extra'),
                            'address' => $this->getParam('address'),
                            'vendorcode' => $this->getParam('vendorcode'),
                            'barcode' => $this->getParam('barcode'),
                            'tax' => $this->getParam('tax'),
                            'tax_included' => $this->getParam('tax_included'),
                            'priceFirst' => $this->getParam('priceFirst'),
                            'price' => $this->getParam('price'),
                            'priceWholesale' => $this->getParam('priceWholesale'),
                            'priceWholesaleFrom' => $this->getParam('priceWholesaleFrom'),
                            'discount' => $this->getParam('discount'),
                            'special' => $this->getParam('special'),
                            'quantity' => $this->getParam('quantity'),
                            'quantityMin' => $this->getParam('quantityMin'),
                            'dimension' => $this->getParam('dimension'),
                            'stock' => $this->getParam('stock'),
                            'country' => $this->getParam('country'),
                            'manufacturer' => $this->getParam('manufacturer'),
                            'tags' => $this->getParam('tags'),
                            'order' => $this->getParam('order'),
                            'date' => $this->getParam('date', 'now'),
                            'meta' => $this->getParam('meta'),
                            'external_id' => $this->getParam('external_id'),

                            'attributes' => $this->getParam('attributes', []),
                            'relations' => $this->getParam('relations', []),
                        ]);
                        $product = $this->processEntityFiles($product);

                        $this->container->get(\App\Application\PubSub::class)->publish('cup:catalog:product:edit', $product);

                        switch (true) {
                            case $this->getParam('save', 'exit') === 'exit':
                                return $this->respondWithRedirect('/cup/catalog/product');

                            default:
                                return $this->respondWithRedirect('/cup/catalog/product/' . $product->uuid . '/edit');
                        }
                    } catch (MissingTitleValueException|WrongTitleValueException $e) {
                        $this->addError('title', $e->getMessage());
                    } catch (MissingCategoryValueException $e) {
                        $this->addError('category', $e->getMessage());
                    } catch (AddressAlreadyExistsException $e) {
                        $this->addError('address', $e->getMessage());
                    } catch (AttributeNotFoundException $e) {
                        $this->addError('attribute', $e->getMessage());
                    }
                }

                $categories = $this->catalogCategoryService->read([
                    'status' => \App\Domain\Casts\Catalog\Status::WORK,
                ]);

                return $this->respondWithTemplate('cup/catalog/product/form.twig', [
                    'categories' => $categories,
                    'category' => $product->category,
                    'attributes' => $this->catalogAttributeService->read(),
                    'countries' => $this->referenceService->read(['type' => ReferenceType::COUNTRY, 'status' => true, 'order' => ['order' => 'asc']]),
                    'manufacturers' => $this->referenceService->read(['type' => ReferenceType::MANUFACTURER, 'status' => true, 'order' => ['order' => 'asc']]),
                    'tax_rates' => $this->referenceService->read(['type' => ReferenceType::TAX_RATE, 'status' => true, 'order' => ['order' => 'asc']]),
                    'stock_status' => $this->referenceService->read(['type' => ReferenceType::STOCK_STATUS, 'status' => true, 'order' => ['order' => 'asc']]),
                    'length_class' => $this->referenceService->read(['type' => ReferenceType::LENGTH_CLASS, 'status' => true, 'order' => ['order' => 'asc']]),
                    'weight_class' => $this->referenceService->read(['type' => ReferenceType::WEIGHT_CLASS, 'status' => true, 'order' => ['order' => 'asc']]),
                    'item' => $product,
                ]);
            } catch (ProductNotFoundException $e) {
                // nothing
            }
        }

        return $this->respondWithRedirect('/cup/catalog/product');
    }
}

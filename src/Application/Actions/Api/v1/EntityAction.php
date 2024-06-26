<?php declare(strict_types=1);

namespace App\Application\Actions\Api\v1;

use App\Application\Actions\Api\ActionApi;
use App\Domain\AbstractException;
use App\Domain\AbstractNotFoundException;
use App\Domain\AbstractService;
use App\Domain\Service\Catalog\AttributeService as CatalogAttributeService;
use App\Domain\Service\Catalog\CategoryService as CatalogCategoryService;
use App\Domain\Service\Catalog\OrderService as CatalogOrderService;
use App\Domain\Service\Catalog\ProductService as CatalogProductService;
use App\Domain\Service\File\FileService;
use App\Domain\Service\GuestBook\GuestBookService;
use App\Domain\Service\Page\PageService;
use App\Domain\Service\Parameter\ParameterService;
use App\Domain\Service\Publication\CategoryService as PublicationCategoryService;
use App\Domain\Service\Publication\PublicationService;
use App\Domain\Service\Reference\ReferenceService;
use App\Domain\Service\Task\TaskService;
use App\Domain\Service\User\GroupService as UserGroupService;
use App\Domain\Service\User\UserService;
use Illuminate\Support\Collection;
use Psr\Container\ContainerExceptionInterface;

class EntityAction extends ActionApi
{
    protected function action(): \Slim\Psr7\Response
    {
        $status = 200;
        $result = [];

        try {
            $apikey = (bool) $this->request->getAttribute('apikey', false);
            $entity = ltrim($this->resolveArg('args'), '/');
            $params = $this->getParamsQuery();
            $service = $this->getService($entity);

            // allow access if is CUP level
            if (str_starts_with($this->request->getUri()->getPath(), '/cup/api')) {
                $apikey = true;
            }

            if ($service !== null) {
                switch ($this->request->getMethod()) {
                    case 'GET':
                        try {
                            $result = $service->read($params);
                        } catch (AbstractNotFoundException|\Exception $e) {
                            $status = 404;
                        }

                        break;

                    case 'POST':
                    case 'PUT':
                        if ($apikey) {
                            $result = $service->create($this->getParamsBody());
                            $result = $this->processEntityFiles($result);

                            $status = 201;

                            $this->container->get(\App\Application\PubSub::class)->publish('api:' . str_replace('/', ':', $entity) . ':create', $result);
                            $this->logger->notice('Create entity via API', $params);
                        } else {
                            $status = 405;
                        }

                        break;

                    case 'PATCH':
                        if ($apikey) {
                            try {
                                $result = $service->read($params);

                                if ($result) {
                                    if (!is_array($result) && !is_a($result, Collection::class)) {
                                        $result = [$result];
                                    }

                                    foreach ($result as &$item) {
                                        $item = $service->update($item, $this->getParamsBody());
                                        $item = $this->processEntityFiles($item);
                                    }

                                    $status = 202;

                                    $this->container->get(\App\Application\PubSub::class)->publish('api:' . str_replace('/', ':', $entity) . ':edit', $result);
                                    $this->logger->notice('Update entity via API', $params);
                                } else {
                                    $status = 409;
                                }
                            } catch (AbstractNotFoundException|\Exception $e) {
                                $status = 404;
                            }
                        } else {
                            $status = 405;
                        }

                        break;

                    case 'DELETE':
                        if ($apikey) {
                            try {
                                $result = $service->read($params);

                                if ($result) {
                                    if (!is_array($result) && !is_a($result, Collection::class)) {
                                        $result = [$result];
                                    }

                                    foreach ($result as &$item) {
                                        $item = $service->delete($item);
                                    }

                                    $status = 410;

                                    $this->container->get(\App\Application\PubSub::class)->publish('api:' . str_replace('/', ':', $entity) . ':delete', $result);
                                    $this->logger->notice('Delete entity via API', $params);
                                } else {
                                    $status = 409;
                                }
                            } catch (AbstractNotFoundException|\Exception $e) {
                                $status = 404;
                            }
                        } else {
                            $status = 405;
                        }

                        break;
                }
            } else {
                $status = 404;
            }
        } catch (AbstractException|ContainerExceptionInterface $exception) {
            $status = 503;
            $result = $exception->getTitle();
        }

        return $this
            ->respondWithJson([
                'status' => $status,
                'data' => is_a($result, Collection::class) ? $result->toArray() : $result,
            ])
            ->withStatus($status);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private function getService(mixed $entity): ?AbstractService
    {
        return match ($entity) {
            'catalog/attributes' => $this->container->get(CatalogAttributeService::class),
            'catalog/category' => $this->container->get(CatalogCategoryService::class),
            'catalog/product' => $this->container->get(CatalogProductService::class),
            'catalog/order' => $this->container->get(CatalogOrderService::class),
            'file' => $this->container->get(FileService::class),
            'guestbook' => $this->container->get(GuestBookService::class),
            'page' => $this->container->get(PageService::class),
            'parameter' => $this->container->get(ParameterService::class),
            'publication' => $this->container->get(PublicationService::class),
            'publication/category' => $this->container->get(PublicationCategoryService::class),
            'reference' => $this->container->get(ReferenceService::class),
            'task' => $this->container->get(TaskService::class),
            'user' => $this->container->get(UserService::class),
            'user/group' => $this->container->get(UserGroupService::class),
            default => null,
        };
    }

    private function getParamsQuery(): array
    {
        $default = [
            'status' => 'work',
            'order' => [],
            'limit' => 1000,
            'offset' => 0,
        ];
        $params = $this->request->getQueryParams();

        // fix nullable values
        foreach ($params as &$value) {
            if ($value === 'null') {
                $value = null;
            }
        }

        return array_merge($default, $params);
    }

    private function getParamsBody(): array
    {
        return (array) ($this->request->getParsedBody() ?? []);
    }
}

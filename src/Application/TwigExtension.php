<?php declare(strict_types=1);

namespace App\Application;

use App\Application\Twig\LocaleParser;
use App\Domain\AbstractExtension;
use App\Domain\Casts\Reference\Type as ReferenceType;
use App\Domain\Service\Catalog\AttributeService as CatalogAttributeService;
use App\Domain\Service\Catalog\CategoryService as CatalogCategoryService;
use App\Domain\Service\Catalog\OrderService as CatalogOrderService;
use App\Domain\Service\Catalog\ProductService as CatalogProductService;
use App\Domain\Service\File\FileService;
use App\Domain\Service\GuestBook\GuestBookService;
use App\Domain\Service\Page\PageService;
use App\Domain\Service\Publication\CategoryService as PublicationCategoryService;
use App\Domain\Service\Publication\PublicationService;
use App\Domain\Service\Reference\ReferenceService;
use App\Domain\Service\User\GroupService as UserGroupService;
use App\Domain\Service\User\UserService;
use Illuminate\Cache\ArrayStore as ArrayCache;
use Illuminate\Cache\FileStore as FileCache;
use Illuminate\Support\Collection;
use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension
{
    protected RouteCollectorInterface $routeCollector;

    public function __construct(?ContainerInterface $container = null)
    {
        parent::__construct($container);

        $this->routeCollector = $container->get(RouteCollectorInterface::class);
    }

    public function getName()
    {
        return 'wse';
    }

    public function getTokenParsers()
    {
        return [
            new LocaleParser(),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('preg_replace', [$this, 'preg_replace']),
            new TwigFilter('count', [$this, 'count']),
            new TwigFilter('df', [$this, 'df']),
            new TwigFilter('dfm', [$this, 'dfm']),
            new TwigFilter('locale', '__', ['is_safe' => ['html']]),
            new TwigFilter('trans', [$this, 'trans'], ['is_safe' => ['html']]),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('instanceof', [$this, 'instanceof']),
        ];
    }

    public function instanceof($var, $instance): bool
    {
        return $var instanceof $instance;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('current_url', [$this, 'currentUrl']),
            new TwigFunction('parse_url', [$this, 'parseUrl']),

            // slim functions
            new TwigFunction('path_for', [$this, 'pathFor']),
            new TwigFunction('full_url_for', [$this, 'fullUrlFor']),
            new TwigFunction('base_url', [$this, 'baseUrl']),
            new TwigFunction('is_current_path', [$this, 'isCurrentPath']),
            new TwigFunction('current_path', [$this, 'currentPath']),

            // wse functions
            new TwigFunction('_cup_editor_locale', [$this, '_cup_editor_locale'], ['is_safe' => ['html']]),
            new TwigFunction('_', '__', ['is_safe' => ['html']]),
            new TwigFunction('form', [$this, 'form'], ['is_safe' => ['html']]),
            new TwigFunction('constant', [$this, 'constant']),
            new TwigFunction('pre', [$this, 'pre']),
            new TwigFunction('count', [$this, 'count']),
            new TwigFunction('df', [$this, 'df']),
            new TwigFunction('dfm', [$this, 'dfm']),
            new TwigFunction('collect', [$this, 'collect']),
            new TwigFunction('non_page_path', [$this, 'non_page_path']),
            new TwigFunction('current_page_number', [$this, 'current_page_number']),
            new TwigFunction('current_query', [$this, 'current_query'], ['is_safe' => ['html']]),
            new TwigFunction('is_current_page_number', [$this, 'is_current_page_number']),
            new TwigFunction('build_query', [$this, 'build_query'], ['is_safe' => ['html']]),
            new TwigFunction('base64_encode', [$this, 'base64_encode']),
            new TwigFunction('base64_decode', [$this, 'base64_decode']),
            new TwigFunction('json_encode', [$this, 'json_encode'], ['is_safe' => ['html']]),
            new TwigFunction('json_decode', [$this, 'json_decode'], ['is_safe' => ['html']]),
            new TwigFunction('cache_put', [$this, 'cache_put']),
            new TwigFunction('cache_get', [$this, 'cache_get']),
            new TwigFunction('convert_size', [$this, 'convert_size']),
            new TwigFunction('qr_code', [$this, 'qr_code'], ['is_safe' => ['html']]),

            // files functions
            new TwigFunction('file', [$this, 'file']),

            // page functions
            new TwigFunction('page', [$this, 'page']),

            // publication functions
            new TwigFunction('publication_category', [$this, 'publication_category']),
            new TwigFunction('publication', [$this, 'publication']),

            // parameter functions
            new TwigFunction('parameter', [$this, 'parameter']),

            // reference functions
            new TwigFunction('reference', [$this, 'reference']),

            // guestbook functions
            new TwigFunction('guestbook', [$this, 'guestbook']),

            // catalog functions
            new TwigFunction('catalog_attribute', [$this, 'catalog_attribute']),
            new TwigFunction('catalog_category', [$this, 'catalog_category']),
            new TwigFunction('catalog_product', [$this, 'catalog_product']),
            new TwigFunction('catalog_product_count', [$this, 'catalog_product_count']),
            new TwigFunction('catalog_product_price_type', [$this, 'catalog_product_price_type'], ['needs_context' => true]),
            new TwigFunction('catalog_product_popular', [$this, 'catalog_product_popular']),
            new TwigFunction('catalog_product_view', [$this, 'catalog_product_view']),
            new TwigFunction('catalog_product_dimensional_weight', [$this, 'catalog_product_dimensional_weight'], ['needs_context' => true]),
            new TwigFunction('catalog_order', [$this, 'catalog_order']),
            new TwigFunction('catalog_order_status', [$this, 'catalog_order_status']),

            // user
            new TwigFunction('user', [$this, 'user']),
            new TwigFunction('user_group', [$this, 'user_group']),
        ];
    }

    public function _cup_editor_locale()
    {
        return json_encode(i18n::$editor[i18n::$localeCode] ?? [], JSON_UNESCAPED_UNICODE);
    }

    protected function currentHost()
    {
        // from nginx
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'], $_SERVER['HTTP_X_FORWARDED_HOST'])) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        // from params
        if (($host = $this->parameter('common_homepage', false))) {
            return $host;
        }
        // from request
        if (isset($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'])) {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        }

        return 'http://localhost';
    }

    public function currentUrl(): string
    {
        return $this->currentHost() . $_SERVER['REQUEST_URI'];
    }

    public function parseUrl(): array
    {
        return parse_url($this->currentUrl());
    }

    // slim functions

    public function pathFor($name, $data = [], $queryParams = [])
    {
        return $this->routeCollector->getRouteParser()->urlFor($name, $data, $queryParams);
    }

    public function fullUrlFor(string $name, array $data = [], array $queryParams = []): string
    {
        return $this->currentHost() . $this->pathFor($name, $data, $queryParams);
    }

    // base address without slash in end
    public function baseUrl()
    {
        return rtrim($this->parameter('common_homepage', ''), '/');
    }

    public function isCurrentPath($name, $data = [])
    {
        return $this->routeCollector->pathFor($name, $data) === $this->baseUrl() . '/' . ltrim($this->currentUrl(), '/');
    }

    /**
     * Returns current path on given URI.
     *
     * @param bool $withQueryString
     *
     * @return string
     */
    public function currentPath($withQueryString = false)
    {
        $path = ltrim($this->currentUrl(), '/');

        return $withQueryString || !mb_strrpos($path, '?') ? $path : mb_strstr($path, '?', true);
    }

    // wse functions

    public function trans($obj)
    {
        if (!is_array($obj)) {
            return $obj;
        }

        return $obj[i18n::$localeCode] ?? '';
    }

    public function form($type, $name, $args = [])
    {
        return form($type, $name, $args);
    }

    public function constant($reference, $value = null)
    {
        try {
            $reference = constant(str_replace('/', '\\', $reference));

            switch ($value) {
                case null:
                    if (is_array($reference)) {
                        return array_combine(array_values($reference), array_values($reference));
                    }

                    return $reference;

                default:
                    return $reference[$value];
            }
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * @param mixed ...$args
     */
    public function pre(...$args): void
    {
        foreach ($args as $obj) {
            dump(is_array($obj) || is_object($obj) ? array_serialize($obj) : $obj);
        }
    }

    /**
     * @param mixed $subject
     * @param mixed $pattern
     * @param mixed $replacement
     *
     * @return null|array|string|string[]
     */
    public function preg_replace($subject, $pattern, $replacement)
    {
        return preg_replace($pattern, $replacement, $subject);
    }

    /**
     * @param mixed $obj
     *
     * @return false|int
     */
    public function count($obj)
    {
        return is_countable($obj) ? count($obj) : false;
    }

    /**
     * Date format function
     *
     * @param \DateTime|string $obj
     *
     * @throws \Exception
     */
    public function df(mixed $obj = 'now', ?string $format = null): string
    {
        return datetime($obj)->format($format ?: $this->parameter('common_date_format', 'j-m-Y, H:i'));
    }

    public function dfm(?string $format = null): string
    {
        return convertPhpToJsMomentFormat($format ?: $this->parameter('common_date_format', 'j-m-Y, H:i'));
    }

    public function collect(array|Collection $array = []): Collection
    {
        return collect($array);
    }

    public function non_page_path(): string
    {
        $url = $this->parseUrl();
        $path = explode('/', ltrim($url['path'], '/'));

        if (($key = count($path) - 1) && ($buf = $path[$key]) && ctype_digit($buf)) {
            unset($path[$key]);
        }

        return '/' . implode('/', $path);
    }

    public function current_page_number(): int
    {
        $url = $this->parseUrl();
        $path = explode('/', ltrim($url['path'], '/'));
        $page = 0;

        if (($key = count($path) - 1) && ($buf = $path[$key]) && ctype_digit($buf)) {
            $page = +$path[$key];
        }

        return $page;
    }

    public function current_query(?string $key = null, mixed $value = null): string
    {
        $url = $this->parseUrl();
        $query = [];

        foreach (explode('&', rawurldecode($url['query'] ?? '')) as $fragment) {
            if ($fragment) {
                $buf = explode('=', $fragment);
                $query[$buf[0]] = $buf[1] ?? '';
            }
        }
        if ($key) {
            $query[$key] = $value;
        }

        return $query ? '?' . rawurldecode(http_build_query($query)) : '';
    }

    public function is_current_page_number($number): bool
    {
        return $this->current_page_number() === $number;
    }

    public function build_query($url = '', array $params = []): string
    {
        if (is_array($url)) {
            $params = $url;
            $url = '';
        }

        return $url . '?' . urldecode(http_build_query($params));
    }

    public function base64_encode(string $string): string
    {
        return base64_encode($string);
    }

    public function base64_decode(string $string, bool $strict = false): false|string
    {
        return base64_decode($string, $strict);
    }

    public function json_decode(string $json, ?bool $associative = true, int $depth = 512, int $flags = 0): mixed
    {
        return json_decode($json, $associative, $depth, $flags);
    }

    function cache_put(string $key, mixed $value, int $seconds, string $type = 'memory'): void
    {
        /** @var FileCache|ArrayCache $cache */
        $cache = match ($type) {
            'file' => $this->container->get(FileCache::class),
            'memory' => $this->container->get(ArrayCache::class),
        };

        $cache->put('twig-' . $key, $value, $seconds);
    }

    function cache_get(string $key, mixed $default = null, string $type = 'memory'): mixed
    {
        /** @var FileCache|ArrayCache $cache */
        $cache = match ($type) {
            'file' => $this->container->get(FileCache::class),
            'memory' => $this->container->get(ArrayCache::class),
        };

        return $cache->get('twig-' . $key) ?? $default;
    }

    public function json_encode($value, int $flags = JSON_UNESCAPED_UNICODE, int $depth = 512): false|string
    {
        return json_encode($value, $flags, $depth);
    }

    public function convert_size(int $size): string
    {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[(int)$i];
    }

    public function qr_code(mixed $value, $size = 256, $margin = 0): string
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, $margin),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );

        $writer = new \BaconQrCode\Writer($renderer);

        return '<img src="data:image/svg+xml;base64,' . base64_encode($writer->writeString($value)) . '" height="' . $size . '" width="' . $size . '">';
    }

    // files functions

    // fetch files by args
    public function file(array $criteria = [], $order = ['date' => 'desc'], $limit = 10, $offset = null)
    {
        $fileService = $this->container->get(FileService::class);

        return $fileService->read(array_merge($criteria, [
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    // pages functions

    // fetch page by args
    public function page(array $criteria = [], $order = ['date' => 'desc'], $limit = 10, $offset = null)
    {
        $pageService = $this->container->get(PageService::class);

        return $pageService->read(array_merge($criteria, [
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    // publication functions

    // fetch publication category
    public function publication_category(bool $public = true)
    {
        $publicationCategoryService = $this->container->get(PublicationCategoryService::class);

        return $publicationCategoryService->read([
            'is_public' => $public ?: null,
        ]);
    }

    // fetch publications by criteria
    public function publication(array $criteria = [], $order = [], $limit = 10, $offset = null)
    {
        $publicationService = $this->container->get(PublicationService::class);

        return $publicationService->read(array_merge($criteria, [
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    // reference functions

    // fetch reference by type
    public function reference(?string $type = null, bool $pluck = false)
    {
        if (in_array($type, ReferenceType::LIST, true)) {
            $referenceService = $this->container->get(ReferenceService::class);

            $output = $referenceService->read([
                'type' => $type,
                'status' => true,
                'order' => ['order' => 'asc'],
            ]);

            if ($pluck) {
                $output = $output->pluck('value', 'title');
            }

            return $output;
        }

        return ReferenceType::LIST;
    }

    // parameter functions

    // return parameter value by name or default
    public function parameter(mixed $name = null, mixed $default = null): mixed
    {
        return parent::parameter($name, $default);
    }

    // guestbook functions

    // fetch guest book rows
    public function guestbook($order = ['date' => 'desc'], $limit = 10, $offset = null)
    {
        $guestBookService = $this->container->get(GuestBookService::class);

        return $guestBookService
            ->read([
                'status' => \App\Domain\Casts\GuestBook\Status::WORK,
                'order' => $order,
                'limit' => $limit,
                'offset' => $offset,
            ])
            ->map(function (\App\Domain\Models\GuestBook $model) {
                $model->email = str_mask_email($model->email);

                return $model;
            });
    }

    // catalog functions

    // fetch attributes list
    public function catalog_attribute(array $criteria = [], $order = [])
    {
        $catalogAttributeService = $this->container->get(CatalogAttributeService::class);

        return $catalogAttributeService->read(array_merge($criteria, ['order' => $order]));
    }

    // fetch categories list
    public function catalog_category(array $criteria = [], $order = ['order' => 'asc'])
    {
        $catalogCategoryService = $this->container->get(CatalogCategoryService::class);

        return $catalogCategoryService->read(array_merge(
            [
                'is_hidden' => false,
            ],
            $criteria,
            [
                'status' => \App\Domain\Casts\Catalog\Status::WORK,
                'order' => $order,
            ]
        ));
    }

    // returns a product or a list of products by criteria
    public function catalog_product(array $criteria = [], $order = ['order' => 'asc'], $limit = 50, $offset = null)
    {
        $criteria['status'] = \App\Domain\Casts\Catalog\Status::WORK;
        $catalogProductService = $this->container->get(CatalogProductService::class);

        return $catalogProductService->read(array_merge($criteria, ['order' => $order, 'limit' => $limit, 'offset' => $offset]));
    }

    // returns a product count by criteria
    public function catalog_product_count(array $criteria = [])
    {
        $criteria['status'] = \App\Domain\Casts\Catalog\Status::WORK;
        $catalogProductService = $this->container->get(CatalogProductService::class);

        return $catalogProductService->count($criteria);
    }

    // returns product price type
    public function catalog_product_price_type($context)
    {
        if (!empty($context['user'])) {
            return $this->parameter('catalog_price_type', \App\Domain\References\Catalog::PRODUCT_PRICE_TYPE_PRICE);
        }

        return \App\Domain\References\Catalog::PRODUCT_PRICE_TYPE_PRICE;
    }

    public function catalog_product_popular(int $limit = 10, string $type = \App\Domain\Casts\Catalog\Product\Type::PRODUCT): Collection
    {
        return \App\Domain\Models\CatalogProduct::query()
            ->select('cp.*')
            ->addSelect($this->db->raw('COUNT(cop.product_uuid) as order_count'))
            ->from('catalog_product as cp')
            ->join('catalog_order_product as cop', 'cp.uuid', '=', 'cop.product_uuid')
            ->where('cp.type', '=', $type)
            ->where('cp.status', '=', \App\Domain\Casts\Catalog\Status::WORK)
            ->groupBy('cp.uuid', ...array_map(fn($col) => "cp.{$col}", array_keys((new \App\Domain\Models\CatalogProduct())->getAttributes())))
            ->orderByDesc('order_count')
            ->limit($limit)
            ->get();
    }

    // save uuid of product in session or return saved list
    public function catalog_product_view(string $uuid = null, int $limit = 10)
    {
        $list = $_SESSION['catalog_product_view'] ?? [];

        if (is_string($uuid) && \Ramsey\Uuid\Uuid::isValid($uuid)) {
            $list[] = $uuid;
            $list = array_unique($list);

            // shift first element
            if (count($list) > $limit) {
                $list = array_slice($list, 0 - $limit, $limit);
            }

            $_SESSION['catalog_product_view'] = $list;
        }

        return $list;
    }

    // calculate dimension weight
    public function catalog_product_dimensional_weight($context, $product = null): float
    {
        if ($product === null && !empty($context['product'])) {
            $product = $context['product'];
        }

        if ($product) {
            if (!blank($product->dimension['length']) && !blank($product->dimension['width']) && !blank($product->dimension['height'])) {
                $ratio = $this->parameter('catalog_dimensional_weight', 5000);
                $length_class = $this->reference(ReferenceType::LENGTH_CLASS)->firstWhere('value.unit', $product->dimension['length_class']);
                $length_value = $length_class ? $length_class->value()['value'] : 1;

                return round(
                    ($product->dimension['length'] * $length_value) *
                    ($product->dimension['width'] * $length_value) *
                    ($product->dimension['height'] * $length_value) / $ratio,
                    4
                );
            }
        }

        return 0;
    }

    // fetch order
    public function catalog_order(array $criteria = [], $order = ['date' => 'desc'], $limit = 10, $offset = null)
    {
        /** @var CatalogOrderService $catalogOrderService */
        $catalogOrderService = $this->container->get(CatalogOrderService::class);

        return $catalogOrderService->read(array_merge(
            $criteria,
            [
                'order' => $order,
                'limit' => $limit,
                'offset' => $offset,
            ]
        ));
    }

    // user functions

    // fetch user list
    public function user(array $criteria = [], $order = ['email' => 'asc'])
    {
        /** @var UserService $userService */
        $userService = $this->container->get(UserService::class);

        return $userService->read(array_merge(
            $criteria,
            [
                'status' => \App\Domain\Casts\User\Status::WORK,
                'order' => $order,
            ]
        ));
    }

    // fetch user group list
    public function user_group(array $criteria = [], $order = ['title' => 'asc'])
    {
        /** @var UserGroupService $userGroupService */
        $userGroupService = $this->container->get(UserGroupService::class);

        return $userGroupService->read(array_merge($criteria, ['order' => $order]));
    }
}

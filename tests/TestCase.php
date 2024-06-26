<?php declare(strict_types=1);

namespace tests;

use Phinx\Console\PhinxApplication;
use Psr\Container\ContainerInterface;
use Slim\App;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private static App $app;

    private static ContainerInterface $container;

    /**
     * @return App
     */
    public static function setUpBeforeClass(): void
    {
        $_ENV['TEST'] = 1; // in test always true (!)

        require SRC_DIR . '/bootstrap.php';

        /**
         * from bootstrap
         *
         * @var \Slim\App $app
         */
        static::$app = $app;
        static::$container = static::$app->getContainer();
    }

    public function setUp(): void
    {
        /*
         * for each test, we will use an empty database
         * delete the scheme and create it again
         */

        $phinxApp = new PhinxApplication();
        $phinxApp->setAutoExit(false);

        $output = new NullOutput();

        $phinxApp->run(new StringInput('rollback -t 0 -f'), $output);
        $phinxApp->run(new StringInput('migrate'), $output);
    }

    protected function getService($class): mixed
    {
        return static::$container->get($class);
    }

    /**
     * @return \Faker\Generator
     */
    protected function getFaker()
    {
        static $faker;

        if (!$faker) {
            $faker = \Faker\Factory::create();
        }

        return $faker;
    }

    protected function createRequest(): \GuzzleHttp\Client
    {
        static $client;

        if (!$client) {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'http://127.0.0.1:80',
                'http_errors' => false,
                // 'debug' => true,
            ]);
        }

        return $client;
    }
}

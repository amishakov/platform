<?php declare(strict_types=1);

namespace App\Domain\Plugin;

use App\Domain\AbstractPlugin;

abstract class AbstractMailPlugin extends AbstractPlugin
{
    abstract public function send(array $data);
}

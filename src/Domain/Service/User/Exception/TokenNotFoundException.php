<?php declare(strict_types=1);

namespace App\Domain\Service\User\Exception;

use App\Domain\AbstractException;

class TokenNotFoundException extends AbstractException
{
    protected $message = 'EXCEPTION_USER_TOKEN_NOT_FOUND';
}
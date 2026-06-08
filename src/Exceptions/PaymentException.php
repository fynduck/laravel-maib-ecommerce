<?php

namespace Fynduck\MaibEcommerce\Exceptions;

class PaymentException extends MaibException
{
    protected ?string $errorCode = null;

    public static function fromApiError(string $message, ?string $errorCode = null): self
    {
        $exception = new self($message);
        $exception->errorCode = $errorCode;

        return $exception;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}

<?php
declare(strict_types=1);

namespace InitPayCK\Exceptions;

class InitPayException extends \RuntimeException
{
    /** @var array<string,mixed>|null */
    protected ?array $context = null;

    /** @param array<string,mixed>|null $context */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null, ?array $context = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /** @return array<string,mixed>|null */
    public function context(): ?array
    {
        return $this->context;
    }
}

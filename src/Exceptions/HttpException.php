<?php
declare(strict_types=1);

namespace InitPayCK\Exceptions;

class HttpException extends InitPayException
{
    private int $statusCode;
    private ?string $rawBody;

    /** @param array<string,mixed>|null $context */
    public function __construct(string $message, int $statusCode, ?string $rawBody = null, ?array $context = null)
    {
        parent::__construct($message, $statusCode, null, $context);
        $this->statusCode = $statusCode;
        $this->rawBody = $rawBody;
    }

    public function statusCode(): int { return $this->statusCode; }
    public function rawBody(): ?string { return $this->rawBody; }
}

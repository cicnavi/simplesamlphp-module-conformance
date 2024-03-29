<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use Symfony\Component\HttpFoundation\Request;

/**
 * Generic status value object.
 */
class GenericStatus
{
    final public const KEY_STATUS = 'status';
    final public const KEY_MESSAGE = 'message';
    final public const STATUS_ERROR = 'error';
    final public const STATUS_OK = 'ok';

    public function __construct(
        protected ?string $status = null,
        protected ?string $message = null,
    ) {
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function setMessage(?string $message): GenericStatus
    {
        $this->message = $message;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function isError(): ?bool
    {
        return $this->status ? $this->status === self::STATUS_ERROR : null;
    }

    public function isOk(): ?bool
    {
        return $this->status ? $this->status === self::STATUS_OK : null;
    }

    public function setStatusError(): self
    {
        $this->status = self::STATUS_ERROR;
        return $this;
    }

    public function setStatusOk(): self
    {
        $this->status = self::STATUS_OK;
        return $this;
    }

    public function toArray(): array
    {
        return [
            self::KEY_STATUS => $this->status,
            self::KEY_MESSAGE => $this->message,
        ];
    }
}

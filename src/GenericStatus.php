<?php

namespace SimpleSAML\Module\conformance;

use Symfony\Component\HttpFoundation\Request;

/**
 * Generic status value object.
 */
class GenericStatus
{
    public const KEY_STATUS = 'status';
    public const KEY_MESSAGE = 'message';
    public const STATUS_ERROR = 'error';
    public const STATUS_OK = 'ok';

    public function __construct(
        protected ?string $status = null,
        protected ?string $message = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->server->get(self::KEY_STATUS) ?? $request->query->get(self::KEY_STATUS),
            $request->server->get(self::KEY_MESSAGE) ?? $request->query->get(self::KEY_MESSAGE)
        );
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
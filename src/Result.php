<?php

declare(strict_types=1);

namespace ReliqArts\Docweaver;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use stdClass;

class Result implements Arrayable, Jsonable
{
    /**
     * @var bool
     */
    private bool $success;

    /**
     * @var string
     */
    private string $error;

    /**
     * @var string[]
     */
    private array $messages;

    /**
     * @var null|stdClass
     */
    private $extra;

    /**
     * Result constructor.
     *
     * @param string[] $messages
     * @param mixed    $extra
     */
    public function __construct(
        bool $success = true,
        string $error = '',
        array $messages = [],
        $extra = null
    ) {
        $this->success = $success;
        $this->error = $error;
        $this->messages = $messages;
        $this->extra = $extra;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $clone = clone $this;
        $clone->success = $success;

        return $clone;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function setError(string $error): self
    {
        $clone = clone $this;
        $clone->error = $error;
        $clone->success = false;

        return $clone;
    }

    public function getExtra(): ?stdClass
    {
        return $this->extra;
    }

    public function setExtra(stdClass $extra): self
    {
        $clone = clone $this;
        $clone->extra = $extra;

        return $clone;
    }

    public function getMessage(): string
    {
        return empty($this->messages) ? '' : current($this->messages);
    }

    public function setMessage(string $message): self
    {
        return $this->addMessage($message);
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param string ...$messages
     */
    public function setMessages(string ...$messages): self
    {
        $clone = clone $this;
        $clone->messages = $messages;

        return $clone;
    }

    public function addMessage(string $message): self
    {
        $clone = clone $this;
        $clone->messages[] = $message;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return (array)$this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $options
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}

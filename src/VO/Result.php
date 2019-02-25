<?php

declare(strict_types=1);

namespace ReliQArts\Docweaver\VO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use stdClass;

class Result implements Arrayable, Jsonable
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var string
     */
    private $error;

    /**
     * @var string[]
     */
    private $messages;

    /**
     * @var null|stdClass
     */
    private $data;

    /**
     * Result constructor.
     *
     * @param bool     $success
     * @param string   $error
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
        $this->data = $extra;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     *
     * @return self
     */
    public function setSuccess(bool $success): self
    {
        $clone = clone $this;
        $clone->success = $success;

        return $clone;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     *
     * @return self
     */
    public function setError(string $error): self
    {
        $clone = clone $this;
        $clone->error = $error;
        $clone->success = false;

        return $clone;
    }

    /**
     * @return null|stdClass
     */
    public function getData(): ?stdClass
    {
        return $this->data;
    }

    /**
     * @param stdClass $data
     *
     * @return self
     */
    public function setData(stdClass $data): self
    {
        $clone = clone $this;
        $clone->data = $data;

        return $clone;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return empty($this->messages) ? '' : current($this->messages);
    }

    /**
     * @param string $message
     *
     * @return self
     */
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
     *
     * @return self
     */
    public function setMessages(string ...$messages): self
    {
        $clone = clone $this;
        $clone->messages = $messages;

        return $clone;
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public function addMessage(string $message): self
    {
        $clone = clone $this;
        $clone->messages[] = $message;

        return $clone;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toArray()
    {
        return (array)$this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}

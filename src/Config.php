<?php


namespace Swoft\Csp;

class Config
{
    private $mode      = Mode::CSP_MODE_AWAIT; //默认等待执行
    private $key       = 'default';
    private $arguments = [];

    public function __construct()
    {
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setMode(string $mode)
    {
        $this->mode = $mode;
    }

    public function setKey(string $key)
    {
        $this->key = $key;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }
}

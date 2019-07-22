<?php


namespace Swoft\Csp;

class Config implements \JsonSerializable
{
    private $mode      = Mode::CSP_MODE_AWAIT; //默认等待执行
    private $key       = 'default';
    private $arguments = [];
    private $objName   = '_cspConfig_3c2f5decde47940c8baf3b80dea449bd';

    public function __construct()
    {
    }

    public function jsonSerialize()
    {
        $data = [];
        foreach ($this as $key => $val) {
            if ($val !== null) $data[$key] = $val;
        }
        return $data;
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

    /**
     * 判断是否为配置对象的数据
     * @param array $data
     * @return bool
     */
    public function isConfigData(array $data)
    {
        foreach ($data as $key => $val) {
            if (!property_exists($this, $key)) {
                return false;
            }
        }
        return ($this->objName === ($data['objName'] ?? null));
    }

    /**
     * 批量赋值
     * @param array $data
     * @return bool
     */
    public function setAll(array $data)
    {
        foreach ($data as $key => $val) {
            if ($key !== 'objName' && property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        return true;
    }
}

<?php

namespace Swoft\Csp;

use Swoft\Csp\Exception\CspException;
use Swoole\Coroutine;

/**
 * Class AbstractCsp
 *
 * @since 2.0
 */
class AbstractCsp
{
    private $cspKey    = '';
    private $cspObjMap = [];

    /**
     * @param $name
     * @param $arguments
     *
     * @return bool|mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (substr($name, -3) !== "Csp") {
            return false;
        }
        $class      = new \ReflectionClass($this);
        $methodName = substr($name, 0, -3);
        $parameters = $class->getMethod($methodName)->getParameters();
        $config     = $this->argToConfig($arguments);
        if (count($arguments) > count($parameters)) {
            throw new CspException('参数错误');
        }
        return $this->methodCall($methodName, $config);
    }

    /**
     * 方法调用
     * @param $method 方法名
     * @param $config 配置数据
     * @param int $retry 调用失败后重试次数
     *
     * @return mixed
     */
    private function methodCall($methodName, $config, $retry = 3)
    {
        $cspObj = $this->getCspObj($config->getKey());
        $res    = call_user_func([$cspObj, $methodName], $config);
        if ($res === false && $retry > 0) {
            return $this->methodCall($methodName, $config, --$retry);
        }
        return $res;
    }

    /**
     * 根据参数生成配置
     * @param array $arguments
     *
     * @return mixed|Config|null
     */
    private function argToConfig(array &$arguments)
    {
        $config = new Config();
        if (is_array($arguments[0]) && $config->isConfigData($arguments[0])) {
            $config->setAll($arguments[0]);
            array_splice($arguments, 0, 1);
        }
        $config->setArguments($arguments);
        return $config;
    }

    /**
     * 获取一个csp对象
     * @param string $channKey
     *
     * @return $this
     */
    private function getCspObj(string $key = "default")
    {
        $key = $this->keyEncode($key);
        if (!isset($this->cspObjMap[$key])) {
            $this->cspObjGC($key, 5);
            $this->cspObjMap[$key] = new Basic($this);
        }
        if ($this->cspObjMap[$key]->isClose()) {
            $this->clearCspObj($key);
            return $this->getCspObj($key);
        }
        return $this->cspObjMap[$key];
    }

    /**
     * 对key进行编码
     * @param string $str
     *
     * @return string
     */
    private function keyEncode(string $key): string
    {
        return base_convert(md5($key), 4, 10);
    }

    /**
     * csp对象回收
     * @param $key
     *
     * @return bool
     */
    private function cspObjGC(string $key, int $timeout = 60): bool
    {
        $fun = function_exists('sgo') ? 'sgo' : 'go';
        $fun(function () use ($key, $timeout) {
            while (1) {
                Coroutine::sleep($timeout);
                if (!isset($this->cspObjMap[$key]) || !$this->cspObjMap[$key] || $this->cspObjMap[$key]->getAwaitCounter() <= 0) {
                    $this->clearCspObj($key);
                    break;
                }
            }
        });
        return true;
    }

    /**
     * 清除csp对象
     *
     * @return bool
     */
    private function clearCspObj($key)
    {
        @$this->cspObjMap[$key]->close();
        $this->cspObjMap[$key] = null;
        unset($this->cspObjMap[$key]);
        return true;
    }
}

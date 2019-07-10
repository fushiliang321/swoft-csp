<?php

namespace Swoft\Csp;

use Swoole\Coroutine;

/**
 * Class AbstractCsp
 *
 * @since 2.0
 */
class AbstractCsp
{
    protected $cspKey = '';
    private $cspMap = [];

    public const CSP_MODE_SYN = 'syn';
    public const CSP_MODE_ASY = 'asy';

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
        $key = 'default_chann';
        $data = [
            'mode'      => 'syn',
            'arguments' => $arguments,
        ];
        if (isset($arguments[0]['cspKey']) || isset($arguments[0]['cspBack']) || isset($arguments[0]['cspMode'])) {
            $key = $arguments[0]['cspKey'] ?? $key;
            $data['mode'] = $arguments[0]['cspMode'] ?? $data['mode'];
            unset($data['arguments'][0]);
        }
        $cspObj = $this->csp((string)$key);
        $methodName = substr($name, 0, -3);
        return call_user_func([$cspObj, $methodName], $data);
    }

    /**
     * @param string $channKey
     *
     * @return $this
     */
    public function csp(string $key = "default_chann")
    {
        $key = $this->keyEncode($key);
        if (!isset($this->cspMap[$key])) {
            $this->cspMap[$key] = new CspBasic($this);
            $this->cspObjGC($key);
        }
        return $this->cspMap[$key];
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
        sgo(function () use ($key, $timeout) {
            while (1) {
                Coroutine::sleep($timeout);
                if (!$this->cspMap[$key]->counter) {
                    $this->cspMap[$key]->close();
                    $this->cspMap[$key] = null;
                }
                if (!$this->cspMap[$key]) {
                    unset($this->cspMap[$key]);
                    break;
                }
            }
        });
        return true;
    }
}

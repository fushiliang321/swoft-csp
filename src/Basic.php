<?php

namespace Swoft\Csp;

use Swoole\Coroutine\Channel;

class Basic
{
    private $chan;
    private $awaitCounter = 0;
    private $status       = 0;

    public function __construct($class)
    {
        $this->chan   = new Channel();
        $this->status = 1;
        $this->creationConsumer($class);
    }

    public function __call(string $name, array $data)
    {
        if (!$this->status || $this->chan->errCode < 0) {
            $this->close();
            return false;
        }
        $config = $data[0] ?? [];
        if (!($config instanceof Config)) {
            return false;
        }
        $pushData = [
            'method'    => $name,
            'arguments' => $config->getArguments(),
            'chan'      => null,
        ];
        if ($config->getMode() === Mode::CSP_MODE_AWAIT) {
            $pushData['chan'] = new Channel();
        }
        if (!$this->chan->push($pushData)) {
            $this->close();
            return false;
        }
        $res = ['result' => true];
        if ($pushData['chan']) {
            ++$this->awaitCounter;
            $res = $pushData['chan']->pop();
            --$this->awaitCounter;
        }
        return $res;
    }

    /**
     * 创建一个消费者协程
     *
     * @param $class
     */
    private function creationConsumer($class)
    {
        $fun = function_exists('sgo') ? 'sgo' : 'go';
        $fun(function () use ($class) {
            while (true) {
                if (!$this->status || $this->chan->errCode < 0) {
                    $this->close();
                    break;
                }
                $data = $this->chan->pop();
                if ($data === false) {
                    $this->close();
                    break;
                }
                $res = false;
                if (is_callable([$class, $data['method']])) {
                    @$res = call_user_func_array([$class, $data['method']], $data['arguments']);
                }
                if ($data['chan']) {
                    $data['chan']->push(['result' => $res]);
                }
            }
        });
    }

    public function getAwaitCounter(): int
    {
        return $this->awaitCounter;
    }

    /**
     * 关闭
     *
     * @return bool
     */
    public function close(): bool
    {
        print_r('close');
        $this->awaitCounter = 0;
        $this->status       = 0;
        @$this->chan->close();
        $this->chan = null;
        return true;
    }

    /**
     * 判断是否关闭
     *
     * @return bool
     */
    public function isClose()
    {
        return !$this->status;
    }
}

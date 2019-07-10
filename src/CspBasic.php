<?php

namespace Swoft\Csp;

use Swoole\Coroutine\Channel;

class CspBasic
{
    private $chan;
    private $counter = 0;

    public function __construct($class)
    {
        $this->chan = new Channel();
        sgo(function () use ($class) {
            while (1) {
                if ($this->chan->errCode < 0) {
                    $this->counter = 0;
                    break;
                }
                $data = $this->chan->pop();
                if ($data === false) {
                    break;
                }
                if (!is_array($data)) {
                    continue;
                }
                if (is_callable([$class, $data['method']])) {
                    $res = call_user_func_array([$class, $data['method']], $data['arguments']);
                } else {
                    $res = false;
                }
                $data['chan']->push(['result' => $res]);
            }
        });
    }

    public function __call(string $name, array $data)
    {
        if ($this->chan->errCode < 0) {
            $this->counter = 0;
            return false;
        }
        $chan = new Channel();
        $pushData = [
            'method'    => $name,
            'arguments' => $data[0]['arguments'],
            'chan'      => $chan,
        ];
        if (!$this->chan->push($pushData)) {
            return false;
        }
        ++$this->counter;
        $res = $chan->pop();
        if (!is_array($res)) {
            return false;
        }
        --$this->counter;
        return $res;
    }

    public function close(): bool
    {
        $this->chan->close();
        $this->counter = 0;
        return true;
    }
}

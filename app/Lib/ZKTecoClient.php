<?php

namespace App\Lib;

use Rats\Zkteco\Lib\ZKTeco;

class ZKTecoClient extends ZKTeco
{
    public function __construct(
        string $ip,
        int    $port       = 4370,
        int    $timeoutSec = 10,
        int    $timeoutUsec = 500000
    ) {
        parent::__construct($ip, $port);

        socket_set_option(
            $this->_zkclient,
            SOL_SOCKET,
            SO_RCVTIMEO,
            ['sec' => $timeoutSec, 'usec' => $timeoutUsec]
        );

        socket_set_option(
            $this->_zkclient,
            SOL_SOCKET,
            SO_SNDTIMEO,
            ['sec' => $timeoutSec, 'usec' => $timeoutUsec]
        );
    }
}
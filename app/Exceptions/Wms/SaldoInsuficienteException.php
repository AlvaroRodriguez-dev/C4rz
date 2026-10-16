<?php

namespace App\\Exceptions\\Wms;

use RuntimeException;

class SaldoInsuficienteException extends RuntimeException
{
    public function __construct(public readonly int $disponible)
    {
        parent::__construct("no tiene saldo suficiente. Disponible: {$disponible}");
    }
}

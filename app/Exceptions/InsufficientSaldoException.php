<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientSaldoException extends RuntimeException
{
    public function __construct(public readonly float $saldoTersedia)
    {
        parent::__construct('Saldo siswa tidak mencukupi untuk operasi ini.');
    }
}

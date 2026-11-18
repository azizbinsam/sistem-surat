<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Kesalahan "bisnis" di alur langganan (mis. triwulan sudah dibeli, invoice sudah lunas).
 * Pesannya aman & sengaja ditulis untuk ditampilkan langsung ke pengguna.
 */
class LanggananException extends RuntimeException
{
}

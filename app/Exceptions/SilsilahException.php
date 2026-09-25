<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan silsilah/adat. Pesannya aman ditampilkan ke pengguna.
 */
class SilsilahException extends RuntimeException
{
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Data input tidak lolos validasi. Daftar pesan per field ada di errors().
 */
class ValidasiDataException extends SilsilahException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}

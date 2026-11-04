<?php
// app/Exceptions/FiturNonaktifException.php
namespace App\Exceptions;

use Exception;

class FiturNonaktifException extends Exception
{
  public function __construct(
    public string $kodeFitur,
    string $message = 'Fitur ini tidak tersedia untuk sekolah Anda.'
  ) {
    parent::__construct($message);
  }
}

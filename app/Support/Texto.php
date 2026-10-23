<?php

namespace App\Support;

final class Texto
{
    public static function likeSeguro(string $termino): string
    {
        return '%' . addcslashes($termino, '%_\\') . '%';
    }
    public static function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
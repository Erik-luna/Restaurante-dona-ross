<?php

namespace App\Support;

/**
 * Utilidades de texto usadas en varios puntos del sistema.
 */
final class Texto
{
    /**
     * Devuelve el patrón para una búsqueda que contenga el texto escrito.
     *
     * El "%" de los extremos hace que se busque en cualquier parte del nombre. Los
     * caracteres % y _ que el usuario escriba se escapan, porque dentro de un LIKE
     * esos dos son comodines: sin escaparlos, buscar "%" devolvería todo el catálogo.
     */
    public static function likeSeguro(string $termino): string
    {
        return '%' . addcslashes($termino, '%_\\') . '%';
    }

    /**
     * Pasa el correo a minúsculas y quita los espacios sobrantes.
     *
     * MySQL no distingue mayúsculas de minúsculas en el correo (por eso
     * Ana@correo.com y ana@correo.com son la misma cuenta). Normalizarlo aquí
     * garantiza que la búsqueda y el bloqueo por intentos-usados usen siempre
     * la misma clave.
     */
    public static function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
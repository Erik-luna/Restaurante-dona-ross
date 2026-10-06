<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Renombra categorias.activo a categorias.estado y cambia su tipo.
 *
 * 'activo' como booleano (1/0) obliga a recordar qué número significaba qué.
 * Con texto ('activo' / 'inactivo') la propia columna se explica sola al leer la
 * base de datos, y deja la puerta abierta a añadir más estados después.
 *
 * El orden de los pasos importa: primero se cambia el tipo, después se traducen
 * los valores y por último se renombra la columna. Si se renombrara antes,
 * MySQL truncaría 'activo' a 1 carácter al convertir de tinyint a varchar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('activo')->default('activo')->change();
        });

        DB::table('categorias')->where('activo', '1')->update(['activo' => 'activo']);
        DB::table('categorias')->where('activo', '0')->update(['activo' => 'inactivo']);

        Schema::table('categorias', function (Blueprint $table) {
            $table->renameColumn('activo', 'estado');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->renameColumn('estado', 'activo');
        });

        // Primero se traducen los textos a '1' y '0' DESPUÉS de renombrar pero
        // ANTES de cambiar el tipo. Si se cambiara el tipo mientras la columna
        // todavía dice 'activo', MySQL leería esa palabra como un número no
        // recognizable y la guardaría como 0: todas las categorías quedarían
        // inactivas al revertir.
        DB::table('categorias')->where('activo', 'activo')->update(['activo' => '1']);
        DB::table('categorias')->where('activo', 'inactivo')->update(['activo' => '0']);

        Schema::table('categorias', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        DB::table('categorias')->where('activo', 'activo')->update(['activo' => '1']);
        DB::table('categorias')->where('activo', 'inactivo')->update(['activo' => '0']);

        Schema::table('categorias', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->change();
        });
    }
};
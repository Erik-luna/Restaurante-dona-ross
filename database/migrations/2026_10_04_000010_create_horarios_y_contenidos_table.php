<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    private const HORARIOS = [
        [1, '08:00:00', '20:00:00'],
        [2, '08:00:00', '20:00:00'],
        [3, '08:00:00', '20:00:00'],
        [4, '08:00:00', '20:00:00'],
        [5, '08:00:00', '20:00:00'],
        [6, '09:00:00', '21:00:00'],
        [7, '09:00:00', '18:00:00'],
    ];
    private const CONTACTOS = [
        'telefono' => '+591 70000000',
        'whatsapp' => '59170000000',
        'direccion' => 'Av. Principal #123, Zona Central',
        'facebook' => '',
        'instagram' => '',
        'correo' => '',
    ];

    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dia')->unique();
            $table->time('apertura');
            $table->time('cierre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('contenidos', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->text('valor')->nullable();
            $table->timestamps();
        });
        $ahora = now();
        DB::table('horarios')->insert(array_map(fn (array $fila) => [
            'dia' => $fila[0],
            'apertura' => $fila[1],
            'cierre' => $fila[2],
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], self::HORARIOS));

        DB::table('contenidos')->insert(array_map(fn (string $valor, string $clave) => [
            'clave' => $clave,
            'valor' => $valor,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], self::CONTACTOS, array_keys(self::CONTACTOS)));
    }

    public function down(): void
    {
        Schema::dropIfExists('contenidos');
        Schema::dropIfExists('horarios');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CMS mínimo: lo que el administrador cambia sin tocar código.
 *
 * Antes de esta migración el teléfono, la dirección y los horarios estaban
 * escritos dentro del HTML de la portada y del pie de página. Para cambiar el
 * número de WhatsApp había que editar una plantilla Blade y desplegar: eso no es
 * administrar un restaurante, es reprogramarlo.
 *
 * Son dos tablas y no una porque los datos son de dos formas distintas:
 *
 *  - `horarios` tiene estructura. Son siete filas (una por día) con dos horas y un
 *    estado, y el sistema las necesita para responder preguntas que no son de
 *    lectura: si está abierto ahora, a qué hora abre mañana. Guardar eso como
 *    texto suelto ("Lunes a Viernes: 08:00 - 20:00") no permitiría calcular nada.
 *
 *  - `contenidos` es texto con clave: teléfono, dirección, redes. No tiene
 *    estructura porque no hay que calcular nada con él, solo mostrarlo en un sitio
 *    y que el administrador lo cambie.
 *
 * La migración siembra los valores que ya estaban en las plantillas. Es a propósito:
 * el sitio tiene que seguir mostrando lo mismo después de migrar. Si alguien
 * borrara la fila de un teléfono, esa sí desaparecería, que es lo que quiere
 * alguien que lo borró.
 */
return new class extends Migration
{
    /** Horarios que estaban escritos a mano en las vistas. */
    private const HORARIOS = [
        // dia (ISO: 1 = lunes ... 7 = domingo), apertura, cierre
        [1, '08:00:00', '20:00:00'],
        [2, '08:00:00', '20:00:00'],
        [3, '08:00:00', '20:00:00'],
        [4, '08:00:00', '20:00:00'],
        [5, '08:00:00', '20:00:00'],
        [6, '09:00:00', '21:00:00'],
        [7, '09:00:00', '18:00:00'],
    ];

    /** Datos de contacto que estaban escritos a mano en el pie y la portada. */
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

            // 1 = lunes ... 7 = domingo, siguiendo la semana ISO. No se usa el
            // nombre del día porque un nombre está en español o en inglés según el
            // idioma, y una comparación por texto rompería con el primero.
            $table->unsignedTinyInteger('dia')->unique();

            $table->time('apertura');
            $table->time('cierre');

            // Un día puede estar cerrado sin borrar su horario: es la diferencia
            // entre "cerrado por hoy" y "no abierta ese día". Si se borrara la fila,
            // volver a abrirlo sería reescribir a mano las dos horas otra vez.
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });

        Schema::create('contenidos', function (Blueprint $table) {
            $table->id();

            // Clave en lugar de un campo por dato: agregar el número de Telegram es
            // una fila más y un par más en la vista, no otra migración.
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
        // El orden es el inverso al de creación: `contenidos` y `horarios` no
        // tienen claves foráneas entre sí, pero se dejan en el mismo orden en que
        // se crearon para que el rollback se lea en espejo.
        Schema::dropIfExists('contenidos');
        Schema::dropIfExists('horarios');
    }
};
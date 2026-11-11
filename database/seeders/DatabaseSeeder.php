<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\StockJornada;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Contraseñas por defecto, solo para desarrollo y pruebas.
     * En producción el seeder exige variables de entorno.
     */
    private const CONTRASENAS_DEFECTO = [
        'SEED_ADMIN_PASSWORD' => 'admin123',
        'SEED_CAJERO_PASSWORD' => 'personal123',
        'SEED_COCINA_PASSWORD' => 'cocina123',
        'SEED_CLIENTE_PASSWORD' => 'cliente123',
    ];

    public function run(): void
    {
        $this->usuarioInicial('admin@donaross.com', 'Administrador', User::ROL_ADMINISTRADOR, '70000001', 'SEED_ADMIN_PASSWORD');
        $this->usuarioInicial('personal@donaross.com', 'María García', User::ROL_CAJERO, '70000002', 'SEED_CAJERO_PASSWORD');
        $this->usuarioInicial('cocina@donaross.com', 'Cocina Doña Ross', User::ROL_COCINA, '70000004', 'SEED_COCINA_PASSWORD');
        $this->usuarioInicial('cliente@correo.com', 'Juan Pérez', User::ROL_CLIENTE, '70000003', 'SEED_CLIENTE_PASSWORD', 'Av. Principal #456');

        $cat1 = Categoria::firstOrCreate(['nombre' => 'Platos Principales'], ['descripcion' => 'Nuestros platos estrella', 'estado' => Categoria::ESTADO_ACTIVA]);
        $cat2 = Categoria::firstOrCreate(['nombre' => 'Bebidas'], ['descripcion' => 'Refrescos y jugos naturales', 'estado' => Categoria::ESTADO_ACTIVA]);
        $cat3 = Categoria::firstOrCreate(['nombre' => 'Postres'], ['descripcion' => 'Dulces caseros', 'estado' => Categoria::ESTADO_ACTIVA]);
        $productos = [
            ['nombre' => 'Silpancho Tradicional', 'descripcion' => 'Carne, arroz, papa y huevo frito.', 'precio' => 35, 'porciones' => 50, 'destacado' => true, 'categoria_id' => $cat1->id],
            ['nombre' => 'Fricasé Paceño', 'descripcion' => 'Cerdo cocido con chuño y maíz.', 'precio' => 30, 'porciones' => 40, 'destacado' => true, 'categoria_id' => $cat1->id],
            ['nombre' => 'Pique Macho', 'descripcion' => 'Carne, salchichas, papas fritas y huevo.', 'precio' => 45, 'porciones' => 30, 'destacado' => true, 'categoria_id' => $cat1->id],
            ['nombre' => 'Jugo de Limonada', 'descripcion' => 'Limonada natural refrescante.', 'precio' => 8, 'porciones' => 100, 'destacado' => false, 'categoria_id' => $cat2->id],
            ['nombre' => 'Postre de Helado', 'descripcion' => 'Helado artesanal de vainilla.', 'precio' => 12, 'porciones' => 25, 'destacado' => false, 'categoria_id' => $cat3->id],
        ];

        foreach ($productos as $p) {
            $porciones = $p['porciones'];
            unset($p['porciones']);

            $producto = Producto::updateOrCreate(['nombre' => $p['nombre']], array_merge($p, ['activo' => true]));
            StockJornada::updateOrCreate(
                ['producto_id' => $producto->id, 'fecha' => now()->toDateString()],
                ['inicial' => $porciones, 'motivo' => 'Carga inicial del sembrado.']
            );
        }

        Promocion::firstOrCreate(
            ['titulo' => '2x1 los Jueves'],
            ['descripcion' => 'Dos platos principales al precio de uno todos los jueves.', 'descuento_porcentaje' => 50, 'fecha_inicio' => now(), 'fecha_fin' => now()->addMonths(3), 'activo' => true]
        );

        Promocion::firstOrCreate(
            ['titulo' => 'Combo Familiar Ross'],
            ['descripcion' => '4 platos principales + jarra de refresco + postre de cortesía.', 'descuento_porcentaje' => 20, 'activo' => true]
        );
    }

    /**
     * Crea o actualiza un usuario inicial con su rol y estado activo.
     *
     * El rol y el estado activo no están en $fillable del modelo User,
     * así que se fijan de forma explícita con forceFill para que funcionen
     * tanto al sembrar con "php artisan db:seed" como al ejecutar el
     * seeder directamente desde código.
     */
    private function usuarioInicial(string $email, string $nombre, string $role, string $telefono, string $variablePassword, ?string $direccion = null): User
    {
        $usuario = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nombre,
                'password' => Hash::make($this->passwordInicial($variablePassword)),
                'telefono' => $telefono,
                'direccion' => $direccion,
            ]
        );

        $usuario->forceFill(['role' => $role, 'activo' => true])->save();

        return $usuario;
    }

    /**
     * Devuelve la contraseña inicial del usuario: la variable de entorno
     * indicada si existe, o la de desarrollo/pruebas. En producción, si
     * falta la variable, falla con un mensaje claro (nunca usa la por defecto).
     */
    private function passwordInicial(string $variable): string
    {
        $password = env($variable);

        if ($password !== null && $password !== '') {
            return $password;
        }

        if (app()->environment('production')) {
            throw new RuntimeException(
                "Falta la variable de entorno {$variable}. En producción no se permiten contraseñas por defecto: define la contraseña en las variables del entorno (p. ej. en Railway) y vuelve a ejecutar el seeder."
            );
        }

        return self::CONTRASENAS_DEFECTO[$variable];
    }
}

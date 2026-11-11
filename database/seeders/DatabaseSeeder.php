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

        // 1. Asegurar exactamente las 3 categorías requeridas
        $cat1 = Categoria::where('nombre', 'Platos Principales')->first()
            ?? Categoria::firstOrNew(['nombre' => 'Platos principales']);
        $cat1->nombre = 'Platos principales';
        $cat1->descripcion = 'Platos tradicionales y especialidades bolivianas.';
        $cat1->estado = Categoria::ESTADO_ACTIVA;
        $cat1->save();

        $cat2 = Categoria::firstOrNew(['nombre' => 'Bebidas']);
        $cat2->descripcion = 'Refrescos y bebidas tradicionales bolivianas.';
        $cat2->estado = Categoria::ESTADO_ACTIVA;
        $cat2->save();

        $cat3 = Categoria::firstOrNew(['nombre' => 'Postres']);
        $cat3->descripcion = 'Postres y delicias dulces caseras típicas.';
        $cat3->estado = Categoria::ESTADO_ACTIVA;
        $cat3->save();

        // Desactivar cualquier otra categoría no requerida
        Categoria::whereNotIn('id', [$cat1->id, $cat2->id, $cat3->id])
            ->update(['estado' => Categoria::ESTADO_INACTIVA]);

        $productos = [
            // Platos principales
            [
                'nombre' => 'Pique Macho',
                'descripcion' => 'Suculento plato tradicional con trozos de lomo de res, salchichas, papas fritas, huevo duro, cebolla, tomate y locoto.',
                'precio' => 45.00,
                'porciones' => 30,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Silpancho Cochabambino',
                'descripcion' => 'Fina carne apanada servida sobre cama de arroz blanco y papas doradas, coronada con dos huevos fritos y sarsa fresca.',
                'precio' => 35.00,
                'porciones' => 40,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'img/platos/plato_Silpancho_cochabambino.jpeg',
            ],
            [
                'nombre' => 'Fricasé Paceño',
                'descripcion' => 'Caldo tradicional y picante elaborado a base de carne de cerdo cocida a fuego lento con maíz pelado y chuño entero.',
                'precio' => 38.00,
                'porciones' => 35,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'img/platos/plato_fricase.jpeg',
            ],
            [
                'nombre' => 'Saice Tarijeño',
                'descripcion' => 'Clásico guisado tarijeño de carne picada con arvejas en ají colorado, servido con fideo, papa cocida y sarsa criolla.',
                'precio' => 32.00,
                'porciones' => 35,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'https://images.unsplash.com/photo-1574484284002-952d92456975?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Chicharrón de Cerdo',
                'descripcion' => 'Costillitas y trozos de cerdo crujientes cocidos en su propia manteca, acompañados de mote de maíz y llajwa casera.',
                'precio' => 40.00,
                'porciones' => 30,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Ají de Llisa',
                'descripcion' => 'Delicioso guiso andino de papas lisas picadas en juliana con carne de res, ají amarillo y hierbabuena tradicional.',
                'precio' => 28.00,
                'porciones' => 30,
                'destacado' => true,
                'categoria_id' => $cat1->id,
                'imagen' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&auto=format&fit=crop&q=80',
            ],
            // Bebidas
            [
                'nombre' => 'Mocochinchi tradicional',
                'descripcion' => 'Refrescante infusión hervida de duraznos deshidratados, aromatizada con canela, clavo de olor y azúcar caramelizada.',
                'precio' => 7.00,
                'porciones' => 80,
                'destacado' => false,
                'categoria_id' => $cat2->id,
                'imagen' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Api con pastel',
                'descripcion' => 'Bebida caliente y reconfortante de maíz morado con especias andinas, acompañada de pastel frito relleno de queso criollo.',
                'precio' => 10.00,
                'porciones' => 60,
                'destacado' => false,
                'categoria_id' => $cat2->id,
                'imagen' => 'https://images.unsplash.com/photo-1541658016709-82535e94bc69?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Somó',
                'descripcion' => 'Tradicional bebida cruceña a base de maíz frangollo cocido, leche cremosa, canela y azúcar al punto.',
                'precio' => 6.00,
                'porciones' => 50,
                'destacado' => false,
                'categoria_id' => $cat2->id,
                'imagen' => 'https://images.unsplash.com/photo-1556881286-fc6915169721?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Refresco de Linaza',
                'descripcion' => 'Refresco casero digestivo y saludable a base de semillas de linaza hervidas con cáscara de naranja y limón.',
                'precio' => 5.00,
                'porciones' => 70,
                'destacado' => false,
                'categoria_id' => $cat2->id,
                'imagen' => 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=800&auto=format&fit=crop&q=80',
            ],
            // Postres
            [
                'nombre' => 'Helado de Canela artesanal',
                'descripcion' => 'Tradicional helado de nieve batido a mano en barrilete de madera, aromatizado con canela paceña.',
                'precio' => 8.00,
                'porciones' => 50,
                'destacado' => false,
                'categoria_id' => $cat3->id,
                'imagen' => 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Dulce de Cayote con Queso',
                'descripcion' => 'Finas hebras de cayote confitadas en almíbar de clavo y naranja, servidas con tajada de queso fresco artesanal.',
                'precio' => 10.00,
                'porciones' => 40,
                'destacado' => false,
                'categoria_id' => $cat3->id,
                'imagen' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=800&auto=format&fit=crop&q=80',
            ],
            [
                'nombre' => 'Tawa-Tawas',
                'descripcion' => 'Masitas fritas crujientes y esponjosas, generosamente bañadas con miel de caña pura de la región.',
                'precio' => 6.00,
                'porciones' => 60,
                'destacado' => false,
                'categoria_id' => $cat3->id,
                'imagen' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&auto=format&fit=crop&q=80',
            ],
        ];

        $nombresNuevos = array_column($productos, 'nombre');
        Producto::whereNotIn('nombre', $nombresNuevos)
            ->update(['activo' => false, 'destacado' => false]);

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

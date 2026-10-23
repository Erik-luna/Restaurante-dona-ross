<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\StockJornada;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@donaross.com'],
            ['name' => 'Administrador', 'password' => Hash::make('admin123'), 'role' => User::ROL_ADMINISTRADOR, 'telefono' => '70000001', 'activo' => true]
        );
        User::updateOrCreate(
            ['email' => 'personal@donaross.com'],
            ['name' => 'María García', 'password' => Hash::make('personal123'), 'role' => User::ROL_CAJERO, 'telefono' => '70000002', 'activo' => true]
        );

        User::updateOrCreate(
            ['email' => 'cocina@donaross.com'],
            ['name' => 'Cocina Doña Ross', 'password' => Hash::make('cocina123'), 'role' => User::ROL_COCINA, 'telefono' => '70000004', 'activo' => true]
        );

        User::updateOrCreate(
            ['email' => 'cliente@correo.com'],
            ['name' => 'Juan Pérez', 'password' => Hash::make('cliente123'), 'role' => User::ROL_CLIENTE, 'telefono' => '70000003', 'direccion' => 'Av. Principal #456', 'activo' => true]
        );

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
}

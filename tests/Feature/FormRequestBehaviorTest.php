<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestBehaviorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->administrador()->create(['activo' => true]);
    }

    private function producto(array $atributos = []): Producto
    {
        return $this->crearProducto(10, array_merge(['precio' => 85.00], $atributos));
    }
    public function test_mensajes_de_validacion_en_espanol_llegan_a_la_peticion(): void
    {
        $admin = $this->admin();
        $producto = $this->producto();

        $response = $this->actingAs($admin)->put(route('admin.stock.update', $producto), []);

        $response->assertSessionHasErrors('cantidad');

        $errores = session('errors')->getBag('default');

        $this->assertSame(
            'Indica la cantidad de porciones.',
            $errores->first('cantidad'),
            'Los mensajes propios del formulario no se están mostrando: se está usando la traducción por defecto de Laravel.'
        );
    }

    public function test_la_maquina_de_estados_se_aplica_en_la_ruta_web(): void
    {
        $admin = $this->admin();

        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $entregado = Pedido::create([
            'user_id' => $cliente->id,
            'estado' => 'entregado',
            'total' => 50,
            'direccion_entrega' => 'Calle Falsa 123',
            'telefono_contacto' => '70000000',
        ]);
        $regresion = $this->actingAs($admin)->patch(route('admin.pedidos.estado', $entregado), [
            'estado' => 'pendiente',
        ]);

        $regresion->assertSessionHasErrors('estado');
        $this->assertSame(
            'entregado',
            $entregado->fresh()->estado,
            'Un pedido entregado pudo volver a pendiente: la máquina de estados no se está aplicando.'
        );

        $inventado = $this->actingAs($admin)->patch(route('admin.pedidos.estado', $entregado), [
            'estado' => 'volando',
        ]);

        $inventado->assertSessionHasErrors('estado');
    }
    public function test_una_transicion_legitima_se_sigue_pudiendo_hacer_en_la_web(): void
    {
        $admin = $this->admin();

        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $pendiente = Pedido::create([
            'user_id' => $cliente->id,
            'estado' => 'pendiente',
            'total' => 50,
            'direccion_entrega' => 'Calle Falsa 123',
            'telefono_contacto' => '70000000',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.pedidos.estado', $pendiente), [
            'estado' => 'confirmado',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('confirmado', $pendiente->fresh()->estado);
        $this->assertSame(
            $admin->id,
            $pendiente->fresh()->atendido_por,
            'El cambio de estado debe quedar atribuido a quien lo hizo.'
        );
    }
    public function test_se_puede_desactivar_un_producto_desmarcando_la_casilla(): void
    {
        $admin = $this->admin();
        $producto = $this->producto(['activo' => true, 'destacado' => true]);

        $response = $this->actingAs($admin)->put(route('admin.productos.update', $producto), [
            'nombre' => 'Asado de Doña Ross',
            'precio' => 90.00,
        ]);

        $response->assertSessionHasNoErrors();

        $producto = $producto->fresh();

        $this->assertFalse(
            (bool) $producto->activo,
            'El producto se reactivó solo: la casilla desmarcada no se puede guardar.'
        );
        $this->assertFalse(
            (bool) $producto->destacado,
            'Una casilla desmarcada debe guardarse como desmarcada.'
        );

        $this->actingAs($admin)->put(route('admin.productos.update', $producto), [
            'nombre' => 'Asado de Doña Ross',
            'precio' => 90.00,
            'activo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $producto->fresh()->activo);
    }
    public function test_un_producto_nace_activo_cuando_no_se_marca_la_casilla(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.productos.store'), [
            'nombre' => 'Silpancho',
            'precio' => 35.50,
            'porciones_iniciales' => 20,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Silpancho',
            'activo' => true,
        ]);

        $creado = Producto::where('nombre', 'Silpancho')->firstOrFail();

        $this->assertSame(20, $creado->disponibleEn());
    }
    public function test_editar_el_producto_no_reinicia_el_stock_de_la_jornada(): void
    {
        $admin = $this->admin();
        $producto = $this->producto();

        $this->actingAs($admin)->put(route('admin.productos.update', $producto), [
            'nombre' => 'Asado de Doña Ross',
            'precio' => 90.00,
            'porciones_iniciales' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            10,
            $producto->disponibleEn(),
            'La edición de la ficha no debe cambiar el stock de la jornada.'
        );
    }
}

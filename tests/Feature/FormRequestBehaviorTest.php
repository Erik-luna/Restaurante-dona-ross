<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de regresión sobre el COMPORTAMIENTO de los Form Requests.
 *
 * Estas pruebas no cubren una vulnerabilidad nueva: cubren tres defectos
 * silenciosos ya corregidos. Se incluyen porque los tres compartían la misma
 * característica, que es la que los hace peligrosos: NINGUNO lanzaba excepción,
 * NINGUNO rompía el lint y TODOS dejaban las pruebas en verde mientras la
 * funcionalidad era incorrecta. Un fallo que solo se manifiesta en pantalla es
 * el que sobrevive más tiempo en un repositorio.
 */
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

    /**
     * REGRESIÓN 1: los mensajes de validación en español llegaban a mostrarse.
     *
     * `BaseRequest` definía los mensajes en un método llamado `mensajes()` en
     * lugar de `messages()`. El nombre es casi idéntico, así que el editor y el
     * linter no señalaban nada, pero el método que consulta el framework al
     * construir el validador se llama `messages()`. Resultado: los 24 Form
     * Requests que definían `mensajesPropios()` compilaban, pasaban las pruebas
     * y no mostraban nunca su texto, porque Laravel recurría a su traducción
     * genérica en inglés.
     *
     * Esta prueba falla contra el código anterior: el mensaje de error sería
     * "The cantidad field is required." en lugar del esperado en español.
     */
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

    /**
     * REGRESIÓN 2: la máquina de estados de pedidos se aplica en el panel web.
     *
     * Un pedido en estado 'entregado' es terminal: degradarlo a 'pendiente'
     * falsearía las ventas del día y permitiría reabrir un pedido ya cerrado.
     * La comprobación vive en PedidoEstadoRequestWeb::after(), que lee el estado
     * actual del pedido y lo compara con la tabla de transiciones.
     */
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

        // `entregado` es terminal: degradarlo a `pendiente` falsearía las
        // métricas de venta del día.
        $regresion = $this->actingAs($admin)->patch(route('admin.pedidos.estado', $entregado), [
            'estado' => 'pendiente',
        ]);

        $regresion->assertSessionHasErrors('estado');
        $this->assertSame(
            'entregado',
            $entregado->fresh()->estado,
            'Un pedido entregado pudo volver a pendiente: la máquina de estados no se está aplicando.'
        );

        // Un estado inexistente debe seguir rechazándose por tipo.
        $inventado = $this->actingAs($admin)->patch(route('admin.pedidos.estado', $entregado), [
            'estado' => 'volando',
        ]);

        $inventado->assertSessionHasErrors('estado');
    }

    /**
     * REGRESIÓN 3: una transición legítima sigue siendo posible.
     *
     * El control de la regresión 2 solo tiene valor si no bloquea el uso normal
     * del panel: un pedido `pendiente` todavía debe poder confirmarse.
     */
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

    /**
     * REGRESIÓN 4: se puede DESACTIVAR un producto desde el panel.
     *
     * Un `<input type="checkbox">` sin marca no se envía en el cuerpo de la
     * petición. Por eso la lectura del campo no puede usar un valor por omisión
     * de `true`: si el administrador desmarca "Activo" y guarda, la clave
     * desaparecería del cuerpo y el producto volvería a activarse solo,
     * impidiendo desactivarlo desde la interfaz.
     *
     * Lo correcto es tratar un formulario de HTML como lo que es: un
     * ENVÍO COMPLETO del registro. Si la casilla no llega, está desmarcada.
     * La ambigüedad se resuelve en la capa de petición: si la casilla no llega,
     * se asume desmarcada.
     */
    public function test_se_puede_desactivar_un_producto_desmarcando_la_casilla(): void
    {
        $admin = $this->admin();
        $producto = $this->producto(['activo' => true, 'destacado' => true]);

        // `activo` y `destacado` NO se envían: es exactamente lo que hace el
        // navegador cuando ambas casillas están desmarcadas. Tampoco se envía el
        // stock: editar la ficha no debe tocar la jornada del día.
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

        // Y la operación inversa también tiene que funcionar: marcar la casilla
        // activa el producto.
        $this->actingAs($admin)->put(route('admin.productos.update', $producto), [
            'nombre' => 'Asado de Doña Ross',
            'precio' => 90.00,
            'activo' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $producto->fresh()->activo);
    }

    /**
     * REGRESIÓN 5: en el alta, un producto nace activo por omisión.
     *
     * Contraprueba de la anterior: si `activo` no se envía al CREAR (no hay
     * valor previo que conservar), el producto debe quedar disponible para la
     * venta en lugar de creado invisible.
     */
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

        // El alta también abre la jornada con esas 20 porciones. Si no, el producto
        // quedaría creado pero imposible de vender, y eso no se vería hasta que
        // alguien intentara comprarlo.
        $creado = Producto::where('nombre', 'Silpancho')->firstOrFail();

        $this->assertSame(20, $creado->disponibleEn());
    }

    /**
     * REGRESIÓN 6: editar la ficha no puede tocar el stock del día.
     *
     * El número de stock pertenece a la jornada, no al producto. Si el formulario de
     * edición lo aceptara, alguien podría recargar el día con un número más bajo que
     * lo ya vendido, y la jornada quedaría mostrando stock negativo sin ninguna venta
     * que lo explicara.
     */
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

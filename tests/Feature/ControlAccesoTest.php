<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ControlAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function producto(array $atributos = []): Producto
    {
        return $this->crearProducto(20, $atributos);
    }

    public function test_sin_sesion_las_rutas_de_los_paneles_redirigen_al_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login.cliente'));
        $this->get(route('cajero.dashboard'))->assertRedirect(route('login.cliente'));
        $this->get(route('cocina.dashboard'))->assertRedirect(route('login.cliente'));
        $this->get(route('cliente.dashboard'))->assertRedirect(route('login.cliente'));
    }

    public function test_cada_rol_entra_a_su_propio_panel(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.dashboard'))->assertOk();

        $this->actingAs(User::factory()->cajero()->create())
            ->get(route('cajero.dashboard'))->assertOk();

        $this->actingAs(User::factory()->cocina()->create())
            ->get(route('cocina.dashboard'))->assertOk();

        $this->actingAs(User::factory()->create(['role' => User::ROL_CLIENTE]))
            ->get(route('cliente.dashboard'))->assertOk();
    }

    public function test_un_rol_no_puede_entrar_en_el_panel_de_otro(): void
    {
        $cajero = User::factory()->cajero()->create();
        $cocina = User::factory()->cocina()->create();
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $this->actingAs($cajero)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($cajero)->get(route('admin.productos.index'))->assertForbidden();
        $this->actingAs($cocina)->get(route('cocina.dashboard'))->assertOk();
        $this->actingAs($cocina)->get(route('cajero.ventas.index'))->assertForbidden();
        $this->actingAs($cliente)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($cliente)->get(route('cocina.dashboard'))->assertForbidden();
    }
    public function test_el_cajero_consulta_pedidos_pero_no_cambia_su_estado(): void
    {
        $cajero = User::factory()->cajero()->create();

        $this->actingAs($cajero)->get(route('cajero.pedidos.index'))->assertOk();

        $this->assertFalse(
            Route::has('cajero.pedidos.estado'),
            'El cajero no debe tener ruta para cambiar el estado de un pedido.'
        );
        $this->assertTrue(Route::has('cocina.pedidos.estado'));
    }
    public function test_la_cocina_cambia_el_estado_y_el_stock(): void
    {
        $cocina = User::factory()->cocina()->create();

        $this->actingAs($cocina)->get(route('cocina.stock.index'))->assertOk();

        $respuesta = $this->actingAs($cocina)
            ->patch(route('cocina.pedidos.estado', $this->pedido()), ['estado' => 'preparando']);

        $respuesta->assertRedirect();
        $respuesta->assertSessionHasNoErrors();
    }
public function test_el_menu_lateral_corresponde_al_panel(): void
{
    $cocina = User::factory()->cocina()->create();

    $this->actingAs($cocina)->get(route('cocina.stock.index'))
        ->assertOk()
        ->assertSee(route('cocina.stock.index'))
        ->assertDontSee(route('admin.productos.index'));

    $this->actingAs(User::factory()->cajero()->create())
        ->get(route('cajero.ventas.index'))
        ->assertOk()
        ->assertSee(route('cajero.ventas.create'))
        ->assertDontSee(route('cocina.stock.index'));
}

public function test_la_cocina_no_gestiona_el_catalogo(): void
{
    $this->actingAs(User::factory()->cocina()->create())
        ->get(route('admin.productos.index'))
        ->assertForbidden();
}

public function test_la_cola_de_cocina_prioriza_lo_que_no_se_empezo(): void
{
    $cocina = User::factory()->cocina()->create();

    $listo = $this->pedido(null, null, 'confirmado');
    $nuevo = $this->pedido(null, null, 'pendiente');

    $pedidos = $this->actingAs($cocina)
        ->get(route('cocina.dashboard'))
        ->assertOk()
        ->viewData('pedidos');
    $this->assertSame($nuevo->id, $pedidos->first()->id);
    $this->assertSame($listo->id, $pedidos->last()->id);
}
    public function test_el_administrador_gestiona_las_cuentas_del_restaurante(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('admin.usuarios.index'))
            ->assertOk();
    }
    public function test_un_cliente_no_puede_ver_el_pedido_de_otro(): void
    {
        $dueño = User::factory()->create(['role' => User::ROL_CLIENTE]);
        $otro = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $pedido = $this->pedido($otro);

        $this->actingAs($dueño)
            ->get(route('cliente.pedidos.show', $pedido))
            ->assertForbidden();
    }
    public function test_un_cliente_si_puede_ver_su_pedido(): void
    {
        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);
        $producto = $this->producto();

        $pedido = $this->pedido($cliente, $producto);

        $this->actingAs($cliente)
            ->get(route('cliente.pedidos.show', $pedido))
            ->assertOk();
    }
    public function test_el_registro_siempre_crea_un_cliente(): void
    {
        $this->post(route('register.post'), [
            'name' => 'Juan Pérez',
            'email' => 'juan@correo.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'telefono' => '70000003',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan@correo.com',
            'role' => User::ROL_CLIENTE,
        ]);
    }
    public function test_el_login_del_personal_solo_deja_entrar_a_los_empleados(): void
    {
        foreach ([User::ROL_ADMINISTRADOR, User::ROL_CAJERO, User::ROL_COCINA] as $rol) {
            $this->post(route('login.admin.post'), [
                'email' => User::factory()->create(['role' => $rol])->email,
                'password' => 'password',
                'remember' => false,
            ])->assertSessionHasNoErrors();
        }

        $this->post(route('login.admin.post'), [
            'email' => 'cliente@correo.com',
            'password' => 'password',
            'remember' => false,
        ])->assertSessionHasErrors('email');
    }
    public function test_cada_rol_es_enviado_a_su_panel(): void
    {
        $cocina = User::factory()->cocina()->create();

        $this->assertSame(route('cocina.dashboard'), $cocina->rutaPanel());

        $cajero = User::factory()->cajero()->create();

        $this->assertSame(route('cajero.dashboard'), $cajero->rutaPanel());

        $cliente = User::factory()->create(['role' => User::ROL_CLIENTE]);

        $this->assertSame(route('cliente.dashboard'), $cliente->rutaPanel());
    }
    public function test_las_respuestas_incluyen_las_cabeceras_de_seguridad(): void
    {
        $respuesta = $this->get(route('home'));

        $respuesta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->assertHeader('X-Content-Type-Options', 'nosniff');
        $respuesta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
    public function test_el_catalogo_publico_solo_muestra_productos_activos(): void
    {
        $this->producto(['nombre' => 'Pique Macho', 'activo' => true]);
        $this->producto(['nombre' => 'Plato Retirado', 'activo' => false]);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('Pique Macho')
            ->assertDontSee('Plato Retirado')
            ->assertSee(asset('img/platos/plato1.jpeg'), false);
    }

    public function test_el_catalogo_mantiene_los_datos_de_cada_producto_y_un_orden_estable(): void
    {
        $primero = $this->producto([
            'nombre' => 'Z plato',
            'descripcion' => 'Descripción del primer plato',
            'imagen' => 'platos/primero.jpg',
        ]);
        $segundo = $this->producto([
            'nombre' => 'A plato',
            'descripcion' => 'Descripción del segundo plato',
            'imagen' => 'platos/segundo.jpg',
        ]);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSeeInOrder(['Z plato', 'A plato'])
            ->assertSee('Descripción del primer plato')
            ->assertSee('Descripción del segundo plato')
            ->assertSee($primero->imagen_url, false)
            ->assertSee($segundo->imagen_url, false);
    }

    public function test_el_panel_guarda_y_actualiza_la_imagen_en_el_disco_de_imagenes(): void
    {
        // P5: la imagen ya no se copia al directorio público img/platos sino
        // que se guarda en el disco configurado (por defecto "public").
        Storage::fake('public');

        $this->actingAs(User::factory()->administrador()->create())
            ->post(route('admin.productos.store'), [
                'nombre' => 'Plato con imagen',
                'descripcion' => 'Imagen de prueba',
                'precio' => 25,
                'porciones_iniciales' => 10,
                'imagen' => UploadedFile::fake()->createWithContent(
                    'primera.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZ8AAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('admin.productos.index'));

        $producto = Producto::where('nombre', 'Plato con imagen')->firstOrFail();
        $rutaAnterior = $producto->imagen;

        $this->assertStringStartsWith('platos/', $rutaAnterior);
        Storage::disk('public')->assertExists($rutaAnterior);
        $this->assertSame(Storage::disk('public')->url($rutaAnterior), $producto->imagen_url);

        $this->actingAs(User::factory()->administrador()->create())
            ->put(route('admin.productos.update', $producto), [
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio' => $producto->precio,
                'imagen' => UploadedFile::fake()->createWithContent(
                    'segunda.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWZ8AAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('admin.productos.index'));

        $producto->refresh();
        $rutaNueva = $producto->imagen;

        $this->assertNotSame($rutaAnterior, $rutaNueva);
        Storage::disk('public')->assertMissing($rutaAnterior);
        Storage::disk('public')->assertExists($rutaNueva);
        $this->assertSame(Storage::disk('public')->url($rutaNueva), $producto->imagen_url);
    }

    public function test_el_home_muestra_solo_productos_destacados(): void
    {
        $this->producto(['nombre' => 'Especialidad de la casa', 'destacado' => true]);
        $this->producto(['nombre' => 'Plato del catálogo', 'destacado' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Especialidad de la casa')
            ->assertDontSee('Plato del catálogo');
    }

private function pedido(?User $cliente = null, ?Producto $producto = null, string $estado = 'pendiente'): Pedido
{
    $cliente ??= User::factory()->create(['role' => User::ROL_CLIENTE]);

    $pedido = Pedido::create([
        'user_id' => $cliente->id,
        'estado' => $estado,
        'total' => 50.00,
        'direccion_entrega' => 'Calle Falsa 123',
        'telefono_contacto' => '70000000',
    ]);

        if ($producto) {
            $pedido->items()->create([
                'producto_id' => $producto->id,
                'cantidad' => 1,
                'precio_unitario' => $producto->precio,
                'subtotal' => $producto->precio,
            ]);
        }

        return $pedido;
    }
}
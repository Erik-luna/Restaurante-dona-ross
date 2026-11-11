<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| P6: la página de delivery deja de fingir que registra pedidos
|--------------------------------------------------------------------------
|
| El formulario "Pedido Rápido a Domicilio" mostraba un mensaje de éxito
| pero no creaba ningún pedido real. La corrección elimina la ruta POST
| /delivery/enviar, el método DeliveryController::store y el request
| asociado; la página /delivery pasa a ser informativa y dirige al
| catálogo online, donde sí se registra el pedido con checkout.
*/

test('p6 la ruta para enviar pedidos rapidos de delivery ya no existe', function () {
    expect(Route::has('delivery.store'))->toBeFalse();

    $respuesta = $this->post('/delivery/enviar', [
        'cliente' => 'Juan Perez',
        'telefono' => '71234567',
        'direccion' => 'Av. Heroinas 123',
        'pedido' => 'Silpancho Tradicional',
    ]);

    $respuesta->assertNotFound();
});

test('p6 la pagina de delivery es informativa y enlaza al catalogo', function () {
    $this->get('/delivery')
        ->assertOk()
        ->assertSee('Pedir ahora', false)
        ->assertSee(route('catalogo'), false)
        ->assertDontSee('delivery/enviar')
        ->assertDontSee('Confirmar y Enviar Pedido Delivery');
});

test('p6 el request del formulario rapido fue eliminado del proyecto', function () {
    expect(class_exists(\App\Http\Requests\DeliveryRequest::class))->toBeFalse();
});

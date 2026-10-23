<?php

namespace Tests\Feature;

use App\Models\Contenido;
use App\Models\Horario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContenidoTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->administrador()->create();
    }

    private function horarios(array $horasPorDia): array
    {
        $dias = [];

        foreach ($horasPorDia as $dia => [$apertura, $cierre, $activo]) {
            $dias[$dia] = [
                'dia' => $dia,
                'apertura' => $apertura,
                'cierre' => $cierre,
                'activo' => (int) $activo,
            ];
        }

        return $dias;
    }

    private function relojEn(string $fecha, int $hora, int $minuto = 0): int
    {
        $momento = Carbon::parse($fecha.' '.$hora.':'.str_pad((string) $minuto, 2, '0', STR_PAD_LEFT));
        Carbon::setTestNow($momento);

        return $momento->isoWeekday();
    }

    public function test_el_administrador_guarda_los_horarios(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.horarios'), [
                'dias' => $this->horarios([
                    1 => ['09:00', '13:00', 1],
                    2 => ['09:00', '13:00', 1],
                    7 => ['10:00', '12:00', 1],
                ]),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Horarios actualizados.');

        $this->assertSame('09:00:00', Horario::where('dia', 1)->value('apertura'));
        $this->assertSame('13:00:00', Horario::where('dia', 1)->value('cierre'));
        $this->assertSame('08:00:00', Horario::where('dia', 3)->value('apertura'));
    }

    public function test_el_administrador_guarda_los_datos_de_contacto(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.contacto'), [
                'telefono' => '+591 71234567',
                'whatsapp' => '59171234567',
                'direccion' => 'Calle Sucre 123, La Paz',
                'facebook' => 'https://facebook.com/donaross',
                'instagram' => '',
                'correo' => 'hola@donaross.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Datos de contacto actualizados.');

        $this->assertSame('+591 71234567', Contenido::valorDe('telefono'));
        $this->assertSame('Calle Sucre 123, La Paz', Contenido::valorDe('direccion'));
    }

    public function test_una_hora_invalida_no_se_guarda(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.horarios'), [
                'dias' => $this->horarios([1 => ['8 de la mañana', '13:00', 1]]),
            ])
            ->assertSessionHasErrors('dias.1.apertura');
        $this->assertSame('08:00:00', Horario::where('dia', 1)->value('apertura'));
    }

    public function test_no_se_admite_el_mismo_dia_dos_veces(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.horarios'), [
                'dias' => [
                    ['dia' => 1, 'apertura' => '09:00', 'cierre' => '13:00', 'activo' => 1],
                    ['dia' => 1, 'apertura' => '15:00', 'cierre' => '20:00', 'activo' => 1],
                ],
            ])
            ->assertSessionHasErrors('dias');
    }

    public function test_el_telefono_no_puede_guardar_texto(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.contacto'), [
                'telefono' => 'llamar al mostrador',
                'whatsapp' => '59170000000',
                'direccion' => 'Av. Siempre Viva 742',
                'facebook' => '',
                'instagram' => '',
                'correo' => '',
            ])
            ->assertSessionHasErrors('telefono');

        $this->actingAs($this->admin())
            ->put(route('admin.contenidos.contacto'), [
                'telefono' => '+591 70000000',
                'whatsapp' => '59170000000',
                'direccion' => 'Av. Siempre Viva 742',
                'facebook' => 'facebook.com/donaross',
                'instagram' => '',
                'correo' => '',
            ])
            ->assertSessionHasErrors('facebook');
    }

    public function test_la_pantalla_de_contenido_muestra_los_siete_dias_y_los_valores_actuales(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.contenidos.index'))
            ->assertOk()
            ->assertSee('Contenido del Sitio')
            ->assertSee('Lunes')
            ->assertSee('Domingo')
            ->assertSee('value="08:00"', false)
            ->assertSee('+591 70000000');
    }

    public function test_solo_el_administrador_edita_el_contenido(): void
    {
        $dias = $this->horarios([1 => ['09:00', '13:00', 1]]);

        $this->actingAs(User::factory()->cajero()->create())
            ->put(route('admin.contenidos.horarios'), ['dias' => $dias])
            ->assertForbidden();

        $this->actingAs(User::factory()->cocina()->create())
            ->put(route('admin.contenidos.contacto'), ['telefono' => '+591 00000000'])
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROL_CLIENTE]))
            ->get(route('admin.contenidos.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROL_CLIENTE]))
            ->put(route('admin.contenidos.horarios'), ['dias' => $dias])
            ->assertForbidden();
    }

    public function test_el_indicador_de_abierto_sigue_los_horarios(): void
    {
        $dia = $this->relojEn('2026-10-05', 12);

        Horario::where('dia', $dia)->update(['apertura' => '09:00:00', 'cierre' => '20:00:00', 'activo' => true]);
        $this->assertTrue(Horario::deHoy()->estaAbierto());
        $this->relojEn('2026-10-05', 9, 0);
        $this->assertTrue(Horario::deHoy()->estaAbierto());
        $this->relojEn('2026-10-05', 20, 0);
        $this->assertFalse(Horario::deHoy()->estaAbierto());
        $this->relojEn('2026-10-05', 7, 0);
        $this->assertFalse(Horario::deHoy()->estaAbierto());
    }

    public function test_un_dia_marcado_cerrado_no_abre_aunque_llegue_su_hora(): void
    {
        $dia = $this->relojEn('2026-10-05', 12);

        Horario::where('dia', $dia)->update(['apertura' => '09:00:00', 'cierre' => '20:00:00', 'activo' => false]);

        $this->assertFalse(Horario::deHoy()->estaAbierto());
    }

    public function test_un_turno_que_pasa_la_medianoche_sigue_abierto_de_noche(): void
    {
        $dia = $this->relojEn('2026-10-05', 1);

        Horario::where('dia', $dia)->update(['apertura' => '18:00:00', 'cierre' => '02:00:00', 'activo' => true]);

        $this->assertTrue(Horario::deHoy()->estaAbierto());

        $this->relojEn('2026-10-05', 3);
        $this->assertFalse(Horario::deHoy()->estaAbierto());

        $this->relojEn('2026-10-05', 23);
        $this->assertTrue(Horario::deHoy()->estaAbierto());
    }

    public function test_la_portada_dice_si_esta_abierto_y_no_siempre_que_si(): void
    {
        $dia = $this->relojEn('2026-10-05', 12);
        Horario::where('dia', $dia)->update(['apertura' => '09:00:00', 'cierre' => '20:00:00', 'activo' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Abierto ahora')
            ->assertDontSee('Cerrado ahora');

        $this->relojEn('2026-10-05', 23);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cerrado ahora')
            ->assertDontSee('Abierto ahora');
    }

    public function test_la_portada_muestra_el_telefono_y_la_direccion_guardados(): void
    {
        $this->actingAs($this->admin())->put(route('admin.contenidos.contacto'), [
            'telefono' => '+591 (2) 71234567',
            'whatsapp' => '59171234567',
            'direccion' => 'Calle Sucre 123, La Paz',
            'facebook' => '',
            'instagram' => '',
            'correo' => '',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('+591 (2) 71234567')
            ->assertSee('Calle Sucre 123, La Paz')
            ->assertSee('tel:+591271234567')
            ->assertSee('https://wa.me/59171234567');
    }

    public function test_el_pie_no_muestra_redes_que_no_estan_configuradas(): void
    {
        Contenido::where('clave', 'facebook')->update(['valor' => '']);
        Contenido::where('clave', 'instagram')->update(['valor' => '']);

        $html = $this->get(route('catalogo'))->assertOk()->getContent();

        $this->assertStringNotContainsString('title="Facebook"', $html);
        $this->assertStringNotContainsString('title="Instagram"', $html);

        Contenido::where('clave', 'facebook')->update(['valor' => 'https://facebook.com/donaross']);

        $this->assertStringContainsString('title="Facebook"', $this->get(route('catalogo'))->getContent());
    }

    public function test_el_pie_agrupa_los_dias_que_comparten_horario(): void
    {
        $grupos = Horario::agrupados();

        $this->assertSame('Lunes a Viernes', $grupos[0]['etiqueta']);
        $this->assertSame('08:00:00 - 20:00:00', $grupos[0]['rango']);
        $this->assertSame('Sábado', $grupos[1]['etiqueta']);
        $this->assertSame('09:00:00 - 21:00:00', $grupos[1]['rango']);
        $this->assertSame('Domingo', $grupos[2]['etiqueta']);
        Horario::where('dia', 3)->update(['cierre' => '22:00:00']);

        $grupos = Horario::agrupados();

        $this->assertSame('Lunes y Martes', $grupos[0]['etiqueta']);
        $this->assertSame('Miércoles', $grupos[1]['etiqueta']);
    }

    public function test_un_dia_cerrado_no_aparece_en_el_pie(): void
    {
        $this->relojEn('2026-10-05', 12);
        $dia = now()->isoWeekday();
        Horario::where('dia', $dia)->update(['activo' => false]);

        $html = $this->get(route('catalogo'))->assertOk()->getContent();

        $this->assertStringNotContainsString(Horario::DIAS[$dia], $html);
    }
}
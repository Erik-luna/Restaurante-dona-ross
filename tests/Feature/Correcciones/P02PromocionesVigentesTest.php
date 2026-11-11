<?php

use App\Models\Promocion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * P2: las promociones solo deben mostrarse cuando están vigentes:
 * activas, con fecha_inicio nula o pasada y fecha_fin nula o no vencida
 * (fechas inclusivas). Cobertura CP-09.
 */

function p2Promocion(string $titulo, ?string $inicio, ?string $fin, bool $activo = true): Promocion
{
    return Promocion::create([
        'titulo' => $titulo,
        'descripcion' => 'Promoción de prueba P2.',
        'descuento_porcentaje' => 10,
        'fecha_inicio' => $inicio,
        'fecha_fin' => $fin,
        'activo' => $activo,
    ]);
}

afterEach(function () {
    Carbon::setTestNow();
});

test('CP-09: solo se muestra la promoción vigente en la portada y en promociones', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));

    p2Promocion('Promo Vigente P2', '2026-11-10', '2026-11-12');
    p2Promocion('Promo Vencida P2', '2026-10-01', '2026-11-10'); // terminó ayer
    p2Promocion('Promo Futura P2', '2026-11-12', '2026-12-31'); // empieza mañana

    foreach ([route('home'), route('promociones')] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('Promo Vigente P2')
            ->and($html)->not->toContain('Promo Vencida P2')
            ->and($html)->not->toContain('Promo Futura P2');
    }
});

test('las fechas de la promoción son inclusivas: fin hoy sigue vigente', function () {
    Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00'));

    p2Promocion('Promo Termina Hoy P2', '2026-11-01', '2026-11-11');
    p2Promocion('Promo Empieza Hoy P2', '2026-11-11', '2026-11-30');

    foreach ([route('home'), route('promociones')] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('Promo Termina Hoy P2')
            ->and($html)->toContain('Promo Empieza Hoy P2');
    }
});

test('una promoción sin fechas y activa se sigue mostrando', function () {
    p2Promocion('Promo Sin Fechas P2', null, null);

    foreach ([route('home'), route('promociones')] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->toContain('Promo Sin Fechas P2');
    }
});

test('una promoción inactiva no se muestra aunque sus fechas estén vigentes', function () {
    p2Promocion('Promo Inactiva P2', '2026-11-10', '2026-11-12', false);

    foreach ([route('home'), route('promociones')] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->not->toContain('Promo Inactiva P2');
    }
});

# Documentación técnica — Restaurante “Doña Ross”

> **Modo solo lectura respetado durante el análisis.** Solo se leyeron archivos y se ejecutaron comandos de consulta (`php -v`, `php artisan --version`, `composer -V`, versión del cliente MySQL/MariaDB y `git log`). No se modificó, creó ni eliminó ningún archivo, ni se ejecutaron migraciones o cambios en la base de datos.
>
> Documento generado el 2026-11-05.

---

## 0. Versiones

**Salida de `php -v`:**

```text
PHP 8.2.12 (cli) (built: Oct 24 2023 21:15:15) (ZTS Visual C++ 2019 x64)
Copyright (c) The PHP Group
Zend Engine v4.2.12, Copyright (c) Zend Technologies
```

**Salida de `php artisan --version`:**

```text
Laravel Framework 12.69.3
```

**Salida de `composer -V`:**

```text
Composer version 2.9.5 2026-01-29 11:40:53
PHP version 8.2.12 (C:\xampp\php\php.exe)
```

**MySQL / MariaDB:** `mysql` no está en el `PATH`. La versión encontrada en XAMPP es:

```text
C:\xampp\mysql\bin\mysql.exe  Ver 15.1 Distrib 10.4.32-MariaDB, for Win64 (AMD64)
```

Es **MariaDB 10.4.32**, no Oracle MySQL.

**XAMPP:** `C:\xampp\readme_en.txt`, línea 1:

```text
###### ApacheFriends XAMPP Version 8.2.12 ######
```

**Bootstrap:** no está declarado en `package.json` (allí aparecen Tailwind CSS `^4.0.0`, Vite `^7.0.7`, etc.). Se carga por CDN en `resources/views/layouts/app.blade.php`:

- Línea 11: Bootstrap **5.3.3** CSS.
- Línea 616: Bootstrap **5.3.3** JS bundle.
- Línea 12: Bootstrap Icons **1.11.3**.

```blade
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 1. Incremento 1 — Autenticación, roles y catálogo

### 1.a Rutas públicas y grupos protegidos

**Archivo:** `routes/web.php` | **Método/clase:** rutas públicas | **Líneas:** 24–26

```php
Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/catalogo', [PublicController::class, 'catalogo'])->name('catalogo');
Route::get('/promociones', [PublicController::class, 'promociones'])->name('promociones');
```

**Archivo:** `routes/web.php` | **Método/clase:** grupos `auth` + `role` | **Líneas:** 44–50, 56, 73 y 83 (fragmento con omisiones)

```php
Route::middleware(['auth', 'role:cliente'])->prefix('cliente')->name('cliente.')->group(function () {
    Route::get('/dashboard', [ClienteDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/carrito', [CarritoController::class, 'index'])->name('carrito');
    Route::post('/carrito/agregar/{producto}', [CarritoController::class, 'add'])->name('carrito.add');
    Route::put('/carrito/{producto}', [CarritoController::class, 'update'])->name('carrito.update');
    Route::delete('/carrito/{producto}', [CarritoController::class, 'remove'])->name('carrito.remove');
    // ...
});
Route::middleware(['auth', 'role:cajero'])->prefix('cajero')->name('cajero.')->group(function () {
    // ...
});
Route::middleware(['auth', 'role:cocina'])->prefix('cocina')->name('cocina.')->group(function () {
    // ...
});
Route::middleware(['auth', 'role:administrador'])->prefix('admin')->name('admin.')->group(function () {
    // ...
});
```

### 1.b Catálogo público, destacados/promociones y estado abierto/cerrado

**Archivo:** `app/Http/Controllers/PublicController.php` | **Método/clase:** `PublicController::index()` | **Líneas:** 13–32

```php
public function index()
{

    $destacados = Producto::where('activo', true)
        ->where('destacado', true)
        ->select('productos.*')
        ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha())
        ->orderBy('id', 'asc')
        ->take(3)
        ->get();

    $promociones = Promocion::where('activo', true)
        ->where(function ($q) {
            $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
        })
        ->take(2)
        ->get();

    return view('home', compact('destacados', 'promociones'));
}
```

**Archivo:** `app/Http/Controllers/PublicController.php` | **Método/clase:** `PublicController::catalogo()` | **Líneas:** 34–53

```php
public function catalogo(CatalogoFilterRequest $request)
{
    $query = Producto::with('categoria')
        ->where('activo', true)
        ->select('productos.*')
        ->selectRaw(Producto::EXPRESION_DISPONIBLE.' as disponible', Producto::rangoDeFecha());

    if ($categoria = $request->validated('categoria')) {
        $query->where('categoria_id', (int) $categoria);
    }

    if ($termino = $request->termino()) {
        $query->where('nombre', 'like', Texto::likeSeguro($termino));
    }

    $productos = $query->orderBy('id', 'asc')->paginate(9)->withQueryString();
    $categorias = Categoria::activas()->orderBy('nombre')->get();

    return view('catalogo', compact('productos', 'categorias'));
}
```

**Archivo:** `app/Models/Horario.php` | **Método/clase:** `Horario::estaAbierto()` | **Líneas:** 44–56

```php
public function estaAbierto(?string $hora = null): bool
{
    if (! $this->activo) {
        return false;
    }

    $hora ??= now()->format('H:i:s');
    $cruzaMedianoche = $this->cierre <= $this->apertura;

    return $cruzaMedianoche
        ? ($hora >= $this->apertura || $hora < $this->cierre)
        : ($hora >= $this->apertura && $hora < $this->cierre);
}
```

**Archivo:** `app/Http/Middleware/CompartirContenido.php` | **Método/clase:** `CompartirContenido::handle()` | **Líneas:** 16–18

```php
View::share([
    'horarioHoy' => Horario::deHoy(),
    'horariosAgrupados' => Horario::agrupados(),
```

**Archivo:** `resources/views/home.blade.php` | **Método/clase:** indicador visual abierto/cerrado | **Líneas:** 209–217

```blade
<h5 class="fw-bold mb-1 font-serif">Horario de Atención</h5>
<p class="text-muted small mb-1">
    {{ $horarioHoy?->nombreDia() }}: {{ $horarioHoy?->rango() ?? 'no configurado' }}
</p>
@if($horarioHoy?->estaAbierto())
    <span class="badge bg-success-subtle text-success small">Abierto ahora</span>
@else
    <span class="badge bg-secondary-subtle text-secondary small">Cerrado ahora</span>
@endif
```

> El estado abierto/cerrado **no se calcula en `PublicController`** ni en una tabla `configuracion`: se calcula en `Horario::estaAbierto()` y se comparte globalmente con `CompartirContenido`. La tabla real es `horarios`.

### 1.c Controlador de platos/productos: `store` y validación

**Archivo:** `app/Http/Controllers/ProductoController.php` | **Método/clase:** `ProductoController::store()` | **Líneas:** 37–57

```php
public function store(ProductoRequest $request, StockService $stock)
{
    $imagen = $request->hasFile('imagen')
        ? ['imagen' => $this->guardarImagen($request->file('imagen'))]
        : [];

    DB::transaction(function () use ($request, $imagen, $stock) {
        $producto = Producto::create($request->atributosProducto($imagen));

        $stock->aplicar(
            $producto,
            StockJornada::ACCION_CARGAR,
            $request->porcionesIniciales(),
            'Alta del producto en el catálogo.',
            now()->toDateString(),
            $request->user()->id
        );
    });

    return redirect()->route('admin.productos.index')->with('success', 'Producto creado correctamente.');
}
```

**Archivo:** `app/Http/Requests/ProductoRequest.php` | **Método/clase:** `ProductoRequest::rules()` | **Líneas:** 13–30

```php
public function rules(): array
{
    return [
        'nombre' => ['required', 'string', 'min:3', 'max:150'],
        'descripcion' => ['nullable', 'string', 'max:1000'],
        'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999.99'],
        'porciones_iniciales' => [
            'nullable',
            'integer',
            'min:0',
            'max:100000',
            Rule::requiredIf(fn () => ! $this->esActualizacion()),
        ],
        'categoria_id' => ['nullable', 'integer', 'exists:categorias,id'],
        'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'destacado' => ['nullable', 'boolean'],
        'activo' => ['nullable', 'boolean'],
    ];
}
```

### 1.d Inicio de sesión y cierre de sesión

**Archivo:** `app/Http/Controllers/AuthController.php` | **Método/clase:** `AuthController::loginCliente()` | **Líneas:** 20–54 (fragmento con omisión)

```php
public function loginCliente(LoginRequest $request)
{
    $credentials = $request->credenciales();

    // ... comprobaciones de rol de cliente y cuenta activa (líneas 24–35)

    $recordar = $request->recordar();

    if (! Auth::attempt($credentials, $recordar)) {
        Log::notice('Intento de inicio de sesión fallido.', [
            'email' => $credentials['email'],
            'ip' => $request->ip(),
        ]);

        throw ValidationException::withMessages([
            'email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.',
        ]);
    }

    $this->regenerarSesion($request);

    return redirect()->intended(route('cliente.dashboard'))
        ->with('success', 'Sesión iniciada correctamente. Bienvenido a Doña Ross.');
}
```

También existe `AuthController::loginAdmin()` en las líneas **60–92**, con la misma lógica para personal.

**Archivo:** `app/Http/Controllers/AuthController.php` | **Método/clase:** `AuthController::logout()` | **Líneas:** 111–118

```php
public function logout(Request $request)
{
    Auth::logout();

    $this->regenerarSesion($request, invalidar: true);

    return redirect()->route('home')->with('success', 'Sesión cerrada correctamente.');
}
```

---

## 2. Incremento 2 — Stock y cocina

### 2.a Descuento de stock, validación de insuficiencia y bloqueo

**Archivo:** `app/Services/StockService.php` | **Método/clase:** `StockService::descontar()` | **Líneas:** 11–34

```php
public function descontar(array $items): void
{
    foreach ($items as $item) {
        $producto = $item['producto'];
        $cantidad = (int) $item['cantidad'];

        if (! $producto->activo) {
            throw new RuntimeException('Uno de los productos seleccionados no se encuentra activo.');
        }

        $jornada = $this->bloquearJornadaDe($producto);

        if ($jornada->disponible() < $cantidad) {
            throw new RuntimeException(sprintf(
                'Stock insuficiente para %s: quedan %d porción(es) y se pidieron %d.',
                $producto->nombre,
                $jornada->disponible(),
                $cantidad
            ));
        }

        $jornada->consumir($cantidad);
    }
}
```

**Archivo:** `app/Services/StockService.php` | **Método/clase:** `StockService::bloquearJornadaDe()` | **Líneas:** 56–68

```php
public function bloquearJornadaDe(Producto $producto, ?string $fecha = null): StockJornada
{
    $this->jornadaDe($producto, $fecha);

    try {
        return StockJornada::lockForUpdate()
            ->where('producto_id', $producto->id)
            ->whereDate('fecha', $fecha ?? now()->toDateString())
            ->firstOrFail();
    } catch (ModelNotFoundException) {
        throw new RuntimeException('No se pudo bloquear la jornada de stock de '.$producto->nombre.'.');
    }
}
```

**Sí usa `lockForUpdate()`.** Además, las llamadas a `descontar()` se hacen dentro de `DB::transaction(...)`:

- `CarritoController::checkout()`, línea 114.
- `VentaController::store()`, línea 49.

La creación de la jornada también maneja concurrencia con la restricción única y `QueryException` en `StockJornada::obtenerOCrear()` (líneas 81–97).

### 2.b Panel de stock: registrar o actualizar porciones

**Archivo:** `app/Http/Controllers/StockController.php` | **Método/clase:** `StockController::update()` | **Líneas:** 34–48

```php
public function update(StockAjusteRequestWeb $request, Producto $producto, StockService $stock)
{
    $accion = $request->validated('accion');
    $cantidad = (int) $request->validated('cantidad');
    $motivo = $request->validated('motivo');
    $fecha = $request->fechaAjuste();

    $antes = 0;
    $despues = 0;

    DB::transaction(function () use ($stock, $producto, $accion, $cantidad, $motivo, $fecha, $request, &$antes, &$despues) {
        $antes = $stock->jornadaDe($producto, $fecha)->disponible();
        $stock->aplicar($producto, $accion, $cantidad, $motivo, $fecha, $request->user()->id);
        $despues = $stock->jornadaDe($producto, $fecha)->disponible();
    });
```

Las acciones permitidas son `cargar`, `reponer` y `merma`. La respuesta con el mensaje de éxito está en las líneas 50–61.

**Archivo:** `app/Http/Requests/StockAjusteRequestWeb.php` | **Método/clase:** `StockAjusteRequestWeb::rules()` | **Líneas:** 15–27

```php
public function rules(): array
{
    return [
        'accion' => ['required', Rule::in([StockJornada::ACCION_CARGAR, StockJornada::ACCION_REPONER, StockJornada::ACCION_MERMA])],
        'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
        'fecha' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        'motivo' => [
            'nullable',
            'string',
            'max:200',
            Rule::requiredIf(fn () => $this->input('accion') === StockJornada::ACCION_MERMA),
        ],
    ];
}
```

### 2.c Dónde se bloquea o marca como agotado un plato en el catálogo

**Archivo:** `app/Models/Producto.php` | **Método/clase:** `Producto::EXPRESION_DISPONIBLE` | **Líneas:** 13–16

```php
public const EXPRESION_DISPONIBLE = '(SELECT COALESCE(j.inicial, 0) + COALESCE(j.entradas, 0)'
    .' - COALESCE(j.mermas, 0) - COALESCE(j.consumidos, 0)'
    .' FROM stock_jornadas j WHERE j.producto_id = productos.id'
    .' AND j.fecha >= ? AND j.fecha < ?)';
```

**Archivo:** `app/Models/Producto.php` | **Método/clase:** `Producto::scopeConDisponible()` | **Líneas:** 113–122

```php
public function scopeConDisponible(Builder $query, ?string $fecha = null, int $minimo = 1): Builder
{
    $rango = static::rangoDeFecha($fecha);
    $sql = static::EXPRESION_DISPONIBLE;

    return $query
        ->select('productos.*')
        ->selectRaw($sql.' as disponible', $rango)
        ->whereRaw($sql.' >= ?', [...$rango, $minimo]);
}
```

**Archivo:** `resources/views/catalogo.blade.php` | **Método/clase:** marca visual “Agotado” | **Líneas:** 75–78

```blade
@if((int) $producto->disponible <= 0)
    <span class="position-absolute top-0 end-0 m-3 badge rounded-pill px-3 py-2 bg-danger shadow-sm">
        <i class="bi bi-x-circle me-1"></i> Agotado
    </span>
```

Además, `CarritoController::add()` rechaza el producto si `disponibleEn() < 1` (líneas 52–54), y el botón de “Agregar” solo aparece si `disponible > 0` (línea 106 de la misma vista). En `ventas.create` se filtran los productos con `conDisponible()` (línea 29 de `VentaController`).

---

## 3. Incremento 3 — Pedidos y delivery

### 3.a Carrito: agregar, actualizar, eliminar y almacenamiento

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `getCarrito()` / `saveCarrito()` | **Líneas:** 17–25

```php
private function getCarrito(): array
{
    return session('carrito', []);
}

private function saveCarrito(array $carrito): void
{
    session(['carrito' => $carrito]);
}
```

**El carrito se guarda en la sesión de Laravel**, bajo la clave `carrito`. No existe una tabla `carritos` ni un modelo `Carrito`.

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::add()` | **Líneas:** 45–67

```php
public function add(Request $request, Producto $producto)
{
    if (! auth()->check() || ! auth()->user()->esCliente()) {
        return redirect()->route('login.cliente')
            ->with('error', 'Debes iniciar sesión o registrarte para agregar platillos al carrito.');
    }

    if (! $producto->activo || $producto->disponibleEn() < 1) {
        return back()->with('error', 'Platillo no disponible actualmente.');
    }

    $carrito = $this->getCarrito();
    $cantidadActual = (int) ($carrito[$producto->id] ?? 0);

    if ($cantidadActual + 1 > $producto->disponibleEn()) {
        return back()->with('error', 'No hay más unidades disponibles de este platillo.');
    }

    $carrito[$producto->id] = $cantidadActual + 1;
    $this->saveCarrito($carrito);

    return back()->with('success', '"'.$producto->nombre.'" agregado a tu pedido.');
}
```

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::update()` | **Líneas:** 69–90

```php
public function update(Request $request, Producto $producto)
{
    $data = $request->validate([
        'cantidad' => ['required', 'integer', 'min:1', 'max:50'],
    ], [
        'cantidad.required' => 'La cantidad es obligatoria.',
        'cantidad.min' => 'La cantidad mínima es 1.',
        'cantidad.max' => 'Para pedidos mayores a 50 unidades, contacta directamente con el restaurante.',
    ]);

    $cantidad = (int) $data['cantidad'];

    if ($cantidad > $producto->disponibleEn()) {
        return back()->with('error', 'Stock insuficiente para esa cantidad solicitada.');
    }

    $carrito = $this->getCarrito();
    $carrito[$producto->id] = $cantidad;
    $this->saveCarrito($carrito);

    return back()->with('success', 'Carrito actualizado con éxito.');
}
```

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::remove()` | **Líneas:** 92–99

```php
public function remove(Producto $producto)
{
    $carrito = $this->getCarrito();
    unset($carrito[$producto->id]);
    $this->saveCarrito($carrito);

    return back()->with('success', 'Platillo retirado de tu pedido.');
}
```

### 3.b Registro del pedido, validación, detalles y descuento de stock

> **Importante:** el pedido de delivery **no se registra en `PedidoController`**. Se registra en `CarritoController::checkout()`. `PedidoController` solo tiene `index`, `show` y `updateEstado`.

**Archivo:** `app/Http/Requests/CheckoutRequest.php` | **Método/clase:** `CheckoutRequest::rules()` | **Líneas:** 14–21

```php
public function rules(): array
{
    return [
        'direccion_entrega' => ['required', ...Reglas::DIRECCION],
        'telefono_contacto' => ['required', ...Reglas::TELEFONO],
        'notas' => ['nullable', 'string', 'max:500'],
    ];
}
```

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::checkout()` | **Líneas:** 101–114

```php
public function checkout(CheckoutRequest $request, StockService $stock)
{
    $carrito = $this->getCarrito();

    if ($carrito === []) {
        return redirect()
            ->route('cliente.carrito')
            ->with('error', 'Tu carrito está vacío. Agrega platillos antes de continuar.');
    }

    $total = 0.0;

    try {
        DB::transaction(function () use ($carrito, $request, $stock, &$total) {
```

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::checkout()` — creación de pedido, detalles y stock | **Líneas:** 132–152

```php
$pedido = Pedido::create([
    'user_id' => $request->user()->id,
    'estado' => Pedido::ESTADO_PENDIENTE,
    'total' => $total,
    'direccion_entrega' => $request->validated('direccion_entrega'),
    'telefono_contacto' => $request->validated('telefono_contacto'),
    'notas' => $request->validated('notas'),
]);

foreach ($itemsData as $item) {
    PedidoItem::create([
        'pedido_id' => $pedido->id,
        'producto_id' => $item['producto']->id,
        'cantidad' => $item['cantidad'],
        'precio_unitario' => $item['producto']->precio,
        'subtotal' => $item['subtotal'],
    ]);
}

$stock->descontar($itemsData);
```

El cálculo del total y de cada `subtotal` está en las líneas **117–130**:

```php
$subtotal = (float) $producto->precio * $cantidad;
$total += $subtotal;
$itemsData[] = compact('producto', 'cantidad') + ['subtotal' => $subtotal];
```

### 3.c Cambio de estado del pedido y valores aceptados

**Archivo:** `app/Models/Pedido.php` | **Método/clase:** constantes de estado y pago | **Líneas:** 11–34

```php
public const ESTADO_PENDIENTE = 'pendiente';
public const ESTADO_CONFIRMADO = 'confirmado';
public const ESTADO_PREPARANDO = 'preparando';
public const ESTADO_ENVIADO = 'enviado';
public const ESTADO_ENTREGADO = 'entregado';
public const ESTADO_CANCELADO = 'cancelado';
public const ESTADOS = [
    self::ESTADO_PENDIENTE,
    self::ESTADO_CONFIRMADO,
    self::ESTADO_PREPARANDO,
    self::ESTADO_ENVIADO,
    self::ESTADO_ENTREGADO,
    self::ESTADO_CANCELADO,
];

public const PAGO_EFECTIVO = 'efectivo';
public const PAGO_QR = 'qr';
public const PAGO_TARJETA = 'tarjeta';

public const METODOS_PAGO = [
    self::PAGO_EFECTIVO,
    self::PAGO_QR,
    self::PAGO_TARJETA,
];
```

**Archivo:** `app/Http/Controllers/PedidoController.php` | **Método/clase:** `PedidoController::updateEstado()` | **Líneas:** 27–46

```php
public function updateEstado(PedidoEstadoRequestWeb $request, Pedido $pedido)
{
    $estadoAnterior = $pedido->estado;
    $estadoNuevo = (string) $request->validated('estado');

    $pedido->forceFill([
        'estado' => $estadoNuevo,
        'atendido_por' => auth()->id(),
    ])->save();

    Log::info('Estado de pedido modificado.', [
        'pedido_id' => $pedido->id,
        'desde' => $estadoAnterior,
        'hacia' => $estadoNuevo,
        'usuario_id' => auth()->id(),
        'ip' => $request->ip(),
    ]);

    return back()->with('success', 'Estado del pedido actualizado correctamente.');
}
```

**Archivo:** `app/Http/Requests/PedidoEstadoRequestWeb.php` | **Método/clase:** `PedidoEstadoRequestWeb::rules()` | **Líneas:** 25–30

```php
public function rules(): array
{
    return [
        'estado' => ['required', 'string', Rule::in(self::ESTADOS)],
    ];
}
```

Las transiciones permitidas están en `PedidoEstadoRequestWeb::TRANSICIONES`, líneas **11–18**, y se validan en `after()`, líneas **37–61**. Solo administrador y cocina pueden cambiar el estado (líneas 20–24).

### 3.d Nota de venta digital

**NO ENCONTRADO** como método, clase o vista específica llamada `nota_venta`, `nota de venta`, PDF o comprobante generado. Lo que existe es una vista HTML de comprobante/ticket:

**Archivo:** `app/Http/Controllers/VentaController.php` | **Método/clase:** `VentaController::show()` | **Líneas:** 120–125

```php
public function show(Venta $venta)
{
    $venta->load(['user', 'items.producto', 'atendidoPor', 'caja.user']);

    return view('ventas.show', compact('venta'));
}
```

**Archivo:** `resources/views/ventas/show.blade.php` | **Método/clase:** vista “Comprobante de Venta” | **Líneas:** 24–31

```blade
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-ross p-4 p-md-5 shadow-lg" style="border-top: 5px solid var(--ross-orange) !important;">
            <div class="text-center pb-4 mb-4 border-bottom">
                <img src="{{ asset('img/logo.jpeg') }}" alt="Logo" class="brand-logo-img mb-2" style="width: 50px; height: 50px;">
                <h4 class="fw-bold font-serif mb-0" style="color: var(--ross-primary);">Doña Ross Restaurant</h4>
                <p class="text-muted small mb-1">Sabor Tradicional & Especialidades</p>
                <span class="badge badge-ross-gold text-dark px-3 py-1 fw-bold">TICKET DE VENTA #{{ $venta->id }}</span>
            </div>
```

Para delivery, el detalle equivalente es:

**Archivo:** `app/Http/Controllers/ClienteDashboardController.php` | **Método/clase:** `ClienteDashboardController::showPedido()` | **Líneas:** 26–35

```php
public function showPedido(Pedido $pedido)
{
    if ($pedido->user_id !== auth()->id()) {
        abort(403, 'Este pedido no pertenece a tu cuenta.');
    }

    $pedido->load('items.producto');

    return view('cliente.pedido-detalle', compact('pedido'));
}
```

El `README.md` afirma que la confirmación “genera la nota de venta”, pero en el código `CarritoController::checkout()` solo crea el `Pedido`, sus `PedidoItem` y redirige a `cliente.pedidos`; no genera un documento aparte.

---

## 4. Incremento 4 — Caja, ventas y reportes

### 4.a Apertura de caja y validación de caja ya abierta

**Archivo:** `app/Http/Controllers/CajaController.php` | **Método/clase:** `CajaController::create()` | **Líneas:** 31–42

```php
public function create()
{
    $caja = Caja::abiertaDeHoy(auth()->id());

    if ($caja !== null) {
        return redirect()
            ->route(auth()->user()->panel().'.caja.show', $caja)
            ->with('info', 'Ya tienes una caja abierta. Ciérrala con su arqueo antes de abrir otra.');
    }

    return view('caja.create');
}
```

**Archivo:** `app/Http/Controllers/CajaController.php` | **Método/clase:** `CajaController::store()` | **Líneas:** 44–65

```php
public function store(CajaAperturaRequest $request)
{
    $caja = Caja::create([
        'user_id' => $request->user()->id,
        'fecha' => today(),
        'monto_inicial' => $request->validated('monto_inicial'),
        'estado' => Caja::ABIERTA,
        'apertura_at' => now(),
        'observacion_apertura' => $request->validated('observacion_apertura'),
    ]);

    Log::info('Caja abierta.', [
        'caja_id' => $caja->id,
        'usuario_id' => $request->user()->id,
        'monto_inicial' => $caja->monto_inicial,
        'ip' => $request->ip(),
    ]);

    return redirect()
        ->route($request->user()->panel().'.caja.show', $caja)
        ->with('success', 'Caja abierta con Bs. '.number_format((float) $caja->monto_inicial, 2).'.');
}
```

**Archivo:** `app/Http/Requests/CajaAperturaRequest.php` | **Método/clase:** `CajaAperturaRequest::after()` | **Líneas:** 29–45

```php
public function after(): array
{
    return [
        function ($validator) {
            $yaHayCajaAbierta = Caja::where('user_id', $this->user()->id)
                ->where('estado', Caja::ABIERTA)
                ->exists();

            if ($yaHayCajaAbierta) {
                $validator->errors()->add(
                    'monto_inicial',
                    'Ya tienes una caja abierta. Ciérrala con su arqueo antes de abrir otra.'
                );
            }
        },
    ];
}
```

> La validación es **por usuario**: impide que el mismo usuario tenga dos cajas abiertas, no que exista una caja abierta de otro usuario.

### 4.b Venta de salón: método, validación y descuento de stock

**Archivo:** `app/Http/Requests/VentaLocalRequestWeb.php` | **Método/clase:** `VentaLocalRequestWeb::rules()` | **Líneas:** 15–25

```php
public function rules(): array
{
    return [
        'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'cliente')->where('activo', true)],
        'items' => ['required', 'array', 'min:1', 'max:50'],
        'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
        'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:50'],
        'notas' => ['nullable', 'string', 'max:500'],
        'metodo_pago' => ['nullable', 'string', Rule::in(Venta::METODOS_PAGO)],
        'monto_recibido' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
    ];
}
```

**Archivo:** `app/Http/Controllers/VentaController.php` | **Método/clase:** `VentaController::store()` — validación de caja | **Líneas:** 36–46

```php
public function store(VentaLocalRequestWeb $request, StockService $stock)
{
    $data = $request->validated();
    $ventaId = null;
    $caja = Caja::abiertaDeHoy(auth()->id());

    if ($caja === null) {
        return redirect()
            ->route(auth()->user()->panel().'.caja.create')
            ->with('error', 'Primero abre tu caja del día para poder registrar ventas.');
    }
```

**Archivo:** `app/Http/Controllers/VentaController.php` | **Método/clase:** `VentaController::store()` — venta, detalles y stock | **Líneas:** 80–104

```php
$venta = Venta::create([
    'user_id' => $data['user_id'] ?? null,
    'caja_id' => $caja->id,
    'estado' => Venta::PAGADA,
    'total' => $total,
    'notas' => $data['notas'] ?? null,
    'atendido_por' => auth()->id(),
    'metodo_pago' => $metodo,
    'monto_recibido' => $metodo === Venta::PAGO_EFECTIVO ? $recibido : null,
    'cambio' => $metodo === Venta::PAGO_EFECTIVO ? round($recibido - $total, 2) : null,
]);

$ventaId = $venta->id;

foreach ($itemsData as $item) {
    VentaItem::create([
        'venta_id' => $venta->id,
        'producto_id' => $item['producto']->id,
        'cantidad' => $item['cantidad'],
        'precio_unitario' => $item['producto']->precio,
        'subtotal' => $item['subtotal'],
    ]);
}
$stock->descontar($itemsData);
```

El cálculo del total, la validación de efectivo insuficiente y el armado de `itemsData` están en las líneas **53–78**.

### 4.c Cierre de caja y arqueo

**Archivo:** `app/Models/Caja.php` | **Método/clase:** `Caja::resumen()` | **Líneas:** 79–95

```php
$ventas = (float) $this->ventas()
    ->where('estado', Venta::PAGADA)
    ->sum('total');

$cobros = (float) $this->cobros()
    ->whereNotNull('pagado_at')
    ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
    ->sum('total');

$movimientos = $this->movimientos()
    ->selectRaw('tipo, COALESCE(SUM(monto), 0) as total')
    ->groupBy('tipo')
    ->pluck('total', 'tipo');

$entradas = (float) ($movimientos['entrada'] ?? 0);
$salidas = (float) ($movimientos['salida'] ?? 0);
$esperado = (float) $this->monto_inicial + $ventas + $cobros + $entradas - $salidas;
```

La diferencia se calcula en la línea **105**:

```php
'diferencia' => (float) ($this->total_declarado ?? 0) - $esperado,
```

**Archivo:** `app/Http/Controllers/CajaController.php` | **Método/clase:** `CajaController::storeCierre()` | **Líneas:** 163–185

```php
public function storeCierre(CajaCierreRequest $request, Caja $caja)
{
    $declarado = (float) $request->validated('total_declarado');

    $cajaCerrada = DB::transaction(function () use ($caja, $declarado, $request) {
        $caja = Caja::lockForUpdate()->findOrFail($caja->id);
        $resumen = $caja->resumen();

        $caja->forceFill([
            'estado' => Caja::CERRADA,
            'cierre_at' => now(),
            'total_ventas' => $resumen['ventas'],
            'total_cobros' => $resumen['cobros'],
            'total_entradas' => $resumen['entradas'],
            'total_salidas' => $resumen['salidas'],
            'total_esperado' => $resumen['esperado'],
            'total_declarado' => $declarado,
            'diferencia' => round($declarado - $resumen['esperado'], 2),
            'observacion_cierre' => $request->validated('observacion_cierre'),
        ])->save();

        return $caja;
    });
```

> Fórmula del monto esperado: **monto inicial + ventas pagadas + cobros de delivery + entradas − salidas**.
> Diferencia: **total declarado − total esperado**. Si es positiva, sobró; si es negativa, faltó.

### 4.d Consultas de reportes

**Archivo:** `app/Services/ReporteService.php` | **Método/clase:** `ReporteService::ventasPorDia()` | **Líneas:** 17–39

```php
public function ventasPorDia(int $dias = self::DIAS): array
{
    $inicio = today()->subDays($dias - 1);

    $serie = $this->serieDiaria($inicio, today());

    $filas = [];

    for ($fecha = $inicio->copy(); $fecha->lte(today()); $fecha->addDay()) {
        $clave = $fecha->toDateString();
        $salon = $serie['salon'][$clave] ?? 0.0;
        $delivery = $serie['delivery'][$clave] ?? 0.0;

        $filas[] = [
            'dia' => $clave,
            'salon' => $salon,
            'delivery' => $delivery,
            'total' => $salon + $delivery,
        ];
    }

    return $filas;
}
```

**Archivo:** `app/Services/ReporteService.php` | **Método/clase:** `ReporteService::serieDiaria()` — consultas SQL | **Líneas:** 141–158

```php
$porDia = static function (string $columna) use ($desde, $hasta) {
    return DB::table('ventas')
        ->where('estado', Venta::PAGADA)
        ->whereDate($columna, '>=', $desde->toDateString())
        ->whereDate($columna, '<=', $hasta->toDateString())
        ->selectRaw('DATE('.$columna.') as dia, SUM(total) as total')
        ->groupBy('dia')
        ->pluck('total', 'dia');
};
$salon = $porDia('created_at');
$delivery = DB::table('pedidos')
    ->whereNotNull('pagado_at')
    ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
    ->whereDate('pagado_at', '>=', $desde->toDateString())
    ->whereDate('pagado_at', '<=', $hasta->toDateString())
    ->selectRaw('DATE(pagado_at) as dia, SUM(total) as total')
    ->groupBy('dia')
    ->pluck('total', 'dia');
```

**Archivo:** `app/Services/ReporteService.php` | **Método/clase:** `ReporteService::ventasPorMes()` | **Líneas:** 40–53

```php
public function ventasPorMes(int $meses = self::MESES): array
{
    $dias = max(28, $meses * 31);
    $serie = $this->serieDiaria(today()->subDays($dias - 1), today());

    $acumulado = [];

    foreach ($serie['salon'] as $dia => $monto) {
        $mes = substr($dia, 0, 7);

        $acumulado[$mes] ??= ['salon' => 0.0, 'delivery' => 0.0];
        $acumulado[$mes]['salon'] += $monto;
        $acumulado[$mes]['delivery'] += $serie['delivery'][$dia] ?? 0.0;
    }
```

**Archivo:** `app/Services/ReporteService.php` | **Método/clase:** `ReporteService::platosMasVendidos()` | **Líneas:** 101–118

```php
$ventas = DB::table('venta_items')
    ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
    ->where('ventas.estado', Venta::PAGADA)
    ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(cantidad * precio_unitario) as importe')
    ->groupBy('producto_id');
$pedidos = DB::table('pedido_items')
    ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
    ->where('pedidos.estado', '!=', Pedido::ESTADO_CANCELADO)
    ->selectRaw('producto_id, SUM(cantidad) as unidades, SUM(cantidad * precio_unitario) as importe')
    ->groupBy('producto_id');
$ranking = DB::query()
    ->fromSub($ventas->unionAll($pedidos), 'movimientos')
    ->selectRaw('producto_id, SUM(movimientos.unidades) as unidades, SUM(movimientos.importe) as importe')
    ->groupBy('producto_id')
    ->orderByDesc('unidades')
    ->orderByDesc('importe')
    ->limit($limite)
    ->get();
```

El reporte de delivery se cuenta por **`pagado_at`**, no por la fecha de creación del pedido.

### 4.e Registro como ingreso de caja de un delivery cobrado

**Archivo:** `app/Http/Controllers/CajaController.php` | **Método/clase:** `CajaController::cobrar()` | **Líneas:** 118–139

```php
public function cobrar(PedidoCobroRequest $request, Pedido $pedido)
{
    $caja = $request->cajaAbierta();
    $metodo = $request->validated('metodo_pago');
    $cobrado = DB::transaction(function () use ($pedido, $caja, $metodo, $request) {
        $pedido = Pedido::lockForUpdate()->findOrFail($pedido->id);

        $total = (float) $pedido->total;
        $recibido = $metodo === Pedido::PAGO_EFECTIVO
            ? (float) $request->validated('monto_recibido')
            : $total;

        $pedido->forceFill([
            'caja_id' => $caja->id,
            'metodo_pago' => $metodo,
            'monto_recibido' => $metodo === Pedido::PAGO_EFECTIVO ? $recibido : null,
            'cambio' => $metodo === Pedido::PAGO_EFECTIVO ? round($recibido - $total, 2) : null,
            'pagado_at' => now(),
        ])->save();

        return $pedido;
    });
```

**Archivo:** `app/Models/Caja.php` | **Método/clase:** `Caja::resumen()` — inclusión de cobros | **Líneas:** 83–86

```php
$cobros = (float) $this->cobros()
    ->whereNotNull('pagado_at')
    ->where('estado', '!=', Pedido::ESTADO_CANCELADO)
    ->sum('total');
```

> El delivery cobrado no crea un `caja_movimientos`; se asocia al pedido con `caja_id` y se suma como `total_cobros` en el arqueo.

---

## 5. Seguridad

### 5.a Middleware de roles completo y su registro

**Archivo:** `app/Http/Middleware/CheckRole.php` | **Método/clase:** `CheckRole` — primera parte | **Líneas:** 1–16

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login.cliente')
                ->with('error', 'Debes iniciar sesión.');
        }
```

**Archivo:** `app/Http/Middleware/CheckRole.php` | **Método/clase:** `CheckRole` — segunda parte | **Líneas:** 17–30

```php
        if ($request->user()->activo === false) {
            auth()->logout();

            return redirect()->route('home')->with('error', 'Tu cuenta está desactivada.');
        }
        $rolesPermitidos = array_map('trim', explode(',', implode(',', $roles)));

        if (! in_array($request->user()->role, $rolesPermitidos, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
```

**Archivo:** `bootstrap/app.php` | **Método/clase:** registro del alias `role` | **Líneas:** 16–19

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => CheckRole::class,
    ]);
```

No hay `app/Http/Kernel.php`; Laravel 12 registra el middleware en `bootstrap/app.php`.

### 5.b Cifrado/hash de contraseña

**Archivo:** `app/Models/User.php` | **Método/clase:** `User::casts()` | **Líneas:** 35–41

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];
}
```

**Archivo:** `database/migrations/2026_10_04_000002_renombrar_roles_del_sistema.php` | **Método/clase:** inserción del usuario de cocina | **Líneas:** 20–29

```php
if (! DB::table('users')->where('email', 'cocina@donaross.com')->exists()) {
    DB::table('users')->insert([
        'name' => 'Cocina Doña Ross',
        'email' => 'cocina@donaross.com',
        'password' => Hash::make('cocina123'),
        'role' => 'cocina',
        'activo' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
```

**Archivo:** `app/Http/Controllers/AuthController.php` | **Método/clase:** verificación de credenciales | **Líneas:** 39–48

```php
if (! Auth::attempt($credentials, $recordar)) {
    Log::notice('Intento de inicio de sesión fallido.', [
        'email' => $credentials['email'],
        'ip' => $request->ip(),
    ]);

    throw ValidationException::withMessages([
        'email' => 'Credenciales incorrectas. Verifica tu correo y contraseña.',
    ]);
}
```

La contraseña se cifra automáticamente por el cast `'hashed'` del modelo `User`; `Auth::attempt()` compara usando el hash almacenado.

### 5.c Ejemplo de formulario Blade con `@csrf`

**Archivo:** `resources/views/auth/login-admin.blade.php` | **Método/clase:** formulario de acceso del personal | **Líneas:** 16–24

```blade
<form action="{{ route('login.admin.post') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label">Correo Corporativo</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0" style="border-radius: 12px 0 0 12px; border-color: #D8D2C6;">
                <i class="bi bi-person-badge text-muted"></i>
            </span>
            <input type="email" class="form-control border-start-0 ps-0" name="email" value="{{ old('email') }}" placeholder="admin@donaross.com" required autofocus style="border-radius: 0 12px 12px 0;">
        </div>
    </div>
```

### 5.d Validación de entradas y HTML sin escapar

**Sí hay validación.** Se usan Form Requests de forma extensiva: `ProductoRequest`, `LoginRequest`, `CheckoutRequest`, `CajaAperturaRequest`, `CajaCierreRequest`, `StockAjusteRequestWeb`, `VentaLocalRequestWeb`, `PedidoEstadoRequestWeb`, etc. También hay un `$request->validate()` directo en el carrito:

**Archivo:** `app/Http/Controllers/CarritoController.php` | **Método/clase:** `CarritoController::update()` | **Líneas:** 71–77

```php
$data = $request->validate([
    'cantidad' => ['required', 'integer', 'min:1', 'max:50'],
], [
    'cantidad.required' => 'La cantidad es obligatoria.',
    'cantidad.min' => 'La cantidad mínima es 1.',
    'cantidad.max' => 'Para pedidos mayores a 50 unidades, contacta directamente con el restaurante.',
]);
```

**Sí hay HTML sin escapar (`{!! !!}`).** Aparece solo en el reporte administrativo, para inyectar datos JSON en Chart.js:

**Archivo:** `resources/views/admin/reportes.blade.php` | **Método/clase:** salida sin escapar de JSON | **Líneas:** 199, 236 y 240

```blade
$json = fn ($valor) => json_encode($valor, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
```

```blade
labels: {!! $json(collect($porDia)->pluck('dia')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all()) !!},
datasets: [
    {
        label: 'Salón',
        data: {!! $json(collect($porDia)->pluck('salon')->map(fn ($v) => (float) $v)->all()) !!},
```

Las demás apariciones de `{!! !!}` están en las líneas **246, 259, 263 y 272**. El helper `$json` usa `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`, por lo que no es una salida directa de HTML de usuario, aunque técnicamente sí evita el escape normal de Blade.

---

## 6. Base de datos

> Fuente: `database/migrations/`. Los tipos se expresan como los genera Laravel 12 sobre MySQL/MariaDB. `timestamps()` significa `created_at` y `updated_at`, ambos `timestamp NULL`.

### 6.a Esquema de tablas

#### `users`

Migraciones: `0001_01_01_000000_create_users_table.php` y `2026_07_03_000001_add_role_fields_to_users_table.php`, modificado por `2026_10_04_000002_renombrar_roles_del_sistema.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| name | varchar(255) | NO | — | — |
| email | varchar(255) | NO | UQ | — |
| role | enum('administrador','cajero','cocina','cliente') | NO | — | 'cliente' |
| telefono | varchar(20) | SÍ | — | NULL |
| direccion | varchar(255) | SÍ | — | NULL |
| activo | tinyint(1) | NO | — | 1 |
| email_verified_at | timestamp | SÍ | — | NULL |
| password | varchar(255) | NO | — | — |
| remember_token | varchar(100) | SÍ | — | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `categorias`

Migración: `2026_07_03_000002_create_categorias_table.php`, modificada por `2026_10_04_000003_renombrar_categorias_activo_a_estado.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| nombre | varchar(255) | NO | — | — |
| descripcion | text | SÍ | — | NULL |
| estado | varchar(255) | NO | — | 'activo' |
| created_at, updated_at | timestamp | SÍ | — | NULL |

Valores usados por el modelo: `'activo'` e `'inactivo'`.

#### `productos`

Migración: `2026_07_03_000003_create_productos_table.php`. La columna `stock` fue eliminada en `2026_10_04_000009_mueve_stock_a_jornadas.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| categoria_id | bigint unsigned | SÍ | FK → categorias.id, nullOnDelete | NULL |
| nombre | varchar(255) | NO | — | — |
| descripcion | text | SÍ | — | NULL |
| precio | decimal(10,2) | NO | — | — |
| imagen | varchar(255) | SÍ | — | NULL |
| destacado | tinyint(1) | NO | — | 0 |
| activo | tinyint(1) | NO | — | 1 |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `stock_jornadas` — tabla real de stock

Migración: `2026_10_04_000008_create_stock_jornadas_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| producto_id | bigint unsigned | NO | FK → productos.id, cascadeOnDelete | — |
| fecha | date | NO | — | — |
| inicial | int unsigned | NO | — | 0 |
| entradas | int unsigned | NO | — | 0 |
| mermas | int unsigned | NO | — | 0 |
| consumidos | int unsigned | NO | — | 0 |
| motivo | varchar(255) | SÍ | — | NULL |
| user_id | bigint unsigned | SÍ | FK → users.id, nullOnDelete | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

Claves adicionales: `UNIQUE(producto_id, fecha)` e índice sobre `fecha`.

#### `promociones`

Migración: `2026_07_03_000005_create_promociones_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| titulo | varchar(255) | NO | — | — |
| descripcion | text | SÍ | — | NULL |
| descuento_porcentaje | decimal(5,2) | SÍ | — | NULL |
| fecha_inicio | date | SÍ | — | NULL |
| fecha_fin | date | SÍ | — | NULL |
| imagen | varchar(255) | SÍ | — | NULL |
| activo | tinyint(1) | NO | — | 1 |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `pedidos`

Migración: `2026_07_03_000004_create_pedidos_table.php`, modificada por `2026_10_04_000005_add_cobro_to_pedidos_table.php` y `2026_10_04_000007_mueve_ventas_locales_a_ventas.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| user_id | bigint unsigned | NO | FK → users.id, cascadeOnDelete | — |
| estado | enum('pendiente','confirmado','preparando','enviado','entregado','cancelado') | NO | — | 'pendiente' |
| total | decimal(10,2) | NO | — | — |
| direccion_entrega | varchar(255) | SÍ | — | NULL |
| telefono_contacto | varchar(20) | SÍ | — | NULL |
| notas | text | SÍ | — | NULL |
| atendido_por | bigint unsigned | SÍ | FK → users.id, nullOnDelete | NULL |
| caja_id | bigint unsigned | SÍ | FK → cajas.id, nullOnDelete | NULL |
| metodo_pago | enum('efectivo','qr','tarjeta') | SÍ | — | NULL |
| monto_recibido | decimal(10,2) | SÍ | — | NULL |
| cambio | decimal(10,2) | SÍ | — | NULL |
| pagado_at | timestamp | SÍ | índice | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

La columna `tipo` existía inicialmente (`online`/`local`), pero fue eliminada por la migración `2026_10_04_000007`.

#### `pedido_items` — equivalente a “detalle_pedidos”

Migración: `2026_07_03_000004_create_pedidos_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| pedido_id | bigint unsigned | NO | FK → pedidos.id, cascadeOnDelete | — |
| producto_id | bigint unsigned | NO | FK → productos.id, cascadeOnDelete | — |
| cantidad | int | NO | — | — |
| precio_unitario | decimal(10,2) | NO | — | — |
| subtotal | decimal(10,2) | NO | — | — |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `ventas`

Migración: `2026_10_04_000006_create_ventas_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| user_id | bigint unsigned | SÍ | FK → users.id, nullOnDelete | NULL |
| caja_id | bigint unsigned | SÍ | FK → cajas.id, nullOnDelete | NULL |
| estado | enum('pagada','anulada') | NO | — | 'pagada' |
| total | decimal(10,2) | NO | — | — |
| metodo_pago | enum('efectivo','qr','tarjeta') | NO | — | 'efectivo' |
| monto_recibido | decimal(10,2) | SÍ | — | NULL |
| cambio | decimal(10,2) | SÍ | — | NULL |
| notas | text | SÍ | — | NULL |
| atendido_por | bigint unsigned | SÍ | FK → users.id, nullOnDelete | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

Claves adicionales: índices sobre `caja_id` y `user_id`. **No existe `pedido_id`.**

#### `venta_items` — equivalente a “detalle_ventas”

Migración: `2026_10_04_000006_create_ventas_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| venta_id | bigint unsigned | NO | FK → ventas.id, cascadeOnDelete | — |
| producto_id | bigint unsigned | NO | FK → productos.id, cascadeOnDelete | — |
| cantidad | int | NO | — | — |
| precio_unitario | decimal(10,2) | NO | — | — |
| subtotal | decimal(10,2) | NO | — | — |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `cajas`

Migración: `2026_10_04_000004_create_cajas_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| user_id | bigint unsigned | NO | FK → users.id, cascadeOnDelete | — |
| fecha | date | NO | — | — |
| monto_inicial | decimal(10,2) | NO | — | 0 |
| estado | enum('abierta','cerrada') | NO | — | 'abierta' |
| apertura_at | timestamp | SÍ | — | NULL |
| cierre_at | timestamp | SÍ | — | NULL |
| total_ventas | decimal(10,2) | SÍ | — | NULL |
| total_cobros | decimal(10,2) | SÍ | — | NULL |
| total_entradas | decimal(10,2) | SÍ | — | NULL |
| total_salidas | decimal(10,2) | SÍ | — | NULL |
| total_esperado | decimal(10,2) | SÍ | — | NULL |
| total_declarado | decimal(10,2) | SÍ | — | NULL |
| diferencia | decimal(10,2) | SÍ | — | NULL |
| observacion_apertura | text | SÍ | — | NULL |
| observacion_cierre | text | SÍ | — | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

Claves adicionales: índices `(user_id, fecha)` y `(user_id, estado)`.

#### `caja_movimientos` — tabla de apoyo de caja

Migración: `2026_10_04_000004_create_cajas_table.php`.

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| caja_id | bigint unsigned | NO | FK → cajas.id, cascadeOnDelete | — |
| tipo | enum('entrada','salida') | NO | — | — |
| monto | decimal(10,2) | NO | — | — |
| concepto | varchar(150) | NO | — | — |
| user_id | bigint unsigned | NO | FK → users.id, cascadeOnDelete | — |
| created_at, updated_at | timestamp | SÍ | — | NULL |

Clave adicional: índice `(caja_id, tipo)`.

#### `horarios` y `contenidos` — reemplazan a “configuracion”

Migración: `2026_10_04_000010_create_horarios_y_contenidos_table.php`.

**`horarios`**

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| dia | tinyint unsigned | NO | UQ | — |
| apertura | time | NO | — | — |
| cierre | time | NO | — | — |
| activo | tinyint(1) | NO | — | 1 |
| created_at, updated_at | timestamp | SÍ | — | NULL |

**`contenidos`**

| Columna | Tipo | Nulo | Clave | Default |
|---|---|---:|---|---|
| id | bigint unsigned | NO | PK, autoincremental | — |
| clave | varchar(60) | NO | UQ | — |
| valor | text | SÍ | — | NULL |
| created_at, updated_at | timestamp | SÍ | — | NULL |

#### `configuracion`

**NO ENCONTRADO.** No hay migración que cree una tabla `configuracion`. La configuración de horarios está en `horarios` y los datos de contacto en `contenidos`.

### 6.b Valores exactos de ENUM y estados

| Tabla.campo | Tipo | Valores exactos | Default |
|---|---|---|---|
| users.role | enum | `administrador`, `cajero`, `cocina`, `cliente` | `cliente` |
| categorias.estado | varchar(255), no enum | `activo`, `inactivo` | `activo` |
| productos.activo | boolean | `0`, `1` | `1` |
| productos.destacado | boolean | `0`, `1` | `0` |
| promociones.activo | boolean | `0`, `1` | `1` |
| pedidos.estado | enum | `pendiente`, `confirmado`, `preparando`, `enviado`, `entregado`, `cancelado` | `pendiente` |
| pedidos.metodo_pago | enum nullable | `efectivo`, `qr`, `tarjeta` | `NULL` |
| ventas.estado | enum | `pagada`, `anulada` | `pagada` |
| ventas.metodo_pago | enum | `efectivo`, `qr`, `tarjeta` | `efectivo` |
| cajas.estado | enum | `abierta`, `cerrada` | `abierta` |
| caja_movimientos.tipo | enum | `entrada`, `salida` | — |
| stock_jornadas | sin enum de estado | Acciones de código: `cargar`, `reponer`, `merma` | — |

### 6.c Confirmaciones explícitas

- **¿`ventas` tiene `pedido_id`?** **NO.** `ventas` tiene `user_id`, `caja_id`, `estado`, `total`, `metodo_pago`, `monto_recibido`, `cambio`, `notas`, `atendido_por` y timestamps. No hay relación directa con `pedidos`.
- **¿`detalle_ventas` tiene `precio_unitario`?** La tabla **`detalle_ventas` no existe**. El equivalente real es **`venta_items`**, y sí tiene `precio_unitario decimal(10,2)` y `subtotal decimal(10,2)`.
- **¿Cómo se llama la tabla de stock?** Se llama **`stock_jornadas`**. No existe `stock_platos` en las migraciones. La antigua columna `productos.stock` fue eliminada y reemplazada por el modelo `StockJornada`.

---

## 7. Fechas de desarrollo según Git

El repositorio existe y tiene **7 commits**. Salida de `git log --reverse --date=short --format="%ad | %s"`:

```text
2026-10-06 | Primer commit limpio para proyecto personal
2026-10-16 | coreccion del http
2026-10-16 | coreccion del http
2026-10-17 | correciones de las imagenes
2026-10-17 | correciones de las imagenes con la base de datos
2026-10-17 | correciones del responsive
2026-10-23 | eliminacion de lineas inecesarias
```

Resumen por módulo, consultando el historial de sus archivos:

| Módulo | Primer commit | Último commit | Observación |
|---|---|---|---|
| Catálogo y autenticación | 2026-10-06 | 2026-10-23 | También hubo cambios el 2026-10-17: imágenes, catálogo, `ProductoController`, `PublicController` y `login-admin`. |
| Stock | 2026-10-06 | 2026-10-23 | Cambios intermedios el 2026-10-17 en `resources/views/stock/index.blade.php`. |
| Pedidos y delivery | 2026-10-06 | 2026-10-23 | Cambios intermedios el 2026-10-17 en vistas de cliente/carrito. |
| Caja y reportes | 2026-10-06 | 2026-10-23 | Sin commits intermedios específicos entre esas fechas. |

> **Advertencia:** el primer commit ya contiene todos los módulos, controladores, modelos, migraciones y vistas. Git no permite demostrar un desarrollo incremental real por módulo; las fechas solo reflejan la importación inicial y los ajustes posteriores. El commit “Primer commit limpio para proyecto personal” es del **2026-10-06**, y el último es del **2026-10-23**.

---

## Lista de discrepancias detectadas

1. **`platos` → `productos`.** El proyecto no tiene tabla ni modelo `Plato`; usa `productos` y `Producto`.
2. **`detalle_pedidos` → `pedido_items`.** No existe `detalle_pedidos`.
3. **`detalle_ventas` → `venta_items`.** No existe `detalle_ventas`, pero `venta_items` sí contiene `precio_unitario` y `subtotal`.
4. **`stock_platos` → `stock_jornadas`.** El stock se controla por jornada diaria y producto en `stock_jornadas`; no existe `stock_platos`.
5. **`configuracion` no existe.** El horario está en la tabla `horarios` y los datos institucionales en `contenidos`.
6. **`ventas` no tiene `pedido_id`.** Las ventas de salón y los pedidos de delivery son entidades separadas; no hay relación directa venta–pedido.
7. **`productos.stock` fue eliminado.** Si la documentación menciona una columna `stock` en `productos`, está desactualizada: el stock ahora es `stock_jornadas.inicial + entradas - mermas - consumidos`.
8. **`pedidos.tipo` fue eliminado.** La migración `2026_10_04_000007` quitó la columna `tipo` (`online`/`local`) y movió las ventas locales a `ventas`.
9. **Roles iniciales distintos.** La primera migración creó `role` como `admin`, `personal`, `cliente`; luego se transformó a `administrador`, `cajero`, `cocina`, `cliente`. Si la documentación dice `admin/personal`, es la versión antigua.
10. **“Platos del día” no existe como tal.** El catálogo público muestra `destacados` y promociones; no hay una consulta o sección denominada “platos del día”.
11. **El registro del pedido no está en `PedidoController`.** `PedidoController` solo lista, muestra y cambia estado. El alta del pedido delivery está en `CarritoController::checkout()`.
12. **`DeliveryController::store()` no persiste un pedido.** La ruta pública `/delivery/enviar` solo devuelve un mensaje y una referencia en sesión; no crea registros en `pedidos`. El pedido real se crea desde el checkout autenticado del cliente.
13. **No hay generación explícita de “nota de venta digital”.** Existen vistas HTML de comprobante (`ventas.show`) y detalle de pedido (`cliente.pedido-detalle`), pero no un método, PDF o vista llamada `nota_venta`. El `README` lo menciona, pero el código no lo implementa como documento aparte.
14. **El estado abierto/cerrado no está en una tabla `configuracion`.** Se calcula con `Horario::estaAbierto()` y `Horario::deHoy()`, compartido por `CompartirContenido`.
15. **El catálogo no usa el horario para bloquear productos.** Bloquea por stock disponible (`Producto::EXPRESION_DISPONIBLE`), no por horario. El horario solo se muestra como indicador “Abierto ahora/Cerrado ahora” en `home.blade.php`.
16. **`categorias.estado` no es ENUM.** Es `varchar(255)` con valores `'activo'`/`'inactivo'`; la migración cambió el antiguo booleano `activo`.
17. **La caja abierta se valida por usuario, no globalmente.** `CajaAperturaRequest` comprueba `user_id` del usuario autenticado; un administrador y un cajero podrían tener cajas abiertas simultáneamente.
18. **El delivery cobrado no crea un movimiento de caja.** Se marca `pedido.caja_id` y `pagado_at`; el arqueo lo suma como `total_cobros`, no como `caja_movimientos`.
19. **Reportes de delivery usan `pagado_at`.** No usan `pedidos.created_at` para las ventas diarias/mensuales; el propio texto de la vista lo indica.
20. **Bootstrap no está en `package.json`.** Se carga por CDN como Bootstrap 5.3.3; el proyecto npm usa Tailwind CSS 4 y Vite 7.
21. **La base no es Oracle MySQL.** El binario de XAMPP reporta **MariaDB 10.4.32**. Si la documentación dice “MySQL 8”, debe corregirse.
22. **No hay método para anular ventas.** `Venta` define `ANULADA`, pero `VentaController` solo tiene `index`, `create`, `store` y `show`; no se encontró un método de anulación.

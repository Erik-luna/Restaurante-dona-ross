# Restaurante Doña Ross — Sistema de Ventas, Stock y Pedidos

Sistema web desarrollado en **Laravel 12 + Blade + Bootstrap 5 + MySQL** para gestionar
el catálogo de platos, los pedidos de delivery, el control de stock, las ventas de
salón y la caja del restaurante.

## Módulos

| # | Módulo | Estado |
|---|---|---|
| 1 | **Catálogo de platos** — listado público con imagen, descripción, categoría, precio y disponibilidad en tiempo real, con sección de destacados. | Implementado |
| 2 | **Pedidos (delivery)** — carrito en sesión, datos de entrega (nombre, teléfono, dirección y referencia) y confirmación que genera la nota de venta. | Implementado |
| 3 | **Información institucional** — indicador abierto/cerrado calculado contra el horario del servidor, datos de contacto y QR de pago. | Implementado |
| 4 | **Caja y ventas diarias** — apertura con monto inicial, registro de ventas de salón, confirmación de cobro de pedidos y cierre con arqueo. | Implementado |
| 5 | **Stock de platos** — porciones disponibles por jornada, con descuento automático al confirmar un pedido o registrar una venta de salón. | Implementado |
| 6 | **CMS** — el administrador edita precios, descripciones, fotos, horarios y contacto sin tocar código. | Implementado |
| 7 | **Reportes** — ventas diarias y mensuales, flujo de caja y platos más vendidos. | Implementado |

## La caja

La caja es la parte del sistema donde un error se nota en el dinero, así que sus
reglas son explícitas:

- **Una caja abierta por persona.** Al empezar el turno el cajero indica con cuánto
  dinero arranca. Tras cerrar su caja con arqueo puede abrir otra el mismo día.
- **Nada se cobra sin caja abierta.** Ni una venta de salón ni un cobro de delivery.
  El dinero siempre entra a una caja concreta, y por eso el arqueo puede sumarlo.
- **Movimientos de efectivo.** Retiros a bóveda, cambios o gastos se registran aparte
  de las ventas; si no, el arqueo compararía el dinero contado contra las ventas y
  mostraría un descuadre falso.
- **El cambio lo calcula el servidor** como `recibido - total`. Nunca se recibe del
  formulario: si se aceptara, bastaría escribir un número alto para que el sistema
  pagara de más.
- **Al cerrar se congela el arqueo.** `esperado = monto inicial + ventas + cobros +
  entradas − salidas`. El dinero contado es el único número que acepta el sistema,
  porque es una observación y no un dato. La diferencia se guarda tal cual: un
  descuadre no se esconde, se documenta.

## El stock

El stock no es un número que baja, es la foto de una jornada. Cada producto tiene
una fila por día en `stock_jornadas` y el disponible nunca se guarda: se deriva, para
que no pueda quedar desfasado de los movimientos que lo producen.

    disponible = inicial + entradas − mermas − consumidos

- **Cada día se carga.** Al dar de alta un producto se indican sus porciones
  iniciales; en los días siguientes, el administrador o la cocina cargan la jornada
  desde `Control de stock`.
- **Las entradas y las mermas son datos, no cálculos.** Reposición y merma se registran
  a mano con su motivo y su responsable. La merma va separada de `consumidos` porque
  un plato que se quemó es una salida de mercadería, no una venta.
- **El consumo es automático y va en la misma transacción que la venta.** El checkout
  de delivery y la venta de salón descuentan su stock antes de guardar la línea; si
  algo falla, se deshace todo. Una venta sin su descuento no puede existir.
- **El descuento ocurre al crear el pedido, no al cambiar su estado.** Es deliberado:
  desde el checkout las porciones ya están comprometidas con ese cliente, así que
  quedan apartadas aunque el pedido se cancele después. Cancelar y reponer es una
  decisión de cocina/administración, no un efecto secundario de cambiar un estado.
- **No se sobrevende.** `StockService` bloquea la fila de la jornada antes de
  descontar, y dos carritos simultáneos sobre el último plato no pueden convertirlo en
  negativo.
- **El agotado sigue en el catálogo**, visible pero sin botón de pedido. Borrarlo de la
  carta sería peor que mostrarlo sin existencias: el cliente ve que está, pero no
  puede pedirlo.
- **La edición de un producto no toca el stock.** Para cambiar existencias se usa el
  control de jornadas, que deja rastro de quién y por qué.

## Los reportes

Un reporte se equivoca de dos formas: que el cálculo esté mal, o que diga una cifra que
cuadra con lo que el día cuadró pero que en realidad mezcla operaciones distintas. Las
reglas que evitan lo segundo son la parte importante:

- **Salón y delivery nunca se suman a ciegas.** Son dinero de dos operaciones
  distintas: una se paga en el mostrador al momento, la otra se cobra después de
  entregar. El reporte los presenta separados y solo entonces muestra la suma.
- **El delivery se cuenta por la fecha de cobro**, no por la del pedido. El dinero entra
  en la caja cuando se cobra, igual que en el arqueo. Contarlo por creación metería en
  el día 30 un pedido tomado el día 1, y ese día ya habría cerrado sin ese importe.
- **Lo anulado no cuenta.** Ventas anuladas y pedidos cancelados quedan fuera: se
  preparó la comida pero nadie pagó.
- **Un pedido entregado sin cobrar todavía no es ingreso.** El stock se consumió, pero
  en caja no entró dinero.
- **El flujo de caja usa las cifras congeladas al cerrar.** Un cobro posterior no puede
  reescribir el arqueo de un turno que ya terminó.
- **El ranking de platos ordena por unidades, no por importe.** Responde qué se
  prepara más. Además usa el precio con el que se vendió cada plato, así que subir un
  precio en enero no reescribe las ventas de diciembre.
- **Los meses son meses naturales**, no ventanas de 30 días rodantes: el 30 y el 1 son
  meses distintos aunque sean días consecutivos.
- **La serie diaria incluye los días sin ventas.** Si el gráfico los saltara, dibujaría
  una pendiente de la semana al lunes que parece caída de ingresos.

## El contenido del sitio

Lo que el administrador cambia sin tocar código. Son dos tablas porque los datos son de
dos formas distintas:

- **`horarios`** tiene estructura: siete filas, una por día, con apertura, cierre y un
  estado. El sistema necesita calcular cosas con ellos, y eso no se puede hacer con
  texto suelto.
- **`contenidos`** es texto con clave: teléfono, WhatsApp, dirección, redes, correo.
  No hay que calcular nada, solo mostrarlo.

Sus reglas:

- **El teléfono se puede escribir como se escribe.** Se acepta "+591 (2) 712-3456" y el
  enlace `tel:` se arma solo con los dígitos, porque un `tel:+591 (2) 712-3456` no
  funciona en ningún navegador. Lo que no se acepta es texto donde va un número.
- **Un día cerrado no se borra, se apaga.** "Hoy no abrimos" y "no abrimos los lunes" son
  cosas distintas, y con la fila borrada no habría forma de volver a abrirlo sin
  reescribir las dos horas a mano.
- **Un cierre posterior a la apertura significa turno de madrugada.** Un local que cierra
  a las 02:00 sigue abierto a la una, y con una comparación simple aparecería siempre
  cerrado.
- **El indicador "Abierto ahora" se calcula, no se escribe.** Antes decía "Abierto ahora"
  en la plantilla a las cuatro de la mañana. Ahora sale de los horarios guardados, y a
  las 23:00 dice "Cerrado ahora".
- **Las redes sin URL no se muestran.** Un icono que lleva a `#` se ve como un enlace
  roto; es mejor que no exista.
- **Una caja cerrada es histórica.** No admite más ventas ni movimientos, y su
  arqueo no se recalcula aunque después se cobre un pedido antiguo: ese cobro entra a
  la caja del día en que se cobra.

## Ventas y pedidos son dos entidades

La venta de salón y el pedido de delivery son la misma operación contable —platos
vendidos, total y quién lo atendió— pero no el mismo negocio:

| | Venta de salón (`ventas`) | Pedido de delivery (`pedidos`) |
|---|---|---|
| Cuándo se paga | En el mostrador, al registrarlo | Después de entregado, desde su ficha |
| Quién lo cobra | El cajero, en el momento | El cajero, en un paso aparte |
| Qué lo prepara | Nadie: se sirve en mesa | La cocina, que ve la cola de pedidos |
| Estado | `pagada` / `anulada` | `pendiente`, `confirmado`, `preparando`, `enviado`, `entregado`, `cancelado` |

Por eso son dos tablas (`ventas` y `pedidos`, con sus líneas `venta_items` y
`pedido_items`) y no una sola con una columna de tipo. Con una tabla compartida había
que acordarse de filtrar por tipo en cada consulta, y olvidarse mezclaba las dos
cosas: la cocina veía ventas ya cobradas, y el arqueo sumaba dinero que no era de
esa caja.

El arqueo mantiene las dos fuentes **por separado** (`total_ventas` y `total_cobros`)
precisamente por eso: si sobró dinero, hay que poder decir si fue por ventas, por
cobros de delivery o por una entrada mal anotada.

## Roles

| Rol | Qué puede hacer |
|---|---|
| `administrador` | Acceso total: catálogo, promociones, stock, pedidos, caja, usuarios y contenidos. |
| `cajero` | Abre su caja, registra las ventas de salón, cobra los pedidos de delivery y cierra el turno con arqueo. |
| `cocina` | Ve los pedidos entrantes, cambia su estado y controla las porciones disponibles. |
| `cliente` | Consulta el catálogo, arma el carrito y confirma pedidos de delivery. |

Cada rol tiene su propio panel, y los permisos se aplican en el grupo de rutas de
cada panel con el middleware `role`:

| Panel | URL | Middleware |
|---|---|---|
| Administrador | `/admin` | `role:administrador` |
| Cajero | `/cajero` | `role:cajero` |
| Cocina | `/cocina` | `role:cocina` |
| Cliente | `/cliente` | `role:cliente` |

Un rol que no está en la lista recibe un 403, así que escribir la URL a mano no
abre una sección ajena.

## Fuera de alcance

Este sistema **no** incluye, por decisión de diseño:

- Facturación fiscal ni integración con el Servicio de Impuestos.
- Pasarelas de pago en línea. Sólo efectivo o QR estático.
- GPS o mapas. La dirección y la referencia se escriben en texto libre.
- API REST para aplicación móvil. Es un sistema web puro.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurar la base de datos en `.env` y luego:

```bash
php artisan migrate --seed
php artisan serve
```

## Usuarios de prueba

| Correo | Contraseña | Rol |
|---|---|---|
| `admin@donaross.com` | `admin123` | administrador |
| `personal@donaross.com` | `personal123` | cajero |
| `cocina@donaross.com` | `cocina123` | cocina |
| `cliente@correo.com` | `cliente123` | cliente |

## Estructura

```
app/
  Http/Controllers/   Controladores web (uno por módulo)
  Http/Requests/      Formularios con sus reglas de validación
  Http/Middleware/    CheckRole (roles) y SecurityHeaders (cabeceras)
  Models/             Categoria, Producto, Pedido, PedidoItem, Venta,
                      VentaItem, Caja, CajaMovimiento, Promocion, User
  Support/            Reglas de validación y utilidades de texto
database/
  migrations/         Estructura de la base de datos
  seeders/            Datos de prueba (4 roles, categorías, platos)
resources/views/
  admin/              Panel del administrador
  cajero/             Panel del cajero
  cocina/             Panel de la cocina
  cliente/            Panel del cliente
  caja/               Apertura, movimientos, arqueo y cierre
  pedidos/ stock/ ventas/   Vistas compartidas por varios paneles
  partials/           Sidebars por rol y alertas
routes/web.php        Todas las rutas del sistema
tests/Feature/        Pruebas de acceso, formularios, caja y ventas de salón
```

## Seguridad en este sistema

- **Contraseñas**: hasheadas con bcrypt (cast `hashed` del modelo `User`).
- **Roles**: middleware `CheckRole` sobre cada grupo de rutas del panel.
- **Validación**: cada formulario tiene su Form Request con sus reglas y mensajes
  en español.
- **SQL**: todo pasa por Eloquent, que usa consultas preparadas.
- **XSS**: las plantillas imprimen con `{{ }}`, que escapa el texto.
- **Fuerza bruta**: los formularios de login permiten 6 intentos por minuto
  (`throttle:6,1`).
- **CSRF**: todos los formularios incluyen `@csrf`.
- **Propiedad de datos**: cada controlador que recibe un `{id}` de la URL
  comprueba que el registro sea del usuario conectado.
- **Cabeceras**: `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy`.

## Pruebas

```bash
php artisan test
```
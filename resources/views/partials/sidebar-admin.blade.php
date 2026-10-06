<nav class="nav flex-column">
    <div class="sidebar-section-title">Principal</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-section-title">Catálogo & Ventas</div>
    <a href="{{ route('admin.productos.index') }}" class="nav-link {{ request()->routeIs('admin.productos.*') ? 'active' : '' }}">
        <i class="bi bi-egg-fried"></i> Productos
    </a>
    <a href="{{ route('admin.pedidos.index') }}" class="nav-link {{ request()->routeIs('admin.pedidos.*') ? 'active' : '' }}">
        <i class="bi bi-cart-check"></i> Pedidos Online
    </a>
    <a href="{{ route('admin.ventas.index') }}" class="nav-link {{ request()->routeIs('admin.ventas.*') ? 'active' : '' }}">
        <i class="bi bi-receipt-cutoff"></i> Ventas de Salón
    </a>
    <a href="{{ route('admin.promociones.index') }}" class="nav-link {{ request()->routeIs('admin.promociones.*') ? 'active' : '' }}">
        <i class="bi bi-tag-fill"></i> Promociones
    </a>
    <a href="{{ route('admin.stock.index') }}" class="nav-link {{ request()->routeIs('admin.stock.*') ? 'active' : '' }}">
        <i class="bi bi-boxes"></i> Control de Stock
    </a>

    <div class="sidebar-section-title">Caja</div>
    <a href="{{ route('admin.caja.index') }}" class="nav-link {{ request()->routeIs('admin.caja.*') ? 'active' : '' }}">
        <i class="bi bi-safe"></i> Cajas y Arqueos
    </a>
    <a href="{{ route('admin.reportes.index') }}" class="nav-link {{ request()->routeIs('admin.reportes.*') ? 'active' : '' }}">
        <i class="bi bi-graph-up-arrow"></i> Reportes
    </a>

    <div class="sidebar-section-title">Usuarios & Config</div>
    <a href="{{ route('admin.usuarios.index') }}" class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i> Personal
    </a>
    <a href="{{ route('admin.clientes.index') }}" class="nav-link {{ request()->routeIs('admin.clientes.*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> Clientes
    </a>
    <a href="{{ route('admin.contenidos.index') }}" class="nav-link {{ request()->routeIs('admin.contenidos.*') ? 'active' : '' }}">
        <i class="bi bi-clock-history"></i> Contenido del Sitio
    </a>
</nav>


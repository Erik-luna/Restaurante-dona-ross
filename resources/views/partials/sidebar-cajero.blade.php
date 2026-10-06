<nav class="nav flex-column">
    <div class="sidebar-section-title">Principal</div>
    <a href="{{ route('cajero.dashboard') }}" class="nav-link {{ request()->routeIs('cajero.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="sidebar-section-title">Salón</div>
    <a href="{{ route('cajero.ventas.index') }}" class="nav-link {{ request()->routeIs('cajero.ventas.*') ? 'active' : '' }}">
        <i class="bi bi-receipt-cutoff"></i> Ventas
    </a>
    <a href="{{ route('cajero.pedidos.index') }}" class="nav-link {{ request()->routeIs('cajero.pedidos.*') ? 'active' : '' }}">
        <i class="bi bi-cart-check"></i> Pedidos Online
    </a>

    <div class="sidebar-section-title">Caja</div>
    <a href="{{ route('cajero.caja.index') }}" class="nav-link {{ request()->routeIs('cajero.caja.*') ? 'active' : '' }}">
        <i class="bi bi-safe"></i> Mi Caja
    </a>
</nav>
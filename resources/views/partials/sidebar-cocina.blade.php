<nav class="nav flex-column">
    <div class="sidebar-section-title">Principal</div>
    <a href="{{ route('cocina.dashboard') }}" class="nav-link {{ request()->routeIs('cocina.dashboard') ? 'active' : '' }}">
        <i class="bi bi-fire"></i> Pedidos de Cocina
    </a>
    <a href="{{ route('cocina.pedidos.index') }}" class="nav-link {{ request()->routeIs('cocina.pedidos.*') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i> Todos los Pedidos
    </a>

    <div class="sidebar-section-title">Almacén</div>
    <a href="{{ route('cocina.stock.index') }}" class="nav-link {{ request()->routeIs('cocina.stock.*') ? 'active' : '' }}">
        <i class="bi bi-boxes"></i> Porciones Disponibles
    </a>
</nav>
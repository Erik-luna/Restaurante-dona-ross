@if(auth()->user()->esAdministrador())
    @include('partials.sidebar-admin')
@elseif(auth()->user()->esCajero())
    @include('partials.sidebar-cajero')
@elseif(auth()->user()->esCocina())
    @include('partials.sidebar-cocina')
@endif
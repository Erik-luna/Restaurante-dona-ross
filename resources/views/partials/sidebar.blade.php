{{--
    Menú lateral del panel.

    Cada rol ve un menú distinto, con las secciones que realmente puede usar.
    Quien entra a una sección que no le corresponde recibe un 403 antes de llegar
    aquí, así que este menú es la primera capa: ni siquiera le muestra el enlace.
--}}
@if(auth()->user()->esAdministrador())
    @include('partials.sidebar-admin')
@elseif(auth()->user()->esCajero())
    @include('partials.sidebar-cajero')
@elseif(auth()->user()->esCocina())
    @include('partials.sidebar-cocina')
@endif
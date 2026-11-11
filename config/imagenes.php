<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Disco de las imágenes del sistema
    |--------------------------------------------------------------------------
    |
    | Disco de archivos donde se guardan las imágenes de platos,
    | promociones y demás contenido (clave de QR, etcétera).
    |
    | Por defecto es el disco "public" de Laravel, que sirve los
    | archivos desde /storage mediante el enlace público. En Railway,
    | donde el sistema de archivos es efímero, se puede definir la
    | variable IMAGENES_DISK y apuntarla a otro disco (por ejemplo
    | uno S3) sin tocar el código.
    |
    */

    'disk' => env('IMAGENES_DISK', 'public'),

];

# Sección niños (agregada sobre la fusión)
Nuevos: resources/views/productos/ninos.blade.php, public/assets/img/fondo-ninos-juguetes.jpg, public/assets/img/fondo-ninos-caritas.jpg
Modificados (solo se AGREGÓ, nada se quitó):
- routes/web.php: ruta productos.ninos (/productos/ninos)
- app/Http/Controllers/ProductoController.php: método ninos()
- resources/views/includes/header.blade.php: enlace "Niños" en el menú + fuente Fredoka
- resources/views/index.blade.php: tarjeta del servicio 1 decorada para niños (sigue leyendo textos/imagen del CMS $pagina)
- public/assets/css/style.css: bloque "MODO NIÑOS" al final
Después de copiar: php artisan optimize:clear

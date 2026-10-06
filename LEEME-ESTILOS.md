# Panel sin depender del CDN de Tailwind

Los paneles del admin y del superadmin ya no cargan `cdn.tailwindcss.com`.
Ahora usan un archivo CSS normal que queda dentro del proyecto, así que se ven
bien aunque el computador o el hosting no tengan acceso a ese CDN.
Alpine.js y Chart.js también quedaron locales.

## Cómo instalarlo

1. Copia el contenido de esta carpeta encima de `visilux-cc`, reemplazando archivos.
2. En la terminal, dentro del proyecto: `php artisan optimize:clear`
3. Recarga el panel con Ctrl + F5.

## Archivos

- public/assets/css/panel.css: los estilos del panel (CSS ya listo, no se edita a mano).
- public/assets/js/: Alpine.js, su plugin collapse y Chart.js.
- resources/views/layouts/admin.blade.php y superadmin.blade.php: ahora enlazan esos archivos.
- resources/css/panel.css y tailwind.panel.config.cjs: solo se usan si hay que regenerar el CSS.

## Si agregas clases nuevas a las vistas del panel

El CSS contiene únicamente las clases que usan las vistas actuales. Si agregas una
clase que no estaba (por ejemplo `bg-purple-500`), regenera el archivo desde la
carpeta del proyecto:

    npx tailwindcss@3 -c tailwind.panel.config.cjs -i resources/css/panel.css -o public/assets/css/panel.css --minify

Esto necesita Node.js e internet solo en ese momento; el sitio no los necesita.

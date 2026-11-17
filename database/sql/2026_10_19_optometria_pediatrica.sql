-- Cambia el texto de la tarjeta infantil del Inicio (serv1) en las páginas YA creadas.
-- Las nuevas se crean con estos mismos textos desde config/visioptica.php.
-- Ejecutar una sola vez en phpMyAdmin (base de datos del proyecto).

UPDATE pagina_empresa
SET serv1_titulo = 'Optometría Pediátrica',
    serv1_items  = 'Examen visual completo\nDetección temprana de problemas visuales\nAsesoría para padres'
WHERE serv1_titulo = 'Optometría Infantil';

-- Nota: el WHERE solo toca las páginas que todavía tienen el título original;
-- si una óptica ya personalizó su texto, no se modifica.

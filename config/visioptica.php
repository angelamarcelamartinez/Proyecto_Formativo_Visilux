<?php

return [

    // Roles de la tabla `rol`
    'rol_admin' => 1,
    'rol_superadmin' => 6,

    // Dirección del login del superadmin (sin "/" al inicio). No aparece en
    // ningún botón ni enlace del sitio: solo la conoce el superadmin. Cámbiala en el .env con SUPERADMIN_LOGIN_PATH.
    'superadmin_login_path' => env('SUPERADMIN_LOGIN_PATH', 'login/superadmin'),

    // Óptica que se muestra en la página principal ("/").
    // Las demás se ven en /optica/{slug}.
    'empresa_principal' => env('VISIOPTICA_EMPRESA_PRINCIPAL', '900000000-1'),

    // Con cuántos días de anticipación se avisa que la licencia va a vencer.
    'dias_aviso' => 30,

    // Texto que ve el admin de la óptica al solicitar un plan.
    'instrucciones_pago' => 'Realiza la transferencia a la cuenta de ahorros Bancolombia 000-000000-00 '
        . 'a nombre de VisiOptica y envía el comprobante a pagos@visioptica.com. '
        . 'Tu plan se activa cuando confirmemos el pago.',

    // Carpeta (dentro de public/) donde se guardan las imágenes de cada página.
    'carpeta_imagenes' => 'assets/img/paginas',

    // Contenido inicial de la página de una óptica nueva.
    // {nombre} se reemplaza por el nombre de la óptica.
    'pagina_por_defecto' => [
        'hero_titulo' => 'Bienvenidos',
        'hero_subtitulo' => 'a tu salud visual',
        'hero_texto' => 'Cuidamos de tus ojos con tecnología avanzada, atención personalizada y años de experiencia.',

        'stat1_valor' => '15+',
        'stat1_texto' => 'Años de experiencia',
        'stat2_valor' => '5000+',
        'stat2_texto' => 'Pacientes felices',
        'stat3_valor' => '100%',
        'stat3_texto' => 'Atención personalizada',

        'servicios_titulo' => 'Nuestros Servicios',
        'servicios_subtitulo' => 'Atención especializada para todas las edades',
        'serv1_titulo' => 'Optometría Infantil',
        'serv1_texto' => 'Evaluaciones adaptadas para niños con un enfoque lúdico y profesional que genera confianza.',
        'serv1_items' => "Examen visual completo\nDetección temprana de problemas\nAsesoría para padres",
        'serv2_titulo' => 'Terapia Visual',
        'serv2_texto' => 'Programas personalizados para mejorar la coordinación ocular, enfoque y rendimiento visual.',
        'serv2_items' => "Ejercicios visuales guiados\nSeguimiento periódico\nEquipos de última generación",

        'prof_nombre' => 'Dra. Catalina Fernández',
        'prof_cargo' => 'Optómetra Especializada',
        'prof_texto' => 'Con más de 15 años de experiencia, combina precisión clínica con un trato cercano.',
        'prof_credenciales' => "Optometría Certificada | Universidad Nacional de Colombia\n"
            . "Terapia Visual Avanzada | Instituto de Salud Visual\n"
            . "Pediatría y Salud Ocular | Colegio Colombiano de Optómetras",

        'porque_titulo' => '¿Por qué elegirnos?',
        'porque_subtitulo' => 'Somos más que una óptica, somos tu aliado en salud visual',
        'ventaja1_titulo' => 'Experiencia Comprobada',
        'ventaja1_texto' => 'Más de 15 años cuidando la visión.',
        'ventaja2_titulo' => 'Tecnología Avanzada',
        'ventaja2_texto' => 'Equipos modernos para diagnósticos precisos.',

        'footer_texto' => 'Somos más que una óptica: acompañamos tu salud visual con tecnología, calidez humana y experiencia comprobada.',
        'horario' => "Lun – Vie: 8:00 – 18:00\nSáb: 9:00 – 14:00\nDom: Cerrado",
    ],

    // Datos de contacto con que arranca la página de la óptica principal
    // (los que ya tenía el sitio). Las demás ópticas arrancan con los datos de su registro.
    'contacto_principal' => [
        'telefono' => '+57 300 123 4567',
        'email' => 'info@visioptica.com',
        'direccion' => 'Bogotá, Colombia',
    ],

    // Imágenes que se usan cuando la óptica todavía no ha subido las suyas.
    'imagenes_por_defecto' => [
        'logo' => 'assets/img/logo-visilux.png',
        'hero_imagen' => 'assets/img/imagen_princi.jpg',
        'serv1_imagen' => 'assets/img/niños.jpg',
        'serv2_imagen' => 'assets/img/adultos.jpg',
        'prof_imagen' => 'assets/img/optometra_1.jpg',
    ],
];

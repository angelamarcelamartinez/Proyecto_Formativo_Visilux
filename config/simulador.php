<?php

/*
 * Simulador visual (MSD Ophthalmics · Vision Simulator) dentro del módulo del optómetra.
 *
 * Los archivos .swf originales viven en resources/simulador/vsmsd (NO en public/): solo los
 * descarga un optómetra con sesión iniciada, a través de la ruta optometra.simulador.archivo.
 * Se reproducen en el navegador con Ruffle (public/assets/ruffle), el emulador de Flash.
 */
return [

    // Carpeta con visionsimulator.swf (menú original) y la subcarpeta swf/ con los módulos.
    'carpeta' => resource_path('simulador/vsmsd'),

    // Archivo de entrada (el menú original que venía dentro de VS-MSD-ES.exe).
    'principal' => 'visionsimulator.swf',

    // Escenas del simulador (botones 1 · 2 · 3 · 4 del menú original).
    'escenas' => [
        1 => 'Conduciendo en la ciudad',
        2 => 'Horizonte de Nueva York',
        3 => 'París y la Torre Eiffel',
        4 => 'Playa',
    ],

    // Condiciones que muestra el simulador.
    //  grupo: dónde aparece en el menú original ("Enfermedades" o "Errores Refractivos").
    //  cie:   prefijos CIE-10 de la tabla `enfermedades` que se relacionan con la condición.
    //  claves: palabras del nombre de la enfermedad que también la relacionan.
    'condiciones' => [
        'amd' => [
            'nombre' => 'Degeneración macular (DMAE)',
            'grupo' => 'Enfermedades',
            'cie' => ['H35.3'],
            'claves' => ['macular por edad', 'dmae', 'degeneración macular', 'degeneracion macular'],
            'explica' => 'Pérdida de la visión central: manchas o zonas borrosas en el centro de lo que mira.',
        ],
        'cat' => [
            'nombre' => 'Cataratas',
            'grupo' => 'Enfermedades',
            'cie' => ['H25', 'H26', 'H28'],
            'claves' => ['catarata'],
            'explica' => 'El cristalino se opaca: la imagen se ve nublada, con menos color y más deslumbramiento.',
        ],
        'glc' => [
            'nombre' => 'Glaucoma',
            'grupo' => 'Enfermedades',
            'cie' => ['H40', 'H42'],
            'claves' => ['glaucoma'],
            'explica' => 'Se pierde la visión periférica poco a poco, sin dolor; al final queda "visión en túnel".',
        ],
        'dme' => [
            'nombre' => 'Edema macular diabético',
            'grupo' => 'Enfermedades',
            'cie' => ['H36', 'E10.3', 'E11.3', 'E14.3'],
            'claves' => ['edema macular', 'retinopatía diabética', 'retinopatia diabetica'],
            'explica' => 'El líquido en la mácula deforma y emborrona la visión central.',
        ],
        'dvh' => [
            'nombre' => 'Hemorragia vítrea diabética',
            'grupo' => 'Enfermedades',
            'cie' => ['H43.1'],
            'claves' => ['hemorragia vítrea', 'hemorragia vitrea'],
            'explica' => 'Manchas oscuras y "telarañas" flotantes que tapan parte de la imagen.',
        ],
        'rd' => [
            'nombre' => 'Desprendimiento de la retina',
            'grupo' => 'Enfermedades',
            'cie' => ['H33'],
            'claves' => ['desprendimiento'],
            'explica' => 'Una sombra o "cortina" que avanza desde un lado del campo visual. Es una urgencia.',
        ],
        'myopia' => [
            'nombre' => 'Miopía',
            'grupo' => 'Errores Refractivos',
            'cie' => ['H52.1'],
            'claves' => ['miopía', 'miopia'],
            'explica' => 'Ve bien de cerca y borroso de lejos.',
        ],
        'hyperopia' => [
            'nombre' => 'Hipermetropía',
            'grupo' => 'Errores Refractivos',
            'cie' => ['H52.0'],
            'claves' => ['hipermetropía', 'hipermetropia'],
            'explica' => 'Le cuesta enfocar de cerca; con el esfuerzo aparecen cansancio y dolor de cabeza.',
        ],
        'astigmatism' => [
            'nombre' => 'Astigmatismo',
            'grupo' => 'Errores Refractivos',
            'cie' => ['H52.2'],
            'claves' => ['astigmatismo'],
            'explica' => 'La córnea irregular deforma la imagen a cualquier distancia.',
        ],
        'presbyopia' => [
            'nombre' => 'Presbicia',
            'grupo' => 'Errores Refractivos',
            'cie' => ['H52.4'],
            'claves' => ['presbicia'],
            'explica' => 'Con la edad se pierde el enfoque de cerca: aleja el texto para poder leer.',
        ],
        'anatomia' => [
            'nombre' => 'Anatomía del ojo',
            'grupo' => 'Anatomía',
            'cie' => [],
            'claves' => [],
            'explica' => 'Recorrido por las partes del ojo para explicar el diagnóstico.',
        ],
    ],
];

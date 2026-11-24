<?php
// API 3: Festivos de Colombia del anio actual (date.nager.at, publica y sin clave)

// La funcion consultarApi($url) esta en consultar_api.php (la comparten estos archivos y Laravel)
require_once __DIR__ . '/consultar_api.php';

$anio  = date("Y");
$datos = consultarApi("https://date.nager.at/api/v3/PublicHolidays/" . $anio . "/CO");

// La API devuelve directamente una lista de festivos
if (is_array($datos) && count($datos) > 0) {
    echo "<h3>Festivos de Colombia " . $anio . " (Datos desde la API)</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='text-align: center;'>";
    echo "<thead>
            <tr style='background-color: #f2f2f2;'>
                <th>#</th>
                <th>Fecha</th>
                <th>Dia</th>
                <th>Nombre en Espanol</th>
                <th>Nombre en Ingles</th>
                <th>Estado</th>
            </tr>
          </thead>";
    echo "<tbody>";

    $dias = ["Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sabado"];
    $hoy  = date("Y-m-d");
    $num  = 1;

    foreach ($datos as $festivo) {

        $detalle = $festivo;
        $fecha   = $detalle['date'] ?? '';
        $local   = $detalle['localName'] ?? '';
        $ingles  = $detalle['name'] ?? '';
        $dia     = $dias[date("w", strtotime($fecha))];
        $estado  = ($fecha < $hoy) ? "Ya paso" : "Pendiente";

        echo "<tr>";
        echo "<td>" . $num . "</td>";
        echo "<td>" . date("d/m/Y", strtotime($fecha)) . "</td>";
        echo "<td>" . $dia . "</td>";
        echo "<td>" . htmlspecialchars($local) . "</td>";
        echo "<td>" . htmlspecialchars($ingles) . "</td>";
        echo "<td>" . $estado . "</td>";
        echo "</tr>";

        $num++;
    }

    echo "</tbody>";
    echo "</table>";
}
else {
    echo "No se recibieron los datos esperados. Verifica que la URL termine en /PublicHolidays/" . $anio . "/CO";
}

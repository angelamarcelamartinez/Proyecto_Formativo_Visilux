<?php
// API 2: Indice UV de Ibague para los proximos 7 dias (open-meteo.com, publica y sin clave)

// La funcion consultarApi($url) esta en consultar_api.php (la comparten estos archivos y Laravel)
require_once __DIR__ . '/consultar_api.php';

$datos = consultarApi("https://api.open-meteo.com/v1/forecast?latitude=4.4389&longitude=-75.2322&timezone=America/Bogota&forecast_days=7&daily=uv_index_max");

// Esta API NO devuelve una lista: trae 'daily' con dos listas paralelas, 'time' (fechas) y 'uv_index_max' (valores)
if (is_array($datos) && isset($datos['daily']['time']) && count($datos['daily']['time']) > 0) {
    echo "<h3>Indice UV de Ibague - Proximos 7 dias (Datos desde la API)</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='text-align: center;'>";
    echo "<thead>
            <tr style='background-color: #f2f2f2;'>
                <th>#</th>
                <th>Fecha</th>
                <th>UV Maximo</th>
                <th>Nivel</th>
                <th>Recomendacion</th>
            </tr>
          </thead>";
    echo "<tbody>";

    foreach ($datos['daily']['time'] as $i => $fecha) {

        $uv = $datos['daily']['uv_index_max'][$i] ?? 0;

        // Clasificacion de la OMS para el indice UV
        if ($uv < 3) {
            $nivel = "Bajo";
            $consejo = "Sin riesgo especial.";
            $color = "#c8e6c9";
        } elseif ($uv < 6) {
            $nivel = "Moderado";
            $consejo = "Usar gafas con filtro UV al aire libre.";
            $color = "#fff9c4";
        } elseif ($uv < 8) {
            $nivel = "Alto";
            $consejo = "Gafas con filtro UV400 y sombrero.";
            $color = "#ffe0b2";
        } elseif ($uv < 11) {
            $nivel = "Muy alto";
            $consejo = "Gafas UV400 y evitar el sol del mediodia.";
            $color = "#ffcdd2";
        } else {
            $nivel = "Extremo";
            $consejo = "Evitar el sol directo, proteccion total.";
            $color = "#e1bee7";
        }

        echo "<tr>";
        echo "<td>" . ($i + 1) . "</td>";
        echo "<td>" . date("d/m/Y", strtotime($fecha)) . "</td>";
        echo "<td>" . $uv . "</td>";
        echo "<td style='background-color: " . $color . ";'>" . $nivel . "</td>";
        echo "<td>" . $consejo . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}
else {
    echo "No se recibieron los datos esperados. Verifica que la URL incluya &daily=uv_index_max";
}

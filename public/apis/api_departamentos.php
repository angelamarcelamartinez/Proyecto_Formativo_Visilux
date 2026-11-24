<?php
// API 1: Departamentos de Colombia (api-colombia.com, publica y sin clave)

// La funcion consultarApi($url) esta en consultar_api.php (la comparten estos archivos y Laravel)
require_once __DIR__ . '/consultar_api.php';

$datos = consultarApi("https://api-colombia.com/api/v1/Department");

// La API devuelve directamente una lista de departamentos
if (is_array($datos) && count($datos) > 0) {
    echo "<h3>Departamentos de Colombia (Datos desde la API)</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='text-align: center;'>";
    echo "<thead>
            <tr style='background-color: #f2f2f2;'>
                <th>ID</th>
                <th>Departamento</th>
                <th>Capital</th>
                <th>Poblacion</th>
                <th>Superficie (km2)</th>
                <th>Indicativo</th>
                <th>Descripcion</th>
            </tr>
          </thead>";
    echo "<tbody>";

    foreach ($datos as $departamento) {

        $detalle    = $departamento;
        $id         = $detalle['id'] ?? 'N/A';
        $nombre     = $detalle['name'] ?? '';
        $capital    = $detalle['cityCapital']['name'] ?? 'Sin dato';
        $poblacion  = $detalle['population'] ?? 0;
        $superficie = $detalle['surface'] ?? 0;
        $indicativo = $detalle['phonePrefix'] ?? '';
        $descrip    = $detalle['description'] ?? '';

        echo "<tr>";
        echo "<td>" . $id . "</td>";
        echo "<td>" . htmlspecialchars($nombre) . "</td>";
        echo "<td>" . htmlspecialchars($capital) . "</td>";
        echo "<td>" . number_format($poblacion, 0, ',', '.') . "</td>";
        echo "<td>" . number_format($superficie, 0, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($indicativo) . "</td>";
        echo "<td>" . htmlspecialchars($descrip) . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
}
else {
    echo "No se recibieron los datos esperados. Verifica que la URL termine en /api/v1/Department";
}

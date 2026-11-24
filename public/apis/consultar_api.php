<?php
// Funcion compartida para consumir APIs externas con cURL.
// La usan los archivos de public/apis/ y tambien Laravel (app/Support/ApisExternas.php),
// asi la logica de conexion esta en un solo lugar.

if (! function_exists('consultarApi')) {

    function consultarApi($url){
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) PHP-cURL");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);

        $respuesta = curl_exec($ch);
        $error = curl_error($ch) ? curl_error($ch) : null;

        curl_close($ch);

        if ($error) {
            return null;
        }

        return json_decode($respuesta, true);
    }

}

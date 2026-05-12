<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["mensaje" => "Método no permitido"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$estado = isset($input['estado']) ? (int)$input['estado'] : null;
$medidores = isset($input['medidores']) ? $input['medidores'] : [];

if (!$estado || !is_array($medidores) || count($medidores) === 0) {
    http_response_code(400);
    echo json_encode(["mensaje" => "Datos inválidos"]);
    exit;
}

// Parámetros de conexión SQL Server
$serverName = "10.4.59.8";
$connectionOptions = [
    "Database" => "master",
    "Uid" => "usrSINAMED",
    "PWD" => "U\$rS1N4M3D2025", 
    "CharacterSet" => "UTF-8"
];

// Conectar
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    echo json_encode(["mensaje" => "Error de conexión a la base de datos"]);
    exit;
}

// Preparar consulta
$chunks = array_chunk($medidores, 1000); // SQL Server soporta hasta 2100 params, pero con margen
$totalActualizados = 0;

foreach ($chunks as $grupo) {
    $placeholders = implode(",", array_fill(0, count($grupo), "?"));
    $query = "UPDATE [kcentinel].[dbo].[TELEPNUEVOMEDIDOR] SET [tlpnIdSigAmi] = ? WHERE [tlpnMedidor] IN ($placeholders)";
    $params = array_merge([$estado], $grupo);

    $stmt = sqlsrv_query($conn, $query, $params);
    if ($stmt === false) {
        echo json_encode(["mensaje" => "Error en la consulta SQL", "detalle" => sqlsrv_errors()]);
        exit;
    }
    $totalActualizados += sqlsrv_rows_affected($stmt);
}

sqlsrv_close($conn);

echo json_encode([
    "exito" => true,
    "mensaje" => "Se actualizaron $totalActualizados medidores"
]);

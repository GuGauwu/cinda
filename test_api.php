// Crea un archivo test_api.php en tu servidor
<?php
$url = 'https://sysweb.unach.mx/Siae/api/Alertas/Obtener';
$response = file_get_contents($url);
echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";
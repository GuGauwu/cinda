<?php
// Configuración de la API
define('API_URL', 'https://sysweb.unach.mx/Siae/api/Alertas');
define('TIMEOUT_CONEXION', 15); // Tiempo máximo en segundos para conexiones
define('MAX_INTENTOS_API', 2); // Intentos máximos para conexión con API
define('LOCAL_ALERTS_FILE', 'alertas.json');

/**
 * Obtiene alertas combinadas (API + locales) con manejo robusto de errores
 * @return array Lista de alertas o array con error
 */
function obtenerAlertas() {
    $alertasAPI = obtenerAlertasAPI();
    $alertasLocales = obtenerAlertasLocales();
    
    // Combinar resultados válidos
    if (isset($alertasAPI['error']) && isset($alertasLocales['error'])) {
        return ['error' => 'No se pudieron cargar alertas (API ni locales)'];
    }
    
    return array_merge(
        isset($alertasAPI['error']) ? [] : $alertasAPI,
        isset($alertasLocales['error']) ? [] : $alertasLocales
    );
}

/**
 * Obtiene alertas desde la API con reintentos y manejo de errores
 */
function obtenerAlertasAPI() {
    $url = API_URL . '/Obtener';
    $intentos = 0;
    $ultimoError = null;
    
    while ($intentos < MAX_INTENTOS_API) {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => TIMEOUT_CONEXION,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception("Error de conexión: $error");
            }
            
            if ($httpCode !== 200) {
                throw new Exception("API respondió con código $httpCode");
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
            }
            
            if (!isset($data['lst']) || !is_array($data['lst'])) {
                throw new Exception("Estructura de datos inesperada");
            }
            
            return $data['lst'];
            
        } catch (Exception $e) {
            $ultimoError = $e->getMessage();
            $intentos++;
            if ($intentos < MAX_INTENTOS_API) {
                sleep(1); // Espera 1 segundo antes de reintentar
            }
        }
    }
    
    error_log("Fallo al obtener alertas de API después de $intentos intentos. Último error: $ultimoError");
    return ['error' => 'No se pudo conectar con el servidor de alertas'];
}

/**
 * Obtiene alertas desde el archivo local con validación
 */
function obtenerAlertasLocales() {
    try {
        if (!file_exists(LOCAL_ALERTS_FILE)) {
            return [];
        }
        
        $contenido = file_get_contents(LOCAL_ALERTS_FILE);
        if ($contenido === false) {
            throw new Exception("No se pudo leer el archivo local");
        }
        
        $data = json_decode($contenido, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON local corrupto: " . json_last_error_msg());
        }
        
        return is_array($data) ? $data : [];
        
    } catch (Exception $e) {
        error_log("Error con alertas locales: " . $e->getMessage());
        return ['error' => 'Error al cargar alertas locales'];
    }
}

/**
 * Gestiona alertas (crear/actualizar) con fallback a almacenamiento local
 */
function gestionarAlerta($datos, $esActualizacion = false) {
    // Validación de campos
    $camposRequeridos = ['Matricula', 'Departamento', 'Semestre', 'Alerta', 'Estatus'];
    foreach ($camposRequeridos as $campo) {
        if (empty($datos[$campo])) {
            return ['error' => "El campo $campo es requerido"];
        }
    }
    
    // Preparar payload
    $payload = [
        'matricula' => strtoupper(trim($datos['Matricula'])),
        'escuela' => $datos['Departamento'],
        'semestre' => $datos['Semestre'],
        'descripcion' => $datos['Alerta'],
        'status' => $datos['Estatus']
    ];
    
    if ($esActualizacion && !empty($datos['Id'])) {
        $payload['id'] = $datos['Id'];
    }
    
    // Intentar con API primero
    $resultadoAPI = gestionarAlertaAPI($payload, $esActualizacion);
    
    if ($resultadoAPI === true) {
        return true;
    }
    
    // Fallback a almacenamiento local
    return guardarAlertaLocal($payload, $esActualizacion);
}

/**
 * Gestiona alertas en la API externa
 */
function gestionarAlertaAPI($payload, $esActualizacion = false) {
    $url = $esActualizacion ? API_URL . '/Actualizar' : API_URL . '/Crear';
    
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $esActualizacion ? 'PUT' : 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => TIMEOUT_CONEXION
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($httpCode === 200 || $httpCode === 201);
        
    } catch (Exception $e) {
        error_log("Error al gestionar alerta en API: " . $e->getMessage());
        return false;
    }
}

/**
 * Guarda alertas localmente con manejo de concurrencia
 */
function guardarAlertaLocal($alerta, $esActualizacion = false) {
    try {
        // Bloquear archivo para evitar condiciones de carrera
        $fp = fopen(LOCAL_ALERTS_FILE, 'c+');
        if (!flock($fp, LOCK_EX)) {
            throw new Exception("No se pudo bloquear el archivo");
        }
        
        $alertas = [];
        if (filesize(LOCAL_ALERTS_FILE) > 0) {
            $contenido = fread($fp, filesize(LOCAL_ALERTS_FILE));
            $alertas = json_decode($contenido, true) ?? [];
        }
        
        // Operación de guardado/actualización
        if ($esActualizacion && isset($alerta['id'])) {
            $encontrado = false;
            foreach ($alertas as &$item) {
                if (($item['id'] ?? null) === $alerta['id']) {
                    $item = array_merge($item, $alerta);
                    $encontrado = true;
                    break;
                }
            }
            if (!$encontrado) {
                throw new Exception("Alerta no encontrada para actualizar");
            }
        } else {
            $alerta['id'] = uniqid('local_', true);
            $alertas[] = $alerta;
        }
        
        // Guardar cambios
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($alertas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
        
    } catch (Exception $e) {
        if (isset($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        error_log("Error al guardar alerta local: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina una alerta (intenta API primero, luego local)
 */
function eliminarAlerta($id) {
    // Si es ID local o falla API, eliminar localmente
    if (str_starts_with($id, 'local_') || !eliminarAlertaAPI($id)) {
        return eliminarAlertaLocal($id);
    }
    return true;
}

/**
 * Intenta eliminar alerta en API
 */
function eliminarAlertaAPI($id) {
    $url = API_URL . '/Eliminar/' . urlencode($id);
    
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_TIMEOUT => TIMEOUT_CONEXION
        ]);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
        
    } catch (Exception $e) {
        error_log("Error al eliminar alerta en API: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina alerta del almacenamiento local
 */
function eliminarAlertaLocal($id) {
    try {
        if (!file_exists(LOCAL_ALERTS_FILE)) {
            return false;
        }
        
        $fp = fopen(LOCAL_ALERTS_FILE, 'c+');
        if (!flock($fp, LOCK_EX)) {
            throw new Exception("No se pudo bloquear el archivo");
        }
        
        $alertas = json_decode(fread($fp, filesize(LOCAL_ALERTS_FILE)), true) ?? [];
        $alertas = array_filter($alertas, fn($a) => ($a['id'] ?? null) !== $id);
        
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode(array_values($alertas), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        flock($fp, LOCK_UN);
        fclose($fp);
        
        return true;
        
    } catch (Exception $e) {
        if (isset($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        error_log("Error al eliminar alerta local: " . $e->getMessage());
        return false;
    }
}
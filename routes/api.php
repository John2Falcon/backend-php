<?php
/**
 * Archivo de enrutamiento de la API REST
 * Maneja las diferentes rutas y endpoints de la aplicación
 */
header("Access-Control-Allow-Origin: *");       // Permitir acceso desde cualquier origen
header("Content-Type: application/json");       // Establecer tipo de respuesta como JSON
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type"); // Cabeceras permitidas

// Manejar la solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); // Respuesta exitosa para la preflight request
    exit();
}

// Importar controladores y crear instancias
require_once '../config/database.php';          // Primero la conexión
require_once '../controllers/FeedController.php';    // Controlador de feeds
require_once '../controllers/NewsController.php';    // Controlador de noticias
require_once '../services/FeedService.php';

$feedService = new FeedService($conn);
// Crear instancias de los controladores
$feedController = new FeedController($conn);
$newsController = new NewsController($conn);


// Enrutamiento de endpoints con manejo de errores
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['feeds'])) {
        // Endpoint: GET /api.php?feeds
        $result = $feedController->getFeeds();
        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['news']) && isset($_GET['q']))) {
        // Endpoint: GET /api.php?searchNews&q={término} //!Quiero buscar noticias
        $result = $newsController->searchNews($_GET['q']);
        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
    }
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['news'])) {
    // Obtener el criterio de orden (por defecto: pub_date)
    $orderBy = isset($_GET['orderBy']) ? $_GET['orderBy'] : 'pub_date';

    // Lista de columnas válidas para evitar SQL Injection
    $allowedColumns = ['pub_date', 'title', 'feed_id'];
    if (!in_array($orderBy, $allowedColumns)) {
        $orderBy = 'pub_date'; // Si el parámetro no es válido, usar el predeterminado
    }

    // Llamar al controlador con el criterio de ordenación
    $result = $newsController->getNews($orderBy);
    echo json_encode([
        "success" => true,
        "data" => $result
    ]);
}

    else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['feeds'])) {
        // Obtener y loggear datos del body
        $rawData = file_get_contents("php://input");
        $data = json_decode($rawData, true);
        // Validar que se hayan proporcionado URL y nombre del feed
        if (!isset($data['url'])) {
            throw new Exception("URL del feed no proporcionada");
        }

        $result = $feedController->addFeed($data);

        if ($result) {
            http_response_code(201); // Created
            echo json_encode([
                "success" => true,
                "data" => "Feed: " . $data['url'] . " agregado correctamente"
            ]);
        } else {
            throw new Exception("Error al agregar el feed");
        }
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['news'])) {
        $result = $feedService->fetchAndSaveNews();
        if ($result) {
            echo json_encode([
                "success"=> true,
                "data"=>"Noticias actualizadas"
            ]);
        }
    }
    else {
        throw new Exception("Endpoint no válido");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>

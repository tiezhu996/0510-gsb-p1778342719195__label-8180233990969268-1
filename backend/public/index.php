<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = getenv('DB_HOST') ?: 'db';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'volunteer_platform';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

function initDatabase($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS volunteers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(100),
            id_card VARCHAR(50),
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS service_hours (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            date DATE NOT NULL,
            hours DECIMAL(4,2) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            check_in DATETIME NOT NULL,
            check_out DATETIME,
            status VARCHAR(20) DEFAULT 'present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS star_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            stars INT NOT NULL CHECK (stars >= 1 AND stars <= 5),
            reason TEXT,
            awarded_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            requirements TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS position_assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            position_id INT,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS evaluations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            evaluated_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS honors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            required_stars INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS honor_awards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            honor_id INT,
            awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS points (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT NOT NULL,
            points INT DEFAULT 0,
            source VARCHAR(50),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS point_exchanges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            points_used INT NOT NULL,
            item_name VARCHAR(100) NOT NULL,
            exchanged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            points_required INT NOT NULL,
            description TEXT,
            available TINYINT(1) DEFAULT 1
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            volunteer_id INT,
            position_id INT,
            schedule_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

initDatabase($pdo);

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$rawInput = file_get_contents('php://stdin');
if (empty($rawInput)) {
    $rawInput = file_get_contents('php://input');
}
$requestBody = json_decode($rawInput, true) ?? $_POST ?? [];

// CORS headers for cross-origin requests from frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

// Handle OPTIONS preflight requests
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getInput($keys, $required = [], $body = null) {
    if ($body === null) {
        global $requestBody;
        $body = $requestBody;
    }
    $data = [];
    foreach ($keys as $key) {
        $data[$key] = $body[$key] ?? ($_GET[$key] ?? null);
    }
    foreach ($required as $key) {
        if (empty($data[$key])) {
            jsonResponse(['error' => "Missing required field: $key", 'debug' => ['data' => $data, 'body' => $body]], 400);
        }
    }
    return $data;
}

if ($uri === '/health') {
    jsonResponse(['status' => 'ok']);
}

if ($uri === '/' || $uri === '/api') {
    jsonResponse([
        'name' => 'Hospital Volunteer Service Platform',
        'version' => '1.0.0',
        'endpoints' => [
            'volunteers' => 'GET/POST /api/volunteers',
            'volunteer' => 'GET/PUT/DELETE /api/volunteers/{id}',
            'service_hours' => 'GET/POST /api/service-hours',
            'attendance' => 'GET/POST /api/attendance',
            'star_ratings' => 'GET/POST /api/star-ratings',
            'positions' => 'GET/POST /api/positions',
            'position_assignments' => 'GET/POST /api/position-assignments',
            'evaluations' => 'GET/POST /api/evaluations',
            'honors' => 'GET/POST /api/honors',
            'honor_awards' => 'GET/POST /api/honor-awards',
            'points' => 'GET/POST /api/points',
            'point_exchanges' => 'GET/POST /api/point-exchanges',
            'rewards' => 'GET/POST /api/rewards',
            'schedules' => 'GET/POST/PUT/DELETE /api/schedules'
        ]
    ]);
}

switch ($uri) {
    case strpos($uri, '/api/volunteers') === 0:
        if ($uri === '/api/volunteers' && $method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM volunteers ORDER BY created_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/volunteers' && $method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = [
                'name' => $input['name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'email' => $input['email'] ?? null,
                'id_card' => $input['id_card'] ?? null
            ];
            if (empty($data['name'])) {
                jsonResponse(['error' => "Missing required field: name"], 400);
            }
            $stmt = $pdo->prepare("INSERT INTO volunteers (name, phone, email, id_card) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['name'], $data['phone'] ?? null, $data['email'] ?? null, $data['id_card'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Volunteer created']);
        }
        if (preg_match('#/api/volunteers/(\d+)$#', $uri, $matches)) {
            $id = $matches[1];
            if ($method === 'GET') {
                $stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ?");
                $stmt->execute([$id]);
                $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$volunteer) jsonResponse(['error' => 'Volunteer not found'], 404);
                jsonResponse(['data' => $volunteer]);
            }
            if ($method === 'PUT') {
                $data = getInput(['name', 'phone', 'email', 'id_card', 'status']);
                $stmt = $pdo->prepare("UPDATE volunteers SET name=?, phone=?, email=?, id_card=?, status=? WHERE id=?");
                $stmt->execute([$data['name'] ?? null, $data['phone'] ?? null, $data['email'] ?? null, $data['id_card'] ?? null, $data['status'] ?? 'active', $id]);
                jsonResponse(['message' => 'Volunteer updated']);
            }
            if ($method === 'DELETE') {
                $stmt = $pdo->prepare("DELETE FROM volunteers WHERE id = ?");
                $stmt->execute([$id]);
                jsonResponse(['message' => 'Volunteer deleted']);
            }
        }
        break;
        
    case strpos($uri, '/api/service-hours') === 0:
        if ($uri === '/api/service-hours' && $method === 'GET') {
            $stmt = $pdo->query("SELECT sh.*, v.name as volunteer_name FROM service_hours sh LEFT JOIN volunteers v ON sh.volunteer_id = v.id ORDER BY sh.date DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/service-hours' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'date', 'hours', 'description'], ['volunteer_id', 'date', 'hours']);
            $stmt = $pdo->prepare("INSERT INTO service_hours (volunteer_id, date, hours, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['date'], $data['hours'], $data['description'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Service hours recorded']);
        }
        break;
        
    case strpos($uri, '/api/attendance') === 0:
        if ($uri === '/api/attendance' && $method === 'GET') {
            $stmt = $pdo->query("SELECT a.*, v.name as volunteer_name FROM attendance a LEFT JOIN volunteers v ON a.volunteer_id = v.id ORDER BY a.check_in DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/attendance' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'check_in', 'check_out', 'status'], ['volunteer_id', 'check_in']);
            $stmt = $pdo->prepare("INSERT INTO attendance (volunteer_id, check_in, check_out, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['check_in'], $data['check_out'] ?? null, $data['status'] ?? 'present']);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Attendance recorded']);
        }
        break;
        
    case strpos($uri, '/api/star-ratings') === 0:
        if ($uri === '/api/star-ratings' && $method === 'GET') {
            $stmt = $pdo->query("SELECT sr.*, v.name as volunteer_name FROM star_ratings sr LEFT JOIN volunteers v ON sr.volunteer_id = v.id ORDER BY sr.created_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/star-ratings' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'stars', 'reason', 'awarded_by'], ['volunteer_id', 'stars']);
            $stmt = $pdo->prepare("INSERT INTO star_ratings (volunteer_id, stars, reason, awarded_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['stars'], $data['reason'] ?? null, $data['awarded_by'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Star rating awarded']);
        }
        break;
        
    case strpos($uri, '/api/positions') === 0:
        if ($uri === '/api/positions' && $method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM positions ORDER BY created_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/positions' && $method === 'POST') {
            $data = getInput(['name', 'description', 'requirements'], ['name']);
            $stmt = $pdo->prepare("INSERT INTO positions (name, description, requirements) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['description'] ?? null, $data['requirements'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Position created']);
        }
        break;
        
    case strpos($uri, '/api/position-assignments') === 0:
        if ($uri === '/api/position-assignments' && $method === 'GET') {
            $stmt = $pdo->query("SELECT pa.*, v.name as volunteer_name, p.name as position_name FROM position_assignments pa LEFT JOIN volunteers v ON pa.volunteer_id = v.id LEFT JOIN positions p ON pa.position_id = p.id ORDER BY pa.assigned_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/position-assignments' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'position_id'], ['volunteer_id', 'position_id']);
            $stmt = $pdo->prepare("INSERT INTO position_assignments (volunteer_id, position_id) VALUES (?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['position_id']]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Position assigned']);
        }
        break;
        
    case strpos($uri, '/api/evaluations') === 0:
        if ($uri === '/api/evaluations' && $method === 'GET') {
            $stmt = $pdo->query("SELECT e.*, v.name as volunteer_name FROM evaluations e LEFT JOIN volunteers v ON e.volunteer_id = v.id ORDER BY e.created_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/evaluations' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'rating', 'comment', 'evaluated_by'], ['volunteer_id', 'rating']);
            $stmt = $pdo->prepare("INSERT INTO evaluations (volunteer_id, rating, comment, evaluated_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['rating'], $data['comment'] ?? null, $data['evaluated_by'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Evaluation recorded']);
        }
        break;
        
    case strpos($uri, '/api/honors') === 0:
        if ($uri === '/api/honors' && $method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM honors ORDER BY required_stars DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/honors' && $method === 'POST') {
            $data = getInput(['name', 'description', 'required_stars'], ['name']);
            $stmt = $pdo->prepare("INSERT INTO honors (name, description, required_stars) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['description'] ?? null, $data['required_stars'] ?? 5]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Honor created']);
        }
        break;
        
    case strpos($uri, '/api/honor-awards') === 0:
        if ($uri === '/api/honor-awards' && $method === 'GET') {
            $stmt = $pdo->query("SELECT ha.*, v.name as volunteer_name, h.name as honor_name FROM honor_awards ha LEFT JOIN volunteers v ON ha.volunteer_id = v.id LEFT JOIN honors h ON ha.honor_id = h.id ORDER BY ha.awarded_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/honor-awards' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'honor_id'], ['volunteer_id', 'honor_id']);
            $stmt = $pdo->prepare("INSERT INTO honor_awards (volunteer_id, honor_id) VALUES (?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['honor_id']]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Honor awarded']);
        }
        break;
        
    case strpos($uri, '/api/points') === 0:
        if ($uri === '/api/points' && $method === 'GET') {
            $stmt = $pdo->query("SELECT p.*, v.name as volunteer_name FROM points p LEFT JOIN volunteers v ON p.volunteer_id = v.id ORDER BY p.created_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/points' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'points', 'source', 'description'], ['volunteer_id', 'points']);
            $stmt = $pdo->prepare("INSERT INTO points (volunteer_id, points, source, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['points'], $data['source'] ?? 'manual', $data['description'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Points recorded']);
        }
        break;
        
    case strpos($uri, '/api/point-exchanges') === 0:
        if ($uri === '/api/point-exchanges' && $method === 'GET') {
            $stmt = $pdo->query("SELECT pe.*, v.name as volunteer_name FROM point_exchanges pe LEFT JOIN volunteers v ON pe.volunteer_id = v.id ORDER BY pe.exchanged_at DESC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/point-exchanges' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'points_used', 'item_name'], ['volunteer_id', 'points_used', 'item_name']);
            $stmt = $pdo->prepare("INSERT INTO point_exchanges (volunteer_id, points_used, item_name) VALUES (?, ?, ?)");
            $stmt->execute([$data['volunteer_id'], $data['points_used'], $data['item_name']]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Points exchanged']);
        }
        break;
        
    case strpos($uri, '/api/rewards') === 0:
        if ($uri === '/api/rewards' && $method === 'GET') {
            $stmt = $pdo->query("SELECT * FROM rewards WHERE available = 1 ORDER BY points_required ASC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/rewards' && $method === 'POST') {
            $data = getInput(['name', 'points_required', 'description'], ['name', 'points_required']);
            $stmt = $pdo->prepare("INSERT INTO rewards (name, points_required, description) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['points_required'], $data['description'] ?? null]);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Reward created']);
        }
        break;
        
    case strpos($uri, '/api/schedules') === 0:
        if ($uri === '/api/schedules' && $method === 'GET') {
            $stmt = $pdo->query("SELECT s.*, v.name as volunteer_name, p.name as position_name FROM schedules s LEFT JOIN volunteers v ON s.volunteer_id = v.id LEFT JOIN positions p ON s.position_id = p.id ORDER BY s.schedule_date DESC, s.start_time ASC");
            jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }
        if ($uri === '/api/schedules' && $method === 'POST') {
            $data = getInput(['volunteer_id', 'position_id', 'schedule_date', 'start_time', 'end_time', 'status'], ['position_id', 'schedule_date', 'start_time', 'end_time']);
            $stmt = $pdo->prepare("INSERT INTO schedules (volunteer_id, position_id, schedule_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data['volunteer_id'] ?? null, $data['position_id'], $data['schedule_date'], $data['start_time'], $data['end_time'], $data['status'] ?? 'pending']);
            jsonResponse(['id' => $pdo->lastInsertId(), 'message' => 'Schedule created']);
        }
        if (preg_match('#/api/schedules/(\d+)$#', $uri, $matches)) {
            $id = $matches[1];
            if ($method === 'PUT') {
                $data = getInput(['volunteer_id', 'position_id', 'schedule_date', 'start_time', 'end_time', 'status']);
                $stmt = $pdo->prepare("UPDATE schedules SET volunteer_id=?, position_id=?, schedule_date=?, start_time=?, end_time=?, status=? WHERE id=?");
                $stmt->execute([$data['volunteer_id'] ?? null, $data['position_id'], $data['schedule_date'], $data['start_time'], $data['end_time'], $data['status'] ?? 'pending', $id]);
                jsonResponse(['message' => 'Schedule updated']);
            }
            if ($method === 'DELETE') {
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
                $stmt->execute([$id]);
                jsonResponse(['message' => 'Schedule deleted']);
            }
        }
        break;
}

jsonResponse(['error' => 'Not found'], 404);

<?php
require_once '../config/database.php';
require_once '../models/Location.php';

header('Content-Type: application/json');

if (!isset($_GET['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type parameter is required']);
    exit();
}

$location = new Location($conn);
$type = $_GET['type'];

try {
    switch ($type) {
        case 'divisions':
            $data = $location->getAllDivisions();
            break;

        case 'districts':
            if (!isset($_GET['division_id'])) {
                throw new Exception('Division ID is required');
            }
            $data = $location->getDistrictsByDivision($_GET['division_id']);
            break;

        case 'upazilas':
            if (!isset($_GET['district_id'])) {
                throw new Exception('District ID is required');
            }
            $data = $location->getUpazilasByDistrict($_GET['district_id']);
            break;

        default:
            throw new Exception('Invalid type parameter');
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

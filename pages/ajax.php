<?php

// Output Buffer leeren und alle vorherigen Ausgaben verwerfen
if (ob_get_level()) {
    ob_end_clean();
}

// Neue Output Buffer-Sitzung starten
ob_start();

use KLXM\MediaCats\CategoryManager;

// CSRF-Schutz
$csrfToken = rex_csrf_token::factory('media_cats');
$action = rex_request('action', 'string');

// JSON-Header setzen und alle anderen Header zurücksetzen
header_remove();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Debug-Information für Fehlerbehebung
$isAjax = rex_request::isXmlHttpRequest();
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'unknown';

// Lockerere AJAX-Prüfung für Debugging
if (!$isAjax && $requestMethod !== 'POST') {
    http_response_code(400);
    $response = [
        'error' => 'Nur AJAX POST-Requests erlaubt',
        'debug' => [
            'is_ajax' => $isAjax,
            'method' => $requestMethod,
            'headers' => getallheaders()
        ]
    ];
    
    // Output Buffer leeren und JSON ausgeben
    ob_clean();
    echo json_encode($response);
    ob_end_flush();
    exit;
}

$categoryManager = new CategoryManager();
$response = ['success' => false, 'message' => '', 'data' => []];

// Debug-Log hinzufügen
error_log('AJAX Request: ' . $action . ', Parent ID: ' . rex_request('parent_id', 'int', 0));

try {
    switch ($action) {
        case 'load_children':
            $parentId = rex_request('parent_id', 'int', 0);
            $children = $categoryManager->getDirectChildren($parentId);
            $response = [
                'success' => true,
                'data' => $children
            ];
            break;
            
        case 'load_category_path':
            $categoryId = rex_request('category_id', 'int');
            $path = $categoryManager->getCategoryPath($categoryId);
            $response = [
                'success' => true,
                'data' => $path
            ];
            break;
            
        case 'search_categories':
            $searchTerm = rex_request('search', 'string', '');
            if (strlen($searchTerm) >= 2) {
                $results = $categoryManager->searchCategories($searchTerm);
                $response = [
                    'success' => true,
                    'data' => $results
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Mindestens 2 Zeichen für die Suche erforderlich'
                ];
            }
            break;
            
        case 'update_category':
            if (!$csrfToken->isValid()) {
                throw new Exception('CSRF-Token ungültig');
            }
            
            $categoryId = rex_request('category_id', 'int');
            $categoryName = rex_request('category_name', 'string', '');
            $parentId = rex_request('parent_id', 'int', 0);
            
            if (empty($categoryName)) {
                throw new Exception('Kategoriename darf nicht leer sein');
            }
            
            if ($categoryManager->wouldCreateCycle($categoryId, $parentId)) {
                throw new Exception('Zirkuläre Referenz nicht erlaubt');
            }
            
            // Automatisches Backup erstellen
            $backupResult = $categoryManager->createBackup();
            
            $result = $categoryManager->updateCategory($categoryId, [
                'name' => $categoryName,
                'parent_id' => $parentId
            ]);
            
            if ($result['status']) {
                $response = [
                    'success' => true,
                    'message' => 'Kategorie erfolgreich aktualisiert'
                ];
            } else {
                throw new Exception($result['message']);
            }
            break;
            
        case 'get_parent_options':
            $excludeId = rex_request('exclude_id', 'int');
            $selectedId = rex_request('selected_id', 'int', 0);
            $search = rex_request('search', 'string', '');
            
            $options = $categoryManager->getParentOptions($excludeId, $selectedId, $search);
            $response = [
                'success' => true,
                'data' => $options
            ];
            break;
            
        default:
            throw new Exception('Unbekannte Aktion');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Output Buffer leeren und nur JSON ausgeben
ob_clean();
echo json_encode($response);
ob_end_flush();
exit;

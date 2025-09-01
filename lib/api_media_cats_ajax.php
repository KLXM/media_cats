<?php

use KLXM\MediaCats\CategoryManager;

/**
 * AJAX API Endpoint für Media Categories
 * URL: /redaxo/index.php?rex-api-call=media_cats_ajax
 */
class rex_api_media_cats_ajax extends rex_api_function
{
    protected $published = true;

    public function execute()
    {
        // Output Buffer komplett leeren
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // JSON-Header setzen
        rex_response::cleanOutputBuffers();
        rex_response::setHeader('Content-Type', 'application/json; charset=utf-8');
        rex_response::setHeader('Cache-Control', 'no-cache, must-revalidate');
        rex_response::setHeader('Pragma', 'no-cache');
        
        $action = rex_request('action', 'string');
        $response = ['success' => false, 'message' => '', 'data' => []];

        try {
            // CSRF-Token validieren
            $csrfToken = rex_csrf_token::factory('media_cats');
            if (!$csrfToken->isValid()) {
                throw new Exception('CSRF-Token ungültig');
            }

            $categoryManager = new CategoryManager();

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
                    
                case 'update_category':
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
                    
                default:
                    throw new Exception('Unbekannte Aktion: ' . $action);
            }
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        // JSON zurückgeben und Script beenden
        rex_response::sendContent(json_encode($response), 'application/json');
        exit;
    }
}

<?php

use KLXM\MediaCats\CategoryManager;

// CSRF-Schutz
$csrfToken = rex_csrf_token::factory('media_cats');

// Instanz der Kategorie-Verwaltungsklasse
$categoryManager = new CategoryManager();

// Meldungsvariablen
$successMessage = '';
$errorMessage = '';

// Prüfen, ob Backup erstellt werden soll
if (rex_post('create_backup', 'boolean')) {
    $backupResult = $categoryManager->createBackup();
    
    if ($backupResult['status']) {
        $successMessage = rex_i18n::msg('media_cats_backup_success');
    } else {
        $errorMessage = $backupResult['message'];
    }
}

// Verarbeitung des Formulars für eine einzelne Kategorie
if (rex_post('save_category', 'boolean') && $csrfToken->isValid()) {
    // Stelle sicher, dass nur ein Kategorie-ID gesendet wurde
    $categoryId = rex_post('category_id', 'int', 0);
    
    // Zusätzliche Sicherheitsprüfung: Stelle sicher, dass es sich um eine einzelne Kategorie-ID handelt
    if ($categoryId > 0) {
        $categoryName = rex_post('category_name', 'string', '');
        $parentId = rex_post('parent_id', 'int', 0);
        
        if (empty($categoryName)) {
            $errorMessage = rex_i18n::msg('media_cats_no_name_error');
        } elseif ($categoryManager->wouldCreateCycle($categoryId, $parentId)) {
            $errorMessage = rex_i18n::msg('media_cats_cyclic_error');
        } else {
            try {
                // Automatisches Backup vor der Änderung erstellen
                $backupResult = $categoryManager->createBackup();
                if (!$backupResult['status']) {
                    $warningMessage = rex_i18n::msg('media_cats_backup_error') . ' ' . 
                                      rex_i18n::msg('media_cats_continue_anyway');
                }
                
                // Kategorie aktualisieren
                $result = $categoryManager->updateCategory($categoryId, [
                    'name' => $categoryName,
                    'parent_id' => $parentId
                ]);
                
                if ($result['status']) {
                    $successMessage = rex_i18n::msg('media_cats_update_success');
                } else {
                    $errorMessage = $result['message'];
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
            }
        }
    } else {
        $errorMessage = rex_i18n::msg('media_cats_invalid_id');
    }
}

// Kategoriebaum laden - nur einmal!
$categoryTree = $categoryManager->getCategoryTree();
$allCategoriesFlat = $categoryManager->getAllCategoriesFlat();

// Ausgabe der Meldungen
if ($successMessage) {
    echo rex_view::success($successMessage);
}
if ($errorMessage) {
    echo rex_view::error($errorMessage);
}

// Manuelles Backup erstellen
$backup_form = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$backup_form .= '<button class="btn btn-primary" type="submit" name="create_backup" value="1">' . rex_i18n::msg('media_cats_backup_now') . '</button>';
$backup_form .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('media_cats_backups'), false);
$fragment->setVar('body', $backup_form, false);
echo $fragment->parse('core/page/section.php');

// Kategoriebaum als Akkordeon anzeigen - mit optimierter Darstellung
$categoryCount = count($allCategoriesFlat);
$categoriesBody = '';

// Performance-Hinweis bei vielen Kategorien
if ($categoryCount > 100) {
    $categoriesBody .= '<div class="performance-hint">';
    $categoriesBody .= '<strong>Performance-Tipp:</strong> Bei ' . $categoryCount . ' Kategorien empfiehlt es sich, die Suchfunktion zu nutzen. ';
    $categoriesBody .= 'Nur die gerade bearbeitete Kategorie wird vollständig geladen.';
    $categoriesBody .= '</div>';
}

$categoriesBody .= '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
$categoriesBody .= renderCategoryAccordion($categoryTree, $allCategoriesFlat);
$categoriesBody .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('media_cats_categories'), false);
$fragment->setVar('body', $categoriesBody, false);
echo $fragment->parse('core/page/section.php');

/**
 * Rendert das Akkordeon für die Kategoriestruktur (optimiert)
 *
 * @param array $categories Kategorien
 * @param array $allCategoriesFlat Flache Liste aller Kategorien
 * @param int $level Aktuelle Ebene für die Einrückung
 * @return string HTML-Code des Akkordeons
 */
function renderCategoryAccordion(array $categories, array $allCategoriesFlat, int $level = 0): string
{
    $output = '';
    $csrfToken = rex_csrf_token::factory('media_cats');
    
    foreach ($categories as $category) {
        $categoryId = $category['id'];
        $panelId = 'panel-' . $categoryId;
        $headingId = 'heading-' . $categoryId;
        $collapseId = 'collapse-' . $categoryId;
        
        // Einrückungsklasse basierend auf Level
        $levelClass = 'level-' . $level;
        $indentStyle = '';
        if ($level > 0) {
            $indentStyle = 'margin-left: ' . ($level * 20) . 'px;';
        }
        
        // Panel erzeugen
        $output .= '<div class="panel panel-default ' . $levelClass . '" style="' . $indentStyle . '">';
        
        // Panel-Header mit Titel
        $output .= '<div class="panel-heading" role="tab" id="' . $headingId . '">';
        $output .= '<h4 class="panel-title">';
        $output .= '<a role="button" data-toggle="collapse" data-parent="#accordion" href="#' . $collapseId . '" aria-expanded="false" aria-controls="' . $collapseId . '">';
        $output .= rex_escape($category['name']);
        $output .= '</a>';
        $output .= '</h4>';
        $output .= '</div>';
        
        // Panel-Body mit Formular
        $output .= '<div id="' . $collapseId . '" class="panel-collapse collapse" role="tabpanel" aria-labelledby="' . $headingId . '">';
        $output .= '<div class="panel-body">';
        
        // Kategorie-Bearbeitungsformular - jede Kategorie hat ein eigenes Formular
        $output .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';
        $output .= $csrfToken->getHiddenField();
        $output .= '<input type="hidden" name="category_id" value="' . $categoryId . '">';
        
        // Formularfelder
        $output .= '<div class="form-group">';
        $output .= '<label for="category_name_' . $categoryId . '">' . rex_i18n::msg('media_cats_name') . ':</label>';
        $output .= '<input class="form-control" id="category_name_' . $categoryId . '" name="category_name" type="text" value="' . rex_escape($category['name']) . '">';
        $output .= '</div>';
        
        $output .= '<div class="form-group">';
        $output .= '<label for="parent_id_' . $categoryId . '">' . rex_i18n::msg('media_cats_parent_category') . ':</label>';
        $output .= '<select class="form-control selectpicker" id="parent_id_' . $categoryId . '" name="parent_id" data-live-search="true" data-width="100%">';
        $output .= '<option value="0">' . rex_i18n::msg('media_cats_no_parent') . '</option>';
        
        // Alle möglichen Elternkategorien auflisten - optimiert mit flacher Liste  
        $output .= renderCategoryOptionsOptimized($allCategoriesFlat, $categoryId, $category['parent_id']);
        
        $output .= '</select>';
        $output .= '</div>';
        
        // Submit-Button
        $output .= '<button class="btn btn-save" type="submit" name="save_category" value="1">' . rex_i18n::msg('media_cats_save') . '</button>';
        $output .= '</form>';
        
        $output .= '</div>'; // Ende panel-body
        $output .= '</div>'; // Ende panel-collapse
        
        $output .= '</div>'; // Ende panel
        
        // Rekursive Verarbeitung der Kindkategorien
        if (!empty($category['children'])) {
            $output .= renderCategoryAccordion($category['children'], $allCategoriesFlat, $level + 1);
        }
    }
    
    return $output;
}

/**
 * Rendert die Optionen für das Elternkategorie-Dropdown (optimiert)
 *
 * @param array $allCategoriesFlat Flache Liste aller Kategorien
 * @param int $currentId ID der aktuellen Kategorie
 * @param int $selectedId ID der ausgewählten Elternkategorie  
 * @return string HTML-Code der Optionen
 */
function renderCategoryOptionsOptimized(array $allCategoriesFlat, int $currentId, int $selectedId): string
{
    $output = '';
    
    // Baue hierarchische Darstellung aus flacher Liste
    $hierarchyOptions = buildHierarchicalOptions($allCategoriesFlat, $currentId);
    
    foreach ($hierarchyOptions as $option) {
        $selected = ($option['id'] == $selectedId) ? ' selected="selected"' : '';
        $output .= '<option value="' . $option['id'] . '"' . $selected . '>' . $option['display_name'] . '</option>';
    }
    
    return $output;
}

/**
 * Erstellt hierarchische Options-Liste aus flacher Kategorie-Liste
 *
 * @param array $allCategoriesFlat Flache Liste aller Kategorien  
 * @param int $excludeId ID der aktuellen Kategorie (ausschließen)
 * @return array Options mit display_name und id
 */
function buildHierarchicalOptions(array $allCategoriesFlat, int $excludeId): array
{
    $options = [];
    
    // Erstelle eine Map für schnelle Pfad-Auflösung
    $categoryMap = [];
    foreach ($allCategoriesFlat as $category) {
        $categoryMap[$category['id']] = $category;
    }
    
    // Ausgeschlossene IDs sammeln (aktuelle Kategorie und ihre Kinder)
    $excludedIds = [$excludeId];
    $excludedIds = array_merge($excludedIds, findAllChildrenIds($allCategoriesFlat, $excludeId));
    
    foreach ($allCategoriesFlat as $category) {
        // Überspringe ausgeschlossene Kategorien
        if (in_array($category['id'], $excludedIds)) {
            continue;
        }
        
        // Erstelle hierarchischen Namen
        $displayName = buildHierarchicalName($category, $categoryMap);
        
        $options[] = [
            'id' => $category['id'],
            'parent_id' => $category['parent_id'],
            'display_name' => $displayName
        ];
    }
    
    // Sortiere nach Parent-ID und Name
    usort($options, function($a, $b) {
        if ($a['parent_id'] !== $b['parent_id']) {
            return $a['parent_id'] - $b['parent_id'];
        }
        return strcmp($a['display_name'], $b['display_name']);
    });
    
    return $options;
}

/**
 * Erstellt hierarchischen Namen für eine Kategorie
 */
function buildHierarchicalName(array $category, array $categoryMap): string
{
    $names = [$category['name']];
    $currentParentId = $category['parent_id'];
    
    // Durchlaufe Pfad nach oben
    while ($currentParentId > 0 && isset($categoryMap[$currentParentId])) {
        $parentCategory = $categoryMap[$currentParentId];
        array_unshift($names, $parentCategory['name']);
        $currentParentId = $parentCategory['parent_id'];
    }
    
    // Erstelle eingerückten Namen
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', max(0, count($names) - 1));
    return $indent . rex_escape(end($names));
}

/**
 * Findet alle Kinder-IDs einer Kategorie rekursiv
 */
function findAllChildrenIds(array $allCategoriesFlat, int $parentId): array
{
    $childIds = [];
    
    foreach ($allCategoriesFlat as $category) {
        if ($category['parent_id'] === $parentId) {
            $childIds[] = $category['id'];
            // Rekursiv für Enkel-Kategorien
            $childIds = array_merge($childIds, findAllChildrenIds($allCategoriesFlat, $category['id']));
        }
    }
    
    return $childIds;
}

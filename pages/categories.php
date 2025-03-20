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
    $categoryId = rex_post('category_id', 'int', 0);
    $categoryName = rex_post('category_name', 'string', '');
    $parentId = rex_post('parent_id', 'int', 0);
    
    if (empty($categoryName)) {
        $errorMessage = rex_i18n::msg('media_cats_no_name_error');
    } elseif ($categoryManager->wouldCreateCycle($categoryId, $parentId)) {
        $errorMessage = rex_i18n::msg('media_cats_cyclic_error');
    } else {
        try {
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
}

// Kategoriebaum laden
$categoryTree = $categoryManager->getCategoryTree();

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

// Kategoriebaum als Akkordeon anzeigen
$categoriesBody = '<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">';
$categoriesBody .= renderCategoryAccordion($categoryTree, $categoryManager);
$categoriesBody .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('media_cats_categories'), false);
$fragment->setVar('body', $categoriesBody, false);
echo $fragment->parse('core/page/section.php');

/**
 * Rendert das Akkordeon für die Kategoriestruktur
 *
 * @param array $categories Kategorien
 * @param CategoryManager $categoryManager Instanz der Kategorie-Verwaltungsklasse
 * @param int $level Aktuelle Ebene für die Einrückung
 * @return string HTML-Code des Akkordeons
 */
function renderCategoryAccordion(array $categories, CategoryManager $categoryManager, int $level = 0): string
{
    $output = '';
    $allCategories = $categoryManager->getCategoryTree();
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
        
        // Kategorie-Bearbeitungsformular
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
        
        // Alle möglichen Elternkategorien auflisten
        $output .= renderCategoryOptions($allCategories, $categoryId, $category['parent_id']);
        
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
            $output .= renderCategoryAccordion($category['children'], $categoryManager, $level + 1);
        }
    }
    
    return $output;
}

/**
 * Rendert die Optionen für das Elternkategorie-Dropdown
 *
 * @param array $categories Alle Kategorien
 * @param int $currentId ID der aktuellen Kategorie
 * @param int $selectedId ID der ausgewählten Elternkategorie
 * @param int $level Aktuelle Ebene für die Einrückung
 * @return string HTML-Code der Optionen
 */
function renderCategoryOptions(array $categories, int $currentId, int $selectedId, int $level = 0): string
{
    $output = '';
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
    
    foreach ($categories as $category) {
        // Überspringe sich selbst und direkte Kinder
        if ($category['id'] != $currentId) {
            $selected = ($category['id'] == $selectedId) ? ' selected="selected"' : '';
            $dataLevel = ' data-level="' . $level . '"';
            $output .= '<option value="' . $category['id'] . '"' . $selected . $dataLevel . '>' . $indent . rex_escape($category['name']) . '</option>';
            
            // Rekursive Verarbeitung, aber nur wenn es nicht die aktuelle Kategorie ist
            if (!empty($category['children'])) {
                $includeInOptions = true;
                
                // Prüfen ob die aktuelle Kategorie im Pfad der Kategorie liegt (verhindert zyklische Abhängigkeiten)
                $pathIds = array_filter(explode('|', $category['path']));
                if (in_array($currentId, $pathIds)) {
                    $includeInOptions = false;
                }
                
                if ($includeInOptions) {
                    $output .= renderCategoryOptions($category['children'], $currentId, $selectedId, $level + 1);
                }
            }
        }
    }
    
    return $output;
}

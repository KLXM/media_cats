<?php

use KLXM\MediaCats\CategoryManager;

// CSRF-Schutz
$csrfToken = rex_csrf_token::factory('media_cats');

// Instanz der Kategorie-Verwaltungsklasse
$categoryManager = new CategoryManager();

// Meldungsvariablen
$successMessage = '';
$errorMessage = '';
$warningMessage = '';

// Bestätigung über URL-Parameter prüfen
$confirmed = rex_get('confirmed', 'boolean', false);
$addon = rex_addon::get('media_cats');

// Prüfen, ob Backup erstellt werden soll
if (rex_post('create_backup', 'boolean')) {
    $backupResult = $categoryManager->createBackup();
    
    if ($backupResult['status']) {
        $successMessage = rex_i18n::msg('media_cats_backup_success');
    } else {
        $errorMessage = $backupResult['message'];
    }
}

// Bestätigung speichern wenn bestätigt wurde
if (rex_post('confirm_action', 'boolean') && $csrfToken->isValid()) {
    // Automatisches Backup bei Bestätigung
    $backupResult = $categoryManager->createBackup();
    
    if ($backupResult['status']) {
        $successMessage = rex_i18n::msg('media_cats_backup_success');
        // Weiterleitung zur bestätigten URL
        header('Location: ' . rex_url::backendPage('media_cats/categories', ['confirmed' => 1]));
        exit;
    } else {
        $errorMessage = $backupResult['message'];
    }
}

// Bestätigung vom Addon-Config verwenden
$showConfirmation = !$confirmed && !$addon->getConfig('confirmed', false);

// Wenn über URL bestätigt, in Config speichern
if ($confirmed && !$addon->getConfig('confirmed', false)) {
    $addon->setConfig('confirmed', true);
    $showConfirmation = false;
}

// Verarbeitung des Formulars
if (!$showConfirmation && rex_post('save', 'boolean') && $csrfToken->isValid()) {
    $categoryIds = rex_post('category_id', 'array', []);
    $categoryNames = rex_post('category_name', 'array', []);
    $parentIds = rex_post('parent_id', 'array', []);
    
    $hasErrors = false;
    $cycleError = false;
    
    // Überprüfe, ob alle Felder ausgefüllt sind
    foreach ($categoryIds as $id) {
        if (empty($categoryNames[$id])) {
            $hasErrors = true;
            break;
        }
        
        // Prüfe auf zyklische Abhängigkeiten
        if ($categoryManager->wouldCreateCycle($id, (int)$parentIds[$id])) {
            $cycleError = true;
            break;
        }
    }
    
    if ($hasErrors) {
        $errorMessage = rex_i18n::msg('media_cats_no_name_error');
    } elseif ($cycleError) {
        $errorMessage = rex_i18n::msg('media_cats_cyclic_error');
    } else {
        // Transaktion starten
        $db = rex_sql::factory();
        $db->beginTransaction();
        
        try {
            $updateErrors = false;
            
            // Kategorien aktualisieren
            foreach ($categoryIds as $id) {
                $result = $categoryManager->updateCategory($id, [
                    'name' => $categoryNames[$id],
                    'parent_id' => (int)$parentIds[$id]
                ]);
                
                if (!$result['status']) {
                    $updateErrors = true;
                    $errorMessage = $result['message'];
                    break;
                }
            }
            
            if (!$updateErrors) {
                $db->commitTransaction();
                $successMessage = rex_i18n::msg('media_cats_update_success');
            } else {
                $db->rollbackTransaction();
            }
        } catch (Exception $e) {
            $db->rollbackTransaction();
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
if ($warningMessage) {
    echo rex_view::warning($warningMessage);
}

// Bestätigungsdialog anzeigen
if ($showConfirmation) {
    $panel = '<div class="alert alert-warning">';
    $panel .= '<h4>' . rex_i18n::msg('media_cats_warning_title') . '</h4>';
    $panel .= '<p>' . rex_i18n::msg('media_cats_warning_message') . '</p>';
    $panel .= '<form action="' . rex_url::currentBackendPage() . '" method="post">';
    $panel .= '<input type="hidden" name="confirm_action" value="1">';
    $panel .= $csrfToken->getHiddenField();
    $panel .= '<button class="btn btn-warning" type="submit">' . rex_i18n::msg('media_cats_confirm_button') . '</button> ';
    $panel .= '<a class="btn btn-default" href="' . rex_url::backendPage('media_cats') . '">' . rex_i18n::msg('media_cats_cancel_button') . '</a>';
    $panel .= '</form>';
    $panel .= '</div>';
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('media_cats_warning_title'), false);
    $fragment->setVar('body', $panel, false);
    echo $fragment->parse('core/page/section.php');
} else {
    // Manuelles Backup erstellen
    $backup_form = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
    $backup_form .= '<button class="btn btn-primary" type="submit" name="create_backup" value="1">' . rex_i18n::msg('media_cats_backup_now') . '</button>';
    $backup_form .= '</form>';
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('media_cats_backups'), false);
    $fragment->setVar('body', $backup_form, false);
    echo $fragment->parse('core/page/section.php');
    
    // Kategoriebaum anzeigen und bearbeiten
    $categoryForm = createCategoryForm($categoryTree, $categoryManager);
    
    $fragment = new rex_fragment();
    $fragment->setVar('title', rex_i18n::msg('media_cats_categories'), false);
    $fragment->setVar('body', $categoryForm, false);
    echo $fragment->parse('core/page/section.php');
}

/**
 * Erstellt das Formular für die Kategorie-Verwaltung
 *
 * @param array $categoryTree Baumstruktur der Kategorien
 * @param CategoryManager $categoryManager Instanz der Kategorie-Verwaltungsklasse
 * @return string HTML-Code des Formulars
 */
function createCategoryForm(array $categoryTree, CategoryManager $categoryManager): string
{
    $csrfToken = rex_csrf_token::factory('media_cats');
    
    $form = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
    $form .= $csrfToken->getHiddenField();
    
    // Tabellenkopf
    $form .= '<div class="table-responsive">';
    $form .= '<table class="table table-hover">';
    $form .= '<thead>';
    $form .= '<tr>';
    $form .= '<th>' . rex_i18n::msg('media_cats_categories') . '</th>';
    $form .= '<th>' . rex_i18n::msg('media_cats_parent_category') . '</th>';
    $form .= '</tr>';
    $form .= '</thead>';
    $form .= '<tbody>';
    
    // Kategorien rekursiv rendern
    $form .= renderCategoryRows($categoryTree, $categoryManager);
    
    $form .= '</tbody>';
    $form .= '</table>';
    $form .= '</div>';
    
    // Submit-Button
    $form .= '<button class="btn btn-save" type="submit" name="save" value="1">' . rex_i18n::msg('media_cats_save') . '</button>';
    $form .= '</form>';
    
    return $form;
}

/**
 * Rendert die Kategoriezeilen rekursiv
 *
 * @param array $categories Kategorien
 * @param CategoryManager $categoryManager Instanz der Kategorie-Verwaltungsklasse
 * @param int $level Aktuelle Ebene für die Einrückung
 * @return string HTML-Code der Zeilen
 */
function renderCategoryRows(array $categories, CategoryManager $categoryManager, int $level = 0): string
{
    $output = '';
    $allCategories = $categoryManager->getCategoryTree();
    
    foreach ($categories as $category) {
        $paddingLeft = $level * 20; // Einrückung je nach Ebene
        
        $output .= '<tr>';
        
        // Kategoriename
        $output .= '<td style="padding-left: ' . $paddingLeft . 'px">';
        $output .= '<input type="hidden" name="category_id[]" value="' . $category['id'] . '">';
        $output .= '<input class="form-control" type="text" name="category_name[' . $category['id'] . ']" value="' . rex_escape($category['name']) . '">';
        $output .= '</td>';
        
        // Elternkategorie-Dropdown
        $output .= '<td>';
        $output .= '<select class="form-control selectpicker" name="parent_id[' . $category['id'] . ']" data-live-search="true" data-width="100%">';
        $output .= '<option value="0">' . rex_i18n::msg('media_cats_no_parent') . '</option>';
        
        // Alle möglichen Elternkategorien auflisten, außer sich selbst und eigene Kinder
        $output .= renderCategoryOptions($allCategories, $category['id'], $category['parent_id']);
        
        $output .= '</select>';
        $output .= '</td>';
        
        $output .= '</tr>';
        
        // Rekursive Verarbeitung der Kindkategorien
        if (!empty($category['children'])) {
            $output .= renderCategoryRows($category['children'], $categoryManager, $level + 1);
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

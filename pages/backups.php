<?php

use KLXM\MediaCats\CategoryManager;

// CSRF-Schutz
$csrfToken = rex_csrf_token::factory('media_cats_backups');

// Instanz der Kategorie-Verwaltungsklasse
$categoryManager = new CategoryManager();

// Meldungsvariablen
$successMessage = '';
$errorMessage = '';
$warningMessage = '';

// Backup wiederherstellen
if (rex_post('restore', 'boolean') && $csrfToken->isValid()) {
    $filename = rex_post('filename', 'string');
    
    if ($filename) {
        // Sicherheitsabfrage
        if (rex_post('confirm_restore', 'boolean')) {
            $result = $categoryManager->restoreBackup($filename);
            
            if ($result['status']) {
                $successMessage = $result['message'];
            } else {
                $errorMessage = $result['message'];
            }
        } else {
            // Bestätigungsabfrage anzeigen
            $warningMessage = '
                <form action="' . rex_url::currentBackendPage() . '" method="post">
                    <input type="hidden" name="filename" value="' . rex_escape($filename) . '">
                    <input type="hidden" name="restore" value="1">
                    ' . $csrfToken->getHiddenField() . '
                    <p>' . rex_i18n::msg('media_cats_restore_confirm', rex_escape($filename)) . '</p>
                    <button class="btn btn-warning" type="submit" name="confirm_restore" value="1">' . rex_i18n::msg('media_cats_restore_confirm_button') . '</button>
                    <a class="btn btn-abort" href="' . rex_url::currentBackendPage() . '">' . rex_i18n::msg('media_cats_cancel_button') . '</a>
                </form>
            ';
        }
    }
}

// Backup löschen
if (rex_post('delete', 'boolean') && $csrfToken->isValid()) {
    $filename = rex_post('filename', 'string');
    
    if ($filename) {
        // Sicherheitsabfrage
        if (rex_post('confirm_delete', 'boolean')) {
            $result = $categoryManager->deleteBackup($filename);
            
            if ($result['status']) {
                $successMessage = $result['message'];
            } else {
                $errorMessage = $result['message'];
            }
        } else {
            // Bestätigungsabfrage anzeigen
            $warningMessage = '
                <form action="' . rex_url::currentBackendPage() . '" method="post">
                    <input type="hidden" name="filename" value="' . rex_escape($filename) . '">
                    <input type="hidden" name="delete" value="1">
                    ' . $csrfToken->getHiddenField() . '
                    <p>' . rex_i18n::msg('media_cats_delete_confirm', rex_escape($filename)) . '</p>
                    <button class="btn btn-danger" type="submit" name="confirm_delete" value="1">' . rex_i18n::msg('media_cats_delete_confirm_button') . '</button>
                    <a class="btn btn btn-save" href="' . rex_url::currentBackendPage() . '">' . rex_i18n::msg('media_cats_cancel_button') . '</a>
                </form>
            ';
        }
    }
}

// Alle Backups löschen
if (rex_post('delete_all', 'boolean') && $csrfToken->isValid()) {
    // Sicherheitsabfrage
    if (rex_post('confirm_delete_all', 'boolean')) {
        $backups = $categoryManager->getBackups();
        $deleteErrors = false;
        
        foreach ($backups as $backup) {
            $result = $categoryManager->deleteBackup($backup['filename']);
            if (!$result['status']) {
                $deleteErrors = true;
                $errorMessage = $result['message'];
                break;
            }
        }
        
        if (!$deleteErrors) {
            $successMessage = rex_i18n::msg('media_cats_delete_all_backup_success');
        }
    } else {
        // Bestätigungsabfrage anzeigen
        $warningMessage = '
            <form action="' . rex_url::currentBackendPage() . '" method="post">
                <input type="hidden" name="delete_all" value="1">
                ' . $csrfToken->getHiddenField() . '
                <p>' . rex_i18n::msg('media_cats_delete_all_confirm') . '</p>
                <button class="btn btn-danger" type="submit" name="confirm_delete_all" value="1">' . rex_i18n::msg('media_cats_delete_all_confirm_button') . '</button>
                <a class="btn btn-abort" href="' . rex_url::currentBackendPage() . '">' . rex_i18n::msg('media_cats_cancel_button') . '</a>
            </form>
        ';
    }
}

// Manuelles Backup erstellen
if (rex_post('create_backup', 'boolean')) {
    $backupResult = $categoryManager->createBackup();
    
    if ($backupResult['status']) {
        $successMessage = rex_i18n::msg('media_cats_backup_success');
    } else {
        $errorMessage = $backupResult['message'];
    }
}

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

// Manuelles Backup erstellen
$panelBody = '<form action="' . rex_url::currentBackendPage() . '" method="post">';
$panelBody .= '<button class="btn btn-primary" type="submit" name="create_backup" value="1">' . rex_i18n::msg('media_cats_backup_now') . '</button>';
$panelBody .= '</form>';

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('media_cats_backup_now'), false);
$fragment->setVar('body', $panelBody, false);
echo $fragment->parse('core/page/section.php');

// Liste der Backups
$backups = $categoryManager->getBackups();

$panelBody = '';

if (empty($backups)) {
    $panelBody .= '<p>' . rex_i18n::msg('media_cats_no_backups') . '</p>';
} else {
    // Button zum Löschen aller Backups
    $panelBody .= '<form action="' . rex_url::currentBackendPage() . '" method="post" class="mb-3">';
    $panelBody .= $csrfToken->getHiddenField();
    $panelBody .= '<button class="btn btn-danger" type="submit" name="delete_all" value="1">' . rex_i18n::msg('media_cats_delete_all') . '</button>';
    $panelBody .= '</form>';
    
    $panelBody .= '<div class="table-responsive">';
    $panelBody .= '<table class="table table-hover">';
    $panelBody .= '<thead>';
    $panelBody .= '<tr>';
    $panelBody .= '<th>' . rex_i18n::msg('media_cats_create_date') . '</th>';
    $panelBody .= '<th>Dateigröße</th>';
    $panelBody .= '<th>Dateiname</th>';
    $panelBody .= '<th width="200">Aktionen</th>';
    $panelBody .= '</tr>';
    $panelBody .= '</thead>';
    $panelBody .= '<tbody>';
    
    foreach ($backups as $backup) {
        $panelBody .= '<tr>';
        $panelBody .= '<td>' . rex_escape($backup['timestamp']) . '</td>';
        $panelBody .= '<td>' . rex_escape($backup['filesize']) . '</td>';
        $panelBody .= '<td>' . rex_escape($backup['filename']) . '</td>';
        $panelBody .= '<td>';
        
        // Wiederherstellen-Button
        $panelBody .= '<form class="rex-display-inline" action="' . rex_url::currentBackendPage() . '" method="post">';
        $panelBody .= '<input type="hidden" name="filename" value="' . rex_escape($backup['filename']) . '">';
        $panelBody .= '<input type="hidden" name="restore" value="1">';
        $panelBody .= $csrfToken->getHiddenField();
        $panelBody .= '<button class="btn btn-warning btn-xs" type="submit">' . rex_i18n::msg('media_cats_restore') . '</button>';
        $panelBody .= '</form>';
        
        // Löschen-Button
        $panelBody .= '<form class="rex-display-inline" action="' . rex_url::currentBackendPage() . '" method="post">';
        $panelBody .= '<input type="hidden" name="filename" value="' . rex_escape($backup['filename']) . '">';
        $panelBody .= '<input type="hidden" name="delete" value="1">';
        $panelBody .= $csrfToken->getHiddenField();
        $panelBody .= '<button class="btn btn-danger btn-xs" type="submit">' . rex_i18n::msg('media_cats_delete') . '</button>';
        $panelBody .= '</form>';
        
        $panelBody .= '</td>';
        $panelBody .= '</tr>';
    }
    
    $panelBody .= '</tbody>';
    $panelBody .= '</table>';
    $panelBody .= '</div>';
}

$fragment = new rex_fragment();
$fragment->setVar('title', rex_i18n::msg('media_cats_backups'), false);
$fragment->setVar('body', $panelBody, false);
echo $fragment->parse('core/page/section.php');

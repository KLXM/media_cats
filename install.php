<?php


// Sicherstellen, dass das data-Verzeichnis existiert
$dataDir = rex_path::addonData('media_cats');
if (!is_dir($dataDir)) {
    rex_dir::create($dataDir);
}

// Backup-Verzeichnis anlegen
$backupDir = rex_path::addonData('media_cats', 'backups');
if (!is_dir($backupDir)) {
    rex_dir::create($backupDir);
}

// Berechtigungen setzen
@chmod($dataDir, rex::getDirPerm());
@chmod($backupDir, rex::getDirPerm());

// Version in die Datenbank schreiben
$addon = rex_addon::get('media_cats');
$version = $addon->getVersion();
$addon->setConfig('version', $version);

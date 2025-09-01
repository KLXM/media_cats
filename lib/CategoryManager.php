<?php

namespace KLXM\MediaCats;

use rex;
use rex_sql;
use rex_sql_exception;
use rex_media_category;
use rex_media_category_service;
use rex_media_cache;
use rex_path;
use rex_dir;
use rex_file;
use rex_i18n;
use DateTime;
use Exception;
use LogicException;

/**
 * Klasse zur sicheren Verwaltung von Medienpool-Kategorien
 * 
 * @package media_cats
 * @author Thomas Skerbis
 */
class CategoryManager
{
    /** @var string */
    private string $backupPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->backupPath = rex_path::addonData('media_cats', 'backups');
        
        // Sicherstellen, dass das Backup-Verzeichnis existiert
        if (!is_dir($this->backupPath)) {
            rex_dir::create($this->backupPath);
        }
    }

    /**
     * Erstellt ein Backup der Medienpool-Kategorie-Tabellen
     *
     * @return array Ergebnis mit Status und Nachricht
     */
    public function createBackup(): array
    {
        try {
            $timestamp = (new DateTime())->format('Y-m-d_H-i-s');
            $filename = "mediacat_backup_{$timestamp}.json";
            $backupFile = $this->backupPath . '/' . $filename;
            
            // Medienpool-Kategorien abrufen
            $sql = rex_sql::factory();
            $sql->setQuery('SELECT * FROM ' . rex::getTable('media_category'));
            $categories = $sql->getArray();
            
            // Backup-Daten als JSON speichern
            $backupData = [
                'timestamp' => $timestamp,
                'version' => rex::getVersion(),
                'categories' => $categories
            ];
            
            if (rex_file::put($backupFile, json_encode($backupData, JSON_PRETTY_PRINT))) {
                return [
                    'status' => true, 
                    'message' => rex_i18n::msg('media_cats_backup_success'),
                    'filename' => $filename
                ];
            }
            
            return [
                'status' => false, 
                'message' => rex_i18n::msg('media_cats_backup_error')
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Stellt ein Backup wieder her
     *
     * @param string $filename Name der Backup-Datei
     * @return array Ergebnis mit Status und Nachricht
     */
    public function restoreBackup(string $filename): array
    {
        try {
            $backupFile = $this->backupPath . '/' . $filename;
            
            if (!file_exists($backupFile)) {
                return [
                    'status' => false,
                    'message' => "Backup-Datei nicht gefunden: {$filename}"
                ];
            }
            
            $backupData = json_decode(rex_file::get($backupFile), true);
            
            if (!isset($backupData['categories']) || !is_array($backupData['categories'])) {
                return [
                    'status' => false,
                    'message' => "Ungültiges Backup-Format"
                ];
            }
            
            // Keine Transaktionen verwenden für Abwärtskompatibilität
            
            try {
                // Lösche alle bestehenden Kategorien
                $sql = rex_sql::factory();
                $sql->setQuery('DELETE FROM ' . rex::getTable('media_category'));
                
                // Stelle die Kategorien wieder her
                foreach ($backupData['categories'] as $category) {
                    $insertSql = rex_sql::factory();
                    $insertSql->setTable(rex::getTable('media_category'));
                    
                    foreach ($category as $key => $value) {
                        $insertSql->setValue($key, $value);
                    }
                    
                    $insertSql->insert();
                }
                
                // Cache leeren nach Restore
                $this->clearCache();
                
                // Cache leeren - einzelne Kategorien löschen
                $sql->setQuery('SELECT id FROM ' . rex::getTable('media_category'));
                foreach ($sql as $row) {
                    rex_media_cache::deleteCategory((int)$row->getValue('id'));
                }
                
                return [
                    'status' => true,
                    'message' => rex_i18n::msg('media_cats_restore_success')
                ];
                
            } catch (Exception $e) {
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => rex_i18n::msg('media_cats_restore_error') . ': ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gibt eine Liste aller verfügbaren Backups zurück
     *
     * @return array Liste der Backups
     */
    public function getBackups(): array
    {
        $backups = [];
        
        if (is_dir($this->backupPath)) {
            $files = [];
            
            // Alle JSON-Dateien im Backup-Verzeichnis finden
            $handle = opendir($this->backupPath);
            if ($handle) {
                while (($file = readdir($handle)) !== false) {
                    if (substr($file, -5) === '.json') {
                        $files[] = $file;
                    }
                }
                closedir($handle);
            }
            
            foreach ($files as $file) {
                if (preg_match('/^mediacat_backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.json$/', $file, $matches)) {
                    $timestamp = str_replace('_', ' ', $matches[1]);
                    $backups[] = [
                        'filename' => $file,
                        'timestamp' => $timestamp,
                        'filesize' => $this->formatFileSize(filesize($this->backupPath . '/' . $file))
                    ];
                }
            }
            
            // Nach Datum absteigend sortieren
            usort($backups, function($a, $b) {
                return strcmp($b['timestamp'], $a['timestamp']);
            });
        }
        
        return $backups;
    }

    /**
     * Löscht ein Backup
     *
     * @param string $filename Name der Backup-Datei
     * @return array Ergebnis mit Status und Nachricht
     */
    public function deleteBackup(string $filename): array
    {
        $backupFile = $this->backupPath . '/' . $filename;
        
        if (!file_exists($backupFile)) {
            return [
                'status' => false,
                'message' => "Backup-Datei nicht gefunden: {$filename}"
            ];
        }
        
        if (unlink($backupFile)) {
            return [
                'status' => true,
                'message' => rex_i18n::msg('media_cats_delete_backup_success')
            ];
        }
        
        return [
            'status' => false,
            'message' => rex_i18n::msg('media_cats_delete_backup_error')
        ];
    }

    /** @var array|null */
    private ?array $categoryTreeCache = null;
    
    /** @var array|null */  
    private ?array $flatCategoriesCache = null;

    /**
     * Holt den Kategoriebaum aus der Datenbank (optimiert mit Caching)
     *
     * @param int $parentId ID der Elternkategorie  
     * @param int $level Ebene (für die Rekursion)
     * @return array Baumstruktur der Kategorien
     */
    public function getCategoryTree(int $parentId = 0, int $level = 0): array
    {
        // Cache nutzen für bessere Performance
        if ($this->categoryTreeCache !== null && $parentId === 0) {
            return $this->categoryTreeCache;
        }
        
        try {
            // Bei Root-Aufruf: Optimiertes Laden aller Kategorien in einem Query
            if ($parentId === 0 && $this->categoryTreeCache === null) {
                $this->loadAllCategoriesOptimized();
                return $this->categoryTreeCache ?? [];
            }
            
            // Für Unter-Ebenen: Aus Cache filtern
            if ($this->flatCategoriesCache !== null) {
                return $this->buildTreeFromCache($parentId, $level);
            }
            
            // Fallback zur alten Methode falls Cache nicht verfügbar
            return $this->getCategoryTreeLegacy($parentId, $level);
            
        } catch (Exception $e) {
            // Fehlerbehandlung - leeres Array zurückgeben
            return [];
        }
    }
    
    /**
     * Lädt alle Kategorien in einem optimierten SQL-Query
     */
    private function loadAllCategoriesOptimized(): void 
    {
        try {
            $sql = rex_sql::factory();
            $sql->setQuery('
                SELECT id, name, parent_id, path, createuser, updateuser, createdate, updatedate
                FROM ' . rex::getTable('media_category') . '
                ORDER BY parent_id, name
            ');
            
            $allCategories = [];
            foreach ($sql as $row) {
                $allCategories[$row->getValue('id')] = [
                    'id' => (int)$row->getValue('id'),
                    'name' => $row->getValue('name'),
                    'parent_id' => (int)$row->getValue('parent_id'),
                    'path' => $row->getValue('path'),
                    'children' => []
                ];
            }
            
            // Flache Liste für schnelle Suche speichern
            $this->flatCategoriesCache = $allCategories;
            
            // Baum-Struktur aufbauen
            $this->categoryTreeCache = $this->buildTreeFromFlat($allCategories);
            
        } catch (Exception $e) {
            $this->categoryTreeCache = [];
            $this->flatCategoriesCache = [];
        }
    }
    
    /**
     * Baut Baum-Struktur aus flacher Liste auf
     */
    private function buildTreeFromFlat(array $flatCategories): array
    {
        $tree = [];
        
        foreach ($flatCategories as $id => $category) {
            if ($category['parent_id'] === 0) {
                // Root-Kategorie 
                $tree[] = $this->addChildrenToCategory($category, $flatCategories, 0);
            }
        }
        
        return $tree;
    }
    
    /**
     * Fügt Kinder zu Kategorie hinzu (rekursiv)
     */
    private function addChildrenToCategory(array $category, array $flatCategories, int $level): array
    {
        $category['level'] = $level;
        $category['children'] = [];
        
        foreach ($flatCategories as $childCandidate) {
            if ($childCandidate['parent_id'] === $category['id']) {
                $category['children'][] = $this->addChildrenToCategory($childCandidate, $flatCategories, $level + 1);
            }
        }
        
        return $category;
    }
    
    /**
     * Baut Teilbaum aus Cache für bestimmte Parent-ID
     */
    private function buildTreeFromCache(int $parentId, int $level): array
    {
        $result = [];
        
        if ($this->flatCategoriesCache === null) {
            return $result;
        }
        
        foreach ($this->flatCategoriesCache as $category) {
            if ($category['parent_id'] === $parentId) {
                $categoryWithChildren = $category;
                $categoryWithChildren['level'] = $level;
                $categoryWithChildren['children'] = $this->buildTreeFromCache($category['id'], $level + 1);
                $result[] = $categoryWithChildren;
            }
        }
        
        return $result;
    }
    
    /**
     * Legacy-Methode für Rückwärtskompatibilität
     */
    private function getCategoryTreeLegacy(int $parentId = 0, int $level = 0): array
    {
        $categories = [];
        
        try {
            // Wir verwenden die native REDAXO-Funktionalität zum Abrufen der Kategorien
            if ($parentId === 0) {
                $categoryObjects = rex_media_category::getRootCategories();
            } else {
                $parentCategory = rex_media_category::get($parentId);
                if ($parentCategory) {
                    $categoryObjects = $parentCategory->getChildren();
                } else {
                    return [];
                }
            }
            
            foreach ($categoryObjects as $categoryObject) {
                $id = $categoryObject->getId();
                $categories[] = [
                    'id' => $id,
                    'name' => $categoryObject->getName(),
                    'parent_id' => $categoryObject->getParentId(),
                    'path' => $categoryObject->getPath(),
                    'level' => $level,
                    'children' => $this->getCategoryTreeLegacy($id, $level + 1)
                ];
            }
        } catch (Exception $e) {
            // Fehlerbehandlung - leeres Array zurückgeben
        }
        
        return $categories;
    }
    
    /**
     * Holt flache Liste aller Kategorien (optimiert)
     */
    public function getAllCategoriesFlat(): array
    {
        if ($this->flatCategoriesCache === null) {
            $this->loadAllCategoriesOptimized();
        }
        
        return $this->flatCategoriesCache ?? [];
    }
    
    /**
     * Löscht den Cache (nach Updates)
     */
    public function clearCache(): void
    {
        $this->categoryTreeCache = null;
        $this->flatCategoriesCache = null;
    }

    /**
     * Überprüft, ob eine Kategorie-Hierarchie einen Zyklus enthalten würde (optimiert)
     *
     * @param int $categoryId ID der zu überprüfenden Kategorie
     * @param int $newParentId ID der neuen Elternkategorie
     * @return bool True wenn ein Zyklus gefunden wurde, sonst False
     */
    public function wouldCreateCycle(int $categoryId, int $newParentId): bool
    {
        // Direkter Zyklus (Kategorie wäre ihr eigener Elternteil)
        if ($categoryId === $newParentId) {
            return true;
        }
        
        // Wenn kein Elternteil (root), kann kein Zyklus entstehen
        if ($newParentId === 0) {
            return false;
        }
        
        // Optimiert: Nutze Cache wenn verfügbar
        if ($this->flatCategoriesCache !== null) {
            return $this->wouldCreateCycleOptimized($categoryId, $newParentId);
        }
        
        // Fallback zur alten Methode
        return $this->wouldCreateCycleLegacy($categoryId, $newParentId);
    }
    
    /**
     * Optimierte Zyklus-Erkennung mit Cache
     */
    private function wouldCreateCycleOptimized(int $categoryId, int $newParentId): bool
    {
        // Hole alle Kinder-IDs rekursiv aus dem Cache
        $childIds = $this->getAllChildrenIdsFromCache($categoryId);
        
        // Wenn die neue Eltern-ID in den Kinder-IDs vorkommt, würde ein Zyklus entstehen
        return in_array($newParentId, $childIds);
    }
    
    /**
     * Legacy Zyklus-Erkennung
     */
    private function wouldCreateCycleLegacy(int $categoryId, int $newParentId): bool
    {
        // Prüfe, ob die neue Elternkategorie ein Nachkomme der aktuellen Kategorie ist
        $currentCategory = rex_media_category::get($categoryId);
        
        if (!$currentCategory) {
            return false; // Kategorie existiert nicht
        }
        
        // Hole alle Kindkategorien rekursiv
        $childIds = $this->getAllChildrenIds($categoryId);
        
        // Wenn die neue Eltern-ID in den Kinder-IDs vorkommt, würde ein Zyklus entstehen
        return in_array($newParentId, $childIds);
    }
    
    /**
     * Holt rekursiv alle Kinder-IDs einer Kategorie aus Cache
     */
    private function getAllChildrenIdsFromCache(int $categoryId): array
    {
        $childIds = [];
        
        if ($this->flatCategoriesCache === null) {
            return $childIds;
        }
        
        foreach ($this->flatCategoriesCache as $category) {
            if ($category['parent_id'] === $categoryId) {
                $childIds[] = $category['id'];
                // Rekursiv für Enkel-Kategorien
                $childIds = array_merge($childIds, $this->getAllChildrenIdsFromCache($category['id']));
            }
        }
        
        return $childIds;
    }
    
    /**
     * Holt rekursiv alle Kinder-IDs einer Kategorie
     *
     * @param int $categoryId ID der Kategorie
     * @return array Array mit allen Kinder-IDs
     */
    private function getAllChildrenIds(int $categoryId): array
    {
        $childIds = [];
        $category = rex_media_category::get($categoryId);
        
        if (!$category) {
            return $childIds;
        }
        
        $children = $category->getChildren();
        
        foreach ($children as $child) {
            $childId = $child->getId();
            $childIds[] = $childId;
            
            // Rekursiver Aufruf für Kinder der Kinder
            $childIds = array_merge($childIds, $this->getAllChildrenIds($childId));
        }
        
        return $childIds;
    }

    /**
     * Aktualisiert eine Kategorie (mit Performance-Optimierung)
     *
     * @param int $categoryId ID der Kategorie
     * @param array $data Zu aktualisierende Daten (name, parent_id)
     * @return array Ergebnis mit Status und Nachricht
     */
    public function updateCategory(int $categoryId, array $data): array
    {
        // Simulation durchführen, um Zyklus zu erkennen
        if (isset($data['parent_id']) && $this->wouldCreateCycle($categoryId, (int)$data['parent_id'])) {
            return [
                'status' => false,
                'message' => rex_i18n::msg('media_cats_cyclic_error')
            ];
        }
        
        try {
            $category = rex_media_category::get($categoryId);
            
            if (!$category) {
                return [
                    'status' => false,
                    'message' => "Kategorie mit ID {$categoryId} nicht gefunden"
                ];
            }
            
            // Keine Transaktionen verwenden für Abwärtskompatibilität
            try {
                $oldParentId = $category->getParentId();
                $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : $oldParentId;
                
                $sql = rex_sql::factory();
                $sql->setTable(rex::getTable('media_category'));
                $sql->setWhere(['id' => $categoryId]);
                
                // Name setzen
                if (isset($data['name']) && !empty($data['name'])) {
                    $sql->setValue('name', $data['name']);
                }
                
                // Elternkategorie und Pfad aktualisieren
                if (isset($data['parent_id'])) {
                    $sql->setValue('parent_id', $parentId);
                    
                    // Pfad aktualisieren
                    if ($parentId === 0) {
                        $sql->setValue('path', '|');
                    } else {
                        $parent = rex_media_category::get($parentId);
                        if ($parent) {
                            $path = $parent->getPath() . $parent->getId() . '|';
                            $sql->setValue('path', $path);
                        }
                    }
                }
                
                $sql->addGlobalUpdateFields();
                $sql->update();
                
                // Cache leeren nach Änderungen - wichtig für Performance
                $this->clearCache();
                
                // Cache aktualisieren
                rex_media_cache::deleteCategory($categoryId);
                
                // Cache für alte und neue Elternkategorie aktualisieren
                if ($oldParentId !== $parentId) {
                    rex_media_cache::deleteCategoryList($oldParentId);
                    rex_media_cache::deleteCategoryList($parentId);
                }
                
                // Alle untergeordneten Kategorien aktualisieren
                $this->updateChildrenPaths($categoryId);
                
                return [
                    'status' => true,
                    'message' => "Kategorie erfolgreich aktualisiert"
                ];
                
            } catch (Exception $e) {
                // Cache bei Fehlern auch leeren
                $this->clearCache();
                throw $e;
            }
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => "Fehler beim Aktualisieren der Kategorie: " . $e->getMessage()
            ];
        }
    }

    /**
     * Aktualisiert die Pfade aller untergeordneten Kategorien rekursiv
     *
     * @param int $parentId ID der Elternkategorie
     */
    private function updateChildrenPaths(int $parentId): void
    {
        $parent = rex_media_category::get($parentId);
        
        if (!$parent) {
            return;
        }
        
        $parentPath = $parent->getPath() . $parent->getId() . '|';
        
        try {
            // Alle Kindkategorien direkt über SQL abrufen statt über Children-Methode
            $sql = rex_sql::factory();
            $sql->setQuery('
                SELECT id 
                FROM ' . rex::getTable('media_category') . ' 
                WHERE parent_id = :parent_id', 
                ['parent_id' => $parentId]
            );
            
            foreach ($sql as $row) {
                $childId = (int)$row->getValue('id');
                
                // Pfad aktualisieren
                $updateSql = rex_sql::factory();
                $updateSql->setTable(rex::getTable('media_category'));
                $updateSql->setWhere(['id' => $childId]);
                $updateSql->setValue('path', $parentPath);
                $updateSql->update();
                
                // Cache aktualisieren
                rex_media_cache::deleteCategory($childId);
                
                // Rekursiv für Kindkategorien aktualisieren
                $this->updateChildrenPaths($childId);
            }
        } catch (Exception $e) {
            // Fehlerbehandlung
        }
    }

    /**
     * Formatiert eine Dateigröße in lesbare Form
     *
     * @param int $bytes Größe in Bytes
     * @return string Formatierte Größe
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

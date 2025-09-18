<?php

// Temporäre Implementierung der neuen getParentOptions Methode

class TempCategoryManager 
{
    /**
     * Holt Optionen für Elternkategorie-Auswahl mit REDAXO's nativer API
     *
     * @param int $excludeId ID der aktuellen Kategorie (ausschließen)
     * @param int $selectedId ID der ausgewählten Kategorie
     * @param string $search Suchbegriff für Filterung
     * @param int $limit Maximale Anzahl Optionen
     * @return array Array der verfügbaren Elternkategorien
     */
    public function getParentOptionsNew(int $excludeId, int $selectedId = 0, string $search = '', int $limit = 100): array
{
    $options = [];
    
    // Root-Option immer hinzufügen
    $options[] = [
        'id' => 0,
        'name' => '--- Keine Elternkategorie ---',
        'display_name' => '--- Keine Elternkategorie ---',
        'selected' => ($selectedId === 0),
        'level' => 0
    ];
    
    try {
        error_log('getParentOptionsNew using rex_media_category::getAll() - Exclude ID: ' . $excludeId . ', Selected: ' . $selectedId);
        
        // REDAXO's native Media Category API verwenden
        $allCategories = rex_media_category::getAll();
        
        foreach ($allCategories as $category) {
            $categoryId = $category->getId();
            
            // Aktuelle Kategorie ausschließen
            if ($categoryId == $excludeId) {
                continue;
            }
            
            // Suchfilter anwenden
            $categoryName = $category->getName();
            if (!empty($search) && strlen($search) >= 2) {
                if (stripos($categoryName, $search) === false) {
                    continue;
                }
            }
            
            // Hierarchie-Level aus REDAXO's Path berechnen
            $path = $category->getPath();
            $level = 0;
            if ($path && $path !== '|') {
                $pathParts = array_filter(explode('|', $path));
                $level = count($pathParts) - 1;
            }
            
            // Hierarchische Darstellung
            $displayName = $categoryName;
            if ($level > 0) {
                $indent = str_repeat('  ', $level);
                $displayName = $indent . '└ ' . $categoryName;
            }
            
            $options[] = [
                'id' => $categoryId,
                'name' => $categoryName,
                'display_name' => $displayName,
                'selected' => ($selectedId === $categoryId),
                'level' => $level,
                'parent_id' => $category->getParentId()
            ];
            
            // Limit beachten
            if (count($options) > $limit) {
                break;
            }
        }
        
        error_log('rex_media_category::getAll() returned ' . count($options) . ' options');
        
    } catch (Exception $e) {
        error_log('Error in getParentOptionsNew: ' . $e->getMessage());
    }
    
    return $options;
    }
}

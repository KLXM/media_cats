<?php

use KLXM\MediaCats\CategoryManager;

// CSRF-Schutz
$csrfToken = rex_csrf_token::factory('media_cats');

// Instanz der Kategorie-Verwaltungsklasse
$categoryManager = new CategoryManager();

// Meldungsvariablen
$successMessage = '';
$errorMessage = '';

// Kategorien-Statistiken für Info-Bereich
$totalCategories = count($categoryManager->getAllCategoriesFlat());

// Ausgabe der Meldungen
if ($successMessage) {
    echo rex_view::success($successMessage);
}
if ($errorMessage) {
    echo rex_view::error($errorMessage);
}

// Performance-Hinweis und Backup-Bereich
$infoBody = '';
if ($totalCategories > 100) {
    $infoBody .= '<div class="alert alert-info">';
    $infoBody .= '<strong>Performance-Modus aktiv:</strong> Bei ' . $totalCategories . ' Kategorien wird eine optimierte Darstellung verwendet. ';
    $infoBody .= 'Kategorien werden nur bei Bedarf geladen (Lazy Loading).';
    $infoBody .= '</div>';
}

// Manuelles Backup erstellen
$infoBody .= '<div class="row">';
$infoBody .= '<div class="col-md-6">';
$infoBody .= '<h4>Backup erstellen</h4>';
$infoBody .= '<p>Vor größeren Änderungen empfiehlt sich ein Backup:</p>';
$infoBody .= '<button class="btn btn-primary" id="create-backup-btn">Backup jetzt erstellen</button>';
$infoBody .= '</div>';

$infoBody .= '<div class="col-md-6">';
$infoBody .= '<h4>Statistiken</h4>';
$infoBody .= '<ul>';
$infoBody .= '<li>Kategorien gesamt: <strong>' . $totalCategories . '</strong></li>';
$infoBody .= '<li>Modus: <strong>' . ($totalCategories > 100 ? 'Performance (AJAX)' : 'Standard') . '</strong></li>';
$infoBody .= '</ul>';
$infoBody .= '</div>';
$infoBody .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Kategorie-Verwaltung', false);
$fragment->setVar('body', $infoBody, false);
echo $fragment->parse('core/page/section.php');

// Neue Tree-Browser-Darstellung
$treeBody = '';

// Such- und Filter-Bereich
$treeBody .= '<div class="media-cats-controls">';
$treeBody .= '<div class="row">';
$treeBody .= '<div class="col-md-6">';
$treeBody .= '<div class="form-group">';
$treeBody .= '<label for="category-search">Kategorie suchen:</label>';
$treeBody .= '<input type="text" id="category-search" class="form-control" placeholder="Mindestens 2 Zeichen eingeben...">';
$treeBody .= '<div id="search-results" class="search-results" style="display:none;"></div>';
$treeBody .= '</div>';
$treeBody .= '</div>';
$treeBody .= '<div class="col-md-6">';
$treeBody .= '<div class="form-group">';
$treeBody .= '<label>&nbsp;</label><br>';
$treeBody .= '<button class="btn btn-default" id="expand-all-btn">Alle ausklappen</button> ';
$treeBody .= '<button class="btn btn-default" id="collapse-all-btn">Alle einklappen</button>';
$treeBody .= '</div>';
$treeBody .= '</div>';
$treeBody .= '</div>';
$treeBody .= '</div>';

// Tree-Container
$treeBody .= '<div class="media-cats-tree">';
$treeBody .= '<div class="tree-loading" style="display:none;">Lade Kategorien...</div>';
$treeBody .= '<ul id="category-tree" class="category-tree"></ul>';
$treeBody .= '</div>';

// Modal für Kategorie-Bearbeitung
$treeBody .= '
<div class="modal fade" id="edit-category-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Kategorie bearbeiten</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="edit-category-form">
                    ' . $csrfToken->getHiddenField() . '
                    <input type="hidden" id="edit-category-id" name="category_id" value="">
                    
                    <div class="form-group">
                        <label for="edit-category-name">Kategoriename:</label>
                        <input type="text" id="edit-category-name" name="category_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-parent-category">Elternkategorie:</label>
                        <select id="edit-parent-category" name="parent_id" class="form-control">
                            <option value="0">--- Keine Elternkategorie ---</option>
                        </select>
                        <div class="parent-search-container" style="margin-top: 10px;">
                            <input type="text" id="parent-search" class="form-control" placeholder="Elternkategorie suchen...">
                            <div class="parent-search-results" style="display:none;"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Aktueller Pfad:</label>
                        <div id="category-path" class="well well-sm"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="save-category-btn">Speichern</button>
            </div>
        </div>
    </div>
</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', 'Kategorie-Browser', false);
$fragment->setVar('body', $treeBody, false);
echo $fragment->parse('core/page/section.php');

// CSRF Token und AJAX URL für JavaScript bereitstellen
// CSS und JS werden bereits in boot.php geladen
?>
<script type="text/javascript">
$(document).ready(function() {
    // Globale Konfiguration für MediaCatsTreeBrowser
    window.MediaCatsConfig = {
        ajaxUrl: '<?php echo rex_url::currentBackendPage(['page' => 'media_cats/ajax']); ?>',
        csrfToken: '<?php echo $csrfToken->getValue(); ?>'
    };
    
    // Tree Browser initialisieren
    if (typeof MediaCatsTreeBrowser !== 'undefined') {
        var mediaCatsTree = new MediaCatsTreeBrowser();
        mediaCatsTree.init();
    } else {
        console.error('MediaCatsTreeBrowser class not found');
    }
});
</script>

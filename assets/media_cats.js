/**
 * JavaScript für das media_cats AddOn
 */
$(document).ready(function() {
    // Prüfen, ob wir uns auf der Kategorieseite befinden
    var isCategoriesPage = window.location.href.indexOf('page=media_cats/categories') > -1;
    
    // Bootstrap-Kollapse-Events behandeln - nur auf der Kategorieseite
    if (isCategoriesPage) {
        // Akkordeon verwalten
        $('.panel-collapse').on('shown.bs.collapse', function () {
            // Formularelemente beim Öffnen aktivieren
            $(this).find('input, select, textarea, button').prop('disabled', false);
            
            // Selectpicker aktualisieren, falls vorhanden
            $(this).find('.selectpicker').selectpicker('refresh');
        });
        
        // Nur bei Formularen, die abgesendet werden, prüfen
        $('.panel-collapse form').on('submit', function() {
            // Nur die Form im aktiven Panel darf abgesendet werden
            if (!$(this).closest('.panel-collapse').hasClass('in')) {
                return false; // Absenden verhindern, falls Panel geschlossen
            }
            return true;
        });
        
        // Bestätigungsdialog vor dem Speichern
        $('form button[name="save_category"]').on('click', function(e) {
            var categoryName = $(this).closest('form').find('input[name="category_name"]').val();
            var parentId = $(this).closest('form').find('select[name="parent_id"]').val();
            var originalName = $(this).closest('form').find('input[name="category_name"]').data('original');
            var originalParentId = $(this).closest('form').find('select[name="parent_id"]').data('original');
            
            // Bestätigungstext
            var confirmText = 'Möchten Sie die Änderungen an dieser Kategorie speichern? Dies kann Auswirkungen auf die Medienorganisation haben.';
            
            // Prüfen, ob sich etwas geändert hat
            if (categoryName !== originalName || parseInt(parentId) !== parseInt(originalParentId)) {
                if (!confirm(confirmText)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Original-Werte speichern für Änderungserkennung
        $('input[name="category_name"]').each(function() {
            $(this).data('original', $(this).val());
        });
        
        $('select[name="parent_id"]').each(function() {
            $(this).data('original', $(this).val());
        });
    }
    
    // Bestätigungsdialog für Backup-Aktionen - Funktioniert auf allen Seiten
    $('form button[name="delete_all"]').on('click', function(e) {
        if (!confirm('Sind Sie sicher, dass Sie ALLE Backups löschen möchten?')) {
            e.preventDefault();
            return false;
        }
    });
});

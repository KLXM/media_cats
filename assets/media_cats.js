/**
 * JavaScript für das media_cats AddOn
 */
$(document).ready(function() {
    // Bootstrap-Kollapse-Events behandeln
    $('.panel-collapse').on('show.bs.collapse', function () {
        // Formularelemente beim Öffnen aktivieren
        $(this).find('input, select, textarea, button').prop('disabled', false);
    });
    
    $('.panel-collapse').on('hide.bs.collapse', function () {
        // Formularelemente beim Schließen deaktivieren
        $(this).find('input, select, textarea, button').prop('disabled', true);
    });
    
    // Initial alle Formularelemente in geschlossenen Panels deaktivieren
    $('.panel-collapse:not(.in)').find('input, select, textarea, button').prop('disabled', true);
    
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
    
    // Zusätzlich: Bei Formularabsendung alle nicht sichtbaren Formulare deaktivieren
    $('form').on('submit', function() {
        // Sicherstellen, dass nur Formulare in geöffneten Panels gesendet werden
        if (!$(this).closest('.panel-collapse').hasClass('in')) {
            return false; // Absenden verhindern, falls Panel geschlossen
        }
        
        // Für mehr Sicherheit: auch hier alle Elemente in geschlossenen Panels deaktivieren
        $('.panel-collapse:not(.in)').find('input, select, textarea, button').prop('disabled', true);
        
        return true;
    });
});

/**
 * JavaScript für das media_cats AddOn
 */
$(document).ready(function() {
    // Wir verwenden das Standard-Bootstrap 3 Akkordeon, kein eigenes JavaScript nötig
    
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
});

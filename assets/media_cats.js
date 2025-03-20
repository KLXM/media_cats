/**
 * JavaScript für das media_cats AddOn
 * 
 * Bietet zusätzliche Funktionalität wie Bestätigungs-Dialoge
 */
$(document).ready(function() {
    // Bestätigungsdialoge für kritische Aktionen
    $('form button[name="confirm_restore"], form button[name="confirm_delete"]').on('click', function(e) {
        if (!confirm('Sind Sie sicher, dass Sie diese Aktion ausführen möchten?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Speichern-Bestätigung
    $('form button[name="save"]').on('click', function(e) {
        // Prüfen, ob Änderungen vorgenommen wurden
        var hasChanges = false;
        $('input[name^="category_name"], select[name^="parent_id"]').each(function() {
            if ($(this).data('original') !== $(this).val()) {
                hasChanges = true;
                return false; // Schleife abbrechen, wenn Änderung gefunden
            }
        });
        
        if (hasChanges) {
            if (!confirm('Möchten Sie die Änderungen speichern?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Original-Werte speichern für Änderungserkennung
    $('input[name^="category_name"], select[name^="parent_id"]').each(function() {
        $(this).data('original', $(this).val());
    });
});

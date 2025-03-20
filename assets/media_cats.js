/**
 * JavaScript für das media_cats AddOn
 */
$(document).ready(function() {
    // Panel öffnen wenn auf den Header geklickt wird
    $('.panel-heading').on('click', function(e) {
        var collapseId = $(this).find('a').attr('href');
        
        // Alle anderen Panel-Inhalte schließen
        $('.panel-collapse').not(collapseId).removeClass('in');
        
        // Gewähltes Panel umschalten
        $(collapseId).toggleClass('in');
        
        e.preventDefault();
    });
    
    // Bestätigungsdialog vor dem Speichern
    $('form button[name="save_category"]').on('click', function(e) {
        var categoryName = $(this).closest('form').find('input[name="category_name"]').val();
        var parentId = $(this).closest('form').find('select[name="parent_id"]').val();
        var originalName = $(this).closest('form').find('input[name="category_name"]').data('original');
        var originalParentId = $(this).closest('form').find('select[name="parent_id"]').data('original');
        
        // Prüfen, ob sich etwas geändert hat
        if (categoryName !== originalName || parseInt(parentId) !== parseInt(originalParentId)) {
            if (!confirm(rex.media_cats_confirm_save)) {
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
    
    // Wenn Hash in URL vorhanden, entsprechendes Panel öffnen
    var hash = window.location.hash;
    if (hash && hash.match(/^#collapse-\d+$/)) {
        $(hash).addClass('in');
        
        // Zu dem Panel scrollen
        $('html, body').animate({
            scrollTop: $(hash).offset().top - 100
        }, 500);
    }
});

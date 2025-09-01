/**
 * JavaScript für das media_cats AddOn (Performance-optimiert)
 */
$(document).ready(function() {
    // Prüfen, ob wir uns auf der Kategorieseite befinden
    var isCategoriesPage = window.location.href.indexOf('page=media_cats/categories') > -1;
    
    // Bootstrap-Kollapse-Events behandeln - nur auf der Kategorieseite
    if (isCategoriesPage) {
        // Performance: Lazy-Loading für Selectpicker
        $('.panel-collapse').on('shown.bs.collapse', function () {
            var $this = $(this);
            
            // Formularelemente beim Öffnen aktivieren
            $this.find('input, select, textarea, button').prop('disabled', false);
            
            // Selectpicker nur initialisieren wenn nicht bereits getan
            var $selectpicker = $this.find('.selectpicker');
            if ($selectpicker.length && !$selectpicker.hasClass('selectpicker-initialized')) {
                $selectpicker.addClass('selectpicker-initialized');
                $selectpicker.selectpicker('refresh');
            }
        });
        
        // Performance: Event-Delegation für Forms
        $('#accordion').on('submit', '.panel-collapse form', function() {
            // Nur die Form im aktiven Panel darf abgesendet werden
            if (!$(this).closest('.panel-collapse').hasClass('in')) {
                return false; // Absenden verhindern, falls Panel geschlossen
            }
            return true;
        });
        
        // Performance: Event-Delegation für Bestätigungsdialog
        $('#accordion').on('click', 'button[name="save_category"]', function(e) {
            var $form = $(this).closest('form');
            var categoryName = $form.find('input[name="category_name"]').val();
            var parentId = $form.find('select[name="parent_id"]').val();
            var originalName = $form.find('input[name="category_name"]').data('original');
            var originalParentId = $form.find('select[name="parent_id"]').data('original');
            
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
        
        // Original-Werte speichern für Änderungserkennung - Performance optimiert
        setTimeout(function() {
            $('input[name="category_name"]').each(function() {
                $(this).data('original', $(this).val());
            });
            
            $('select[name="parent_id"]').each(function() {
                $(this).data('original', $(this).val());
            });
        }, 100);
        
        // Suchfunktionalität für große Kategorienlisten hinzufügen
        addCategorySearch();
    }
    
    // Bestätigungsdialog für Backup-Aktionen - Funktioniert auf allen Seiten
    $(document).on('click', 'form button[name="delete_all"]', function(e) {
        if (!confirm('Sind Sie sicher, dass Sie ALLE Backups löschen möchten?')) {
            e.preventDefault();
            return false;
        }
    });
});

/**
 * Fügt Suchfunktionalität für Kategorien hinzu
 */
function addCategorySearch() {
    // Prüfe ob viele Kategorien vorhanden sind
    var categoryCount = $('.panel-group .panel').length;
    
    if (categoryCount > 20) {
        // Suchfeld hinzufügen
        var searchHtml = '<div class="form-group media-cats-search" style="margin-bottom: 20px;">' +
            '<label for="category-search">Kategorien durchsuchen:</label>' +
            '<input type="text" id="category-search" class="form-control" placeholder="Kategoriename eingeben...">' +
            '<small class="help-block">Bei vielen Kategorien: Suche nutzen für bessere Performance</small>' +
            '</div>';
        
        $('.panel-group').before(searchHtml);
        
        // Suchfunktion implementieren
        var searchTimeout;
        $('#category-search').on('input', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            // Debounce für Performance
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                filterCategories(searchTerm);
            }, 300);
        });
    }
}

/**
 * Filtert Kategorien basierend auf Suchbegriff  
 */
function filterCategories(searchTerm) {
    var $panels = $('.panel-group .panel');
    var visibleCount = 0;
    
    if (!searchTerm) {
        // Alle anzeigen
        $panels.show();
        visibleCount = $panels.length;
    } else {
        // Filtern
        $panels.each(function() {
            var $panel = $(this);
            var categoryName = $panel.find('.panel-title a').text().toLowerCase();
            
            if (categoryName.indexOf(searchTerm) !== -1) {
                $panel.show();
                visibleCount++;
            } else {
                // Panel schließen falls offen
                $panel.find('.panel-collapse').removeClass('in');
                $panel.hide();
            }
        });
    }
    
    // Status anzeigen
    var statusText = visibleCount + ' von ' + $panels.length + ' Kategorien angezeigt';
    var $status = $('.media-cats-search .search-status');
    if ($status.length === 0) {
        $('.media-cats-search').append('<div class="search-status text-muted">' + statusText + '</div>');
    } else {
        $status.text(statusText);
    }
}

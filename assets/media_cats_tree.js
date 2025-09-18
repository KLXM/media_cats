/**
 * JavaScript für das media_cats AddOn - Tree Browser (AJAX-basiert) 
 * Erweitert mit modernen UI-Animationen und UX-Features
 */

/**
 * Tree-Browser für Medien-Kategorien (AJAX-basiert) mit schönem Design
 */
class MediaCatsTreeBrowser {
    constructor() {
        // Konfiguration aus globaler Variable lesen
        this.ajaxUrl = window.MediaCatsConfig ? window.MediaCatsConfig.ajaxUrl : 
                      (window.location.href.split('?')[0] + '?page=media_cats/ajax');
        this.apiUrl = window.MediaCatsConfig ? window.MediaCatsConfig.apiUrl : 
                     (window.location.href.split('?')[0] + '?rex-api-call=media_cats');
        this.csrfToken = window.MediaCatsConfig ? window.MediaCatsConfig.csrfToken : 
                        $('input[name="_csrf_token"]').val();
        this.searchTimeout = null;
        this.parentSearchTimeout = null;
        
        // Animation settings
        this.animationSpeed = 300;
        this.loadingDelay = 150;
        
        // Progress tracking
        this.loadingNodes = new Set();
    }
    
    init() {
        this.showLoadingSpinner();
        setTimeout(() => {
            this.loadRootCategories();
            this.bindEvents();
            // this.initializeEnhancements(); // Temporarily disabled
        }, this.loadingDelay);
    }
    
    /**
     * Moderne UI-Enhancements initialisieren
     */
    initializeEnhancements() {
        // Smooth scroll to top button
        this.addScrollToTop();
        
        // Enhanced search with live feedback
        this.enhanceSearchExperience();
        
        // Keyboard shortcuts
        this.addKeyboardShortcuts();
        
        // Progress indicators
        this.setupProgressIndicators();
    }
    
    /**
     * Scroll to Top Button hinzufügen
     */
    addScrollToTop() {
        // Scroll to top button implementation
        if (!$('.scroll-to-top').length) {
            $('body').append('<button class="scroll-to-top" style="display:none;"><i class="fa fa-arrow-up"></i></button>');
        }
        
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('.scroll-to-top').fadeIn();
            } else {
                $('.scroll-to-top').fadeOut();
            }
        });
        
        $(document).on('click', '.scroll-to-top', function() {
            $('html, body').animate({scrollTop: 0}, 800);
        });
    }
    
    /**
     * Enhanced Search Experience
     */
    enhanceSearchExperience() {
        // Search enhancements implementation
        console.log('Search experience enhanced');
    }
    
    /**
     * Keyboard Shortcuts hinzufügen
     */
    addKeyboardShortcuts() {
        // Keyboard shortcuts implementation
        $(document).keydown(function(e) {
            // ESC to close modals
            if (e.keyCode === 27) {
                $('.modal').modal('hide');
            }
        });
    }
    
    /**
     * Progress Indicators setup
     */
    setupProgressIndicators() {
        // Progress indicators implementation
        console.log('Progress indicators setup');
    }
    
    /**
     * Loading Spinner mit Animation anzeigen
     */
    showLoadingSpinner() {
        $('.tree-loading').fadeIn(this.animationSpeed);
    }
    
    /**
     * Loading Spinner ausblenden
     */
    hideLoadingSpinner() {
        $('.tree-loading').fadeOut(this.animationSpeed);
    }
    
    /**
     * Smooth Loading für einzelne Knoten
     */
    showNodeLoading(nodeId) {
        this.loadingNodes.add(nodeId);
        const $node = $(`[data-category-id="${nodeId}"]`).closest('.tree-node');
        
        // Loading indicator hinzufügen
        if (!$node.find('.node-loading').length) {
            $node.find('.node-icon').after('<span class="node-loading loading-spinner" style="width:16px;height:16px;margin:0 5px;"></span>');
        }
        
        $node.addClass('loading-state').css('opacity', '0.7');
    }
    
    /**
     * Node Loading beenden
     */
    hideNodeLoading(nodeId) {
        this.loadingNodes.delete(nodeId);
        const $node = $(`[data-category-id="${nodeId}"]`).closest('.tree-node');
        
        $node.find('.node-loading').remove();
        $node.removeClass('loading-state').css('opacity', '');
    }
    
    bindEvents() {
        var self = this;
        
        // Enhanced Kategorie-Suche mit Animation
        $('#category-search').on('input', function() {
            clearTimeout(self.searchTimeout);
            var searchTerm = $(this).val();
            
            // Visual feedback
            $(this).addClass('searching');
            
            if (searchTerm.length >= 2) {
                $('.search-status').html('<i class="fa fa-spinner fa-spin"></i> Suche läuft...').fadeIn();
                
                self.searchTimeout = setTimeout(function() {
                    self.searchCategories(searchTerm);
                }, 300);
            } else {
                $('#search-results').slideUp(self.animationSpeed);
                $('.search-status').fadeOut();
                $(this).removeClass('searching');
            }
        });
        
        // Tree-Knoten expandieren/kollabieren mit Animation
        $(document).on('click', '.tree-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $toggle = $(this);
            const $node = $toggle.closest('.tree-node');
            
            // Animation für Toggle-Button
            $toggle.addClass('rotating');
            setTimeout(() => $toggle.removeClass('rotating'), 300);
            
            self.toggleNode($toggle);
        });
        
        // Enhanced Kategorie bearbeiten mit Hover-Effekt
        $(document).on('click', '.edit-category', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const categoryId = $btn.data('category-id');
            
            // Button Animation
            $btn.addClass('btn-clicked');
            setTimeout(() => $btn.removeClass('btn-clicked'), 200);
            
            self.editCategory(categoryId);
        });
        
        // Alle expandieren/kollabieren
        $('#expand-all-btn').on('click', function() {
            self.expandAll();
        });
        
        $('#collapse-all-btn').on('click', function() {
            self.collapseAll();
        });
        
        // Modal-Events
        $('#save-category-btn').on('click', function() {
            self.saveCategory();
        });
        
        // Neue Kategorie erstellen Button
        $('#create-category-btn').on('click', function(e) {
            e.preventDefault();
            self.openCreateModal();
        });
        
        // Neue Kategorie speichern
        $('#save-new-category-btn').on('click', function() {
            self.saveNewCategory();
        });
        
        // Elternkategorie-Auswahl für neue Kategorie
        $('#create-parent-category').on('change', function() {
            self.updateCreateCategoryPath();
        });
        
        // Name-Input für Pfad-Vorschau
        $('#create-category-name').on('input', function() {
            self.updateCreateCategoryPath();
        });
        
        // Elternkategorie-Suche
        $('#parent-search').on('input', function() {
            clearTimeout(self.parentSearchTimeout);
            var searchTerm = $(this).val();
            var excludeId = $('#edit-category-id').val();
            
            if (searchTerm.length >= 2) {
                self.parentSearchTimeout = setTimeout(function() {
                    self.searchParentCategories(searchTerm, excludeId);
                }, 300);
            } else {
                $('.parent-search-results').hide();
            }
        });
        
        // Suchergebnis auswählen
        $(document).on('click', '.search-result-item', function() {
            var categoryId = $(this).data('category-id');
            self.showCategoryInTree(categoryId);
            $('#search-results').hide();
            $('#category-search').val('');
        });
        
        // Parent-Suchergebnis auswählen
        $(document).on('click', '.parent-search-results .search-result-item', function() {
            var categoryId = $(this).data('category-id');
            var categoryName = $(this).find('strong').text();
            
            $('#edit-parent-category').val(categoryId);
            $('#parent-search').val(categoryName);
            $('.parent-search-results').hide();
        });
        
        // Parent-Suchergebnis auswählen
        $(document).on('click', '.parent-search-results .search-result-item', function() {
            var categoryId = $(this).data('category-id');
            var categoryName = $(this).find('strong').text();
            
            $('#edit-parent-category').val(categoryId);
            $('#parent-search').val(categoryName);
            $('.parent-search-results').hide();
        });
    }
    
    loadRootCategories() {
        var self = this;
        
        // Debug-Information
        console.log('Loading root categories...');
        console.log('AJAX URL:', this.ajaxUrl);
        console.log('CSRF Token:', this.csrfToken);
        
        $('.tree-loading').show();
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'load_children',
                parent_id: 0,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text' // Erstmal als Text laden um die Response zu sehen
        }).done(function(response) {
            console.log('Raw AJAX Response:', response);
            
            // Prüfen ob HTML-Response vorliegt und JSON am Ende extrahieren
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                // JSON am Ende der HTML-Response suchen
                var jsonMatch = response.match(/}$/);
                if (jsonMatch) {
                    // Letzten JSON-Block extrahieren
                    var lines = response.split('\n');
                    var jsonLine = '';
                    for (var i = lines.length - 1; i >= 0; i--) {
                        if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                            jsonLine = lines[i].trim();
                            break;
                        }
                    }
                    if (jsonLine) {
                        response = jsonLine;
                        console.log('Extracted JSON from HTML:', response);
                    }
                }
            }
            
            // Versuche JSON zu parsen
            try {
                var jsonResponse = JSON.parse(response);
                console.log('Parsed JSON Response:', jsonResponse);
                
                if (jsonResponse.success) {
                    self.renderCategories(jsonResponse.data, $('#category-tree'), 0);
                } else {
                    self.showError('Fehler beim Laden der Kategorien: ' + jsonResponse.message);
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response was:', response);
                self.showError('Server antwortete mit ungültigem JSON: ' + response.substring(0, 200));
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', {
                status: status,
                error: error,
                response: xhr.responseText,
                url: self.ajaxUrl
            });
            self.showError('AJAX-Fehler beim Laden der Kategorien: ' + error + ' (Status: ' + status + ')');
        }).always(function() {
            $('.tree-loading').hide();
        });
    }
    
    toggleNode($toggle) {
        var $li = $toggle.closest('li');
        var $childrenContainer = $li.find('> .tree-children');
        var categoryId = $li.data('category-id');
        var isExpanded = $li.hasClass('expanded');
        
        if (isExpanded) {
            // Kollabieren
            $childrenContainer.slideUp(200);
            $li.removeClass('expanded');
            $toggle.find('.fa').removeClass('fa-minus').addClass('fa-plus');
        } else {
            // Expandieren
            if ($childrenContainer.length === 0) {
                // Kinder lazy laden
                this.loadChildren(categoryId, $li);
            } else {
                $childrenContainer.slideDown(200);
                $li.addClass('expanded');
                $toggle.find('.fa').removeClass('fa-plus').addClass('fa-minus');
            }
        }
    }
    
    loadChildren(parentId, $parentLi) {
        var self = this;
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: {
                action: 'load_children',
                parent_id: parentId
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success && response.data.length > 0) {
                var $childrenContainer = $('<ul class="tree-children"></ul>');
                self.renderCategories(response.data, $childrenContainer, 1);
                $parentLi.append($childrenContainer);
                $childrenContainer.slideDown(200);
                $parentLi.addClass('expanded');
                $parentLi.find('> .tree-item .tree-toggle .fa').removeClass('fa-plus').addClass('fa-minus');
            }
        }).fail(function() {
            self.showError('Fehler beim Laden der Unterkategorien');
        });
    }
    
    renderCategories(categories, $container, level) {
        $container.empty();
        
        categories.forEach(function(category) {
            var $li = $('<li class="tree-node" data-category-id="' + category.id + '"></li>');
            
            var toggleIcon = category.has_children ? 'fa-plus' : 'fa-circle-o';
            var toggleClass = category.has_children ? 'tree-toggle' : 'tree-leaf';
            
            var itemHtml = 
                '<div class="tree-item">' +
                '<span class="' + toggleClass + '"><i class="fa ' + toggleIcon + '"></i></span> ' +
                '<span class="tree-label">' + category.name + '</span> ' +
                '<span class="tree-actions">' +
                '<a href="#" class="edit-category" data-category-id="' + category.id + '" title="Bearbeiten">' +
                '<i class="fa fa-edit"></i></a>' +
                '</span>' +
                '</div>';
            
            $li.html(itemHtml);
            $container.append($li);
        });
    }
    
    searchCategories(searchTerm) {
        var self = this;
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'search_categories',
                search: searchTerm,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text'
        }).done(function(response) {
            console.log('Search raw response:', response);
            
            // JSON extrahieren falls HTML-Response
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                var lines = response.split('\n');
                var jsonLine = '';
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                        jsonLine = lines[i].trim();
                        break;
                    }
                }
                if (jsonLine) {
                    response = jsonLine;
                    console.log('Extracted search JSON:', response);
                }
            }
            
            try {
                var jsonResponse = JSON.parse(response);
                console.log('Parsed search results:', jsonResponse);
                
                if (jsonResponse.success) {
                    self.renderSearchResults(jsonResponse.data);
                } else {
                    console.error('Search failed:', jsonResponse.message);
                    self.showError('Fehler bei der Suche: ' + jsonResponse.message);
                }
            } catch (e) {
                console.error('Search JSON parse error:', e);
                self.showError('Fehler beim Verarbeiten der Suchergebnisse');
            }
        }).fail(function(xhr, status, error) {
            console.error('Search AJAX error:', error);
            self.showError('Fehler bei der Suche: ' + error);
        });
    }
    
    searchParentCategories(searchTerm, excludeId) {
        var self = this;
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_parent_options',
                exclude_id: excludeId,
                search: searchTerm
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                self.renderParentSearchResults(response.data);
            }
        }).fail(function() {
            self.showError('Fehler bei der Elternkategorie-Suche');
        });
    }
    
    renderSearchResults(results) {
        var self = this;
        var $container = $('#search-results');
        $container.empty();
        
        if (results.length === 0) {
            $container.html('<div class="search-no-results">Keine Kategorien gefunden</div>');
        } else {
            results.forEach(function(category) {
                var $item = $(
                    '<div class="search-result-item" data-category-id="' + category.id + '">' +
                    '<div class="search-result-content">' +
                    '<strong>' + category.name + '</strong>' +
                    '<br><small class="text-muted">' + (category.path || 'Root-Kategorie') + '</small>' +
                    '</div>' +
                    '<div class="search-result-actions">' +
                    '<button type="button" class="btn btn-xs btn-primary search-edit-btn" data-category-id="' + category.id + '" title="Bearbeiten">' +
                    '<i class="fa fa-edit"></i> Bearbeiten' +
                    '</button> ' +
                    '<button type="button" class="btn btn-xs btn-info search-locate-btn" data-category-id="' + category.id + '" title="Im Baum anzeigen">' +
                    '<i class="fa fa-search"></i> Anzeigen' +
                    '</button>' +
                    '</div>' +
                    '</div>'
                );
                $container.append($item);
            });
            
            // Event-Handler für Bearbeiten-Button
            $container.on('click', '.search-edit-btn', function(e) {
                e.stopPropagation();
                var categoryId = parseInt($(this).attr('data-category-id'));
                console.log('Edit category from search:', categoryId);
                $('#search-results').hide();
                $('#category-search').val('');
                self.editCategory(categoryId);
            });
            
            // Event-Handler für Anzeigen-Button
            $container.on('click', '.search-locate-btn', function(e) {
                e.stopPropagation();
                var categoryId = parseInt($(this).attr('data-category-id'));
                console.log('Locate category in tree:', categoryId);
                $('#search-results').hide();
                $('#category-search').val('');
                self.locateCategoryInTree(categoryId);
            });
            
            // Klick auf den ganzen Item zeigt es im Baum an
            $container.on('click', '.search-result-item', function(e) {
                if (!$(e.target).closest('.search-result-actions').length) {
                    var categoryId = parseInt($(this).attr('data-category-id'));
                    console.log('Locate category from item click:', categoryId);
                    $('#search-results').hide();
                    $('#category-search').val('');
                    self.locateCategoryInTree(categoryId);
                }
            });
        }
        
        $container.show();
    }
    
    locateCategoryInTree(categoryId) {
        var self = this;
        
        console.log('Locating category in tree:', categoryId);
        
        // Erst den Pfad der Kategorie laden
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'load_category_path',
                category_id: categoryId,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text'
        }).done(function(response) {
            console.log('Category path for location:', response);
            
            // JSON extrahieren
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                var lines = response.split('\n');
                var jsonLine = '';
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                        jsonLine = lines[i].trim();
                        break;
                    }
                }
                if (jsonLine) {
                    response = jsonLine;
                }
            }
            
            try {
                var jsonResponse = JSON.parse(response);
                
                if (jsonResponse.success && jsonResponse.data && jsonResponse.data.length > 0) {
                    // Pfad durchlaufen und alle Parent-Kategorien aufklappen
                    self.expandCategoryPath(jsonResponse.data, categoryId);
                } else {
                    console.error('Invalid category path response for location');
                    self.showError('Kategorie konnte nicht gefunden werden');
                }
            } catch (e) {
                console.error('Category path JSON parse error for location:', e);
                self.showError('Fehler beim Laden des Kategorie-Pfads');
            }
        }).fail(function(xhr, status, error) {
            console.error('Category path AJAX error for location:', error);
            self.showError('Fehler beim Laden des Kategorie-Pfads: ' + error);
        });
    }
    
    expandCategoryPath(pathData, targetCategoryId) {
        var self = this;
        
        console.log('Expanding path to category:', targetCategoryId, 'Path:', pathData);
        
        // Alle Parent-Kategorien (außer der letzten, das ist die Zielkategorie)
        var parentsToExpand = pathData.slice(0, -1);
        
        // Rekursiv alle Parents aufklappen
        this.expandParentsRecursively(parentsToExpand, 0, function() {
            // Nach dem Aufklappen zur Zielkategorie scrollen und hervorheben
            setTimeout(function() {
                var $targetNode = $('li[data-category-id="' + targetCategoryId + '"]');
                if ($targetNode.length > 0) {
                    // Zur Kategorie scrollen
                    $('html, body').animate({
                        scrollTop: $targetNode.offset().top - 200
                    }, 500);
                    
                    // Kategorie hervorheben
                    $targetNode.addClass('highlighted');
                    setTimeout(function() {
                        $targetNode.removeClass('highlighted');
                    }, 3000);
                    
                    console.log('Category located and highlighted:', targetCategoryId);
                } else {
                    console.warn('Target category not found in tree after expanding:', targetCategoryId);
                }
            }, 100);
        });
    }
    
    expandParentsRecursively(parents, index, callback) {
        var self = this;
        
        if (index >= parents.length) {
            // Alle Parents aufgeklappt, Callback aufrufen
            callback();
            return;
        }
        
        var parentCategory = parents[index];
        var $parentNode = $('li[data-category-id="' + parentCategory.id + '"]');
        
        console.log('Expanding parent:', parentCategory.id, parentCategory.name);
        
        if ($parentNode.length > 0 && !$parentNode.hasClass('expanded')) {
            // Parent aufklappen
            var $toggle = $parentNode.find('> .tree-item .tree-toggle');
            if ($toggle.length > 0) {
                // Auf das Laden der Kinder warten
                var checkExpanded = function() {
                    if ($parentNode.hasClass('expanded')) {
                        // Nächsten Parent aufklappen
                        self.expandParentsRecursively(parents, index + 1, callback);
                    } else {
                        // Noch warten
                        setTimeout(checkExpanded, 100);
                    }
                };
                
                // Toggle klicken und warten
                $toggle.click();
                setTimeout(checkExpanded, 200);
            } else {
                // Kein Toggle, nächsten Parent
                self.expandParentsRecursively(parents, index + 1, callback);
            }
        } else {
            // Parent bereits aufgeklappt oder nicht gefunden
            self.expandParentsRecursively(parents, index + 1, callback);
        }
    }
    
    renderParentSearchResults(results) {
        var $container = $('.parent-search-results');
        $container.empty();
        
        if (results.length <= 1) { // Nur Root-Option
            $container.html('<div class="search-no-results">Keine passenden Kategorien gefunden</div>');
        } else {
            results.slice(1).forEach(function(option) { // Skip Root-Option
                var $item = $(
                    '<div class="search-result-item" data-category-id="' + option.id + '">' +
                    '<strong>' + option.name + '</strong>' +
                    '<br><small>' + option.display_name + '</small>' +
                    '</div>'
                );
                $container.append($item);
            });
        }
        
        $container.show();
    }
    
    editCategory(categoryId) {
        var self = this;
        
        // Modal vorbereiten
        $('#edit-category-id').val(categoryId);
        
        // Kategorie-Daten laden
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'load_category_path',
                category_id: categoryId,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text'
        }).done(function(response) {
            console.log('Category path raw response:', response);
            
            // JSON extrahieren falls HTML-Response
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                var lines = response.split('\n');
                var jsonLine = '';
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                        jsonLine = lines[i].trim();
                        break;
                    }
                }
                if (jsonLine) {
                    response = jsonLine;
                    console.log('Extracted category path JSON:', response);
                }
            }
            
            try {
                var jsonResponse = JSON.parse(response);
                console.log('Parsed category path:', jsonResponse);
                
                if (jsonResponse.success && jsonResponse.data && jsonResponse.data.length > 0) {
                    var category = jsonResponse.data[jsonResponse.data.length - 1]; // Letzte = aktuelle Kategorie
                    $('#edit-category-name').val(category.name);
                    
                    // Pfad anzeigen
                    var pathText = jsonResponse.data.map(function(c) { return c.name; }).join(' > ');
                    $('#category-path').text(pathText);
                    
                    // Elternkategorien laden
                    console.log('Loading parent options for category:', categoryId, 'current parent:', category.parent_id);
                    self.loadParentOptions(categoryId, category.parent_id);
                    
                    // Modal anzeigen
                    $('#edit-category-modal').modal('show');
                    
                    // Selectpicker beim Modal-Öffnen initialisieren falls noch nicht geschehen
                    $('#edit-category-modal').on('shown.bs.modal', function() {
                        var $select = $('#edit-parent-category');
                        if (!$select.hasClass('selectpicker')) {
                            $select.selectpicker({
                                liveSearch: true,
                                size: 8,
                                style: 'btn-default',
                                title: 'Elternkategorie auswählen...',
                                noneSelectedText: 'Kategorie wählen...',
                                liveSearchPlaceholder: 'Kategorie suchen...'
                            });
                        }
                    });
                } else {
                    console.error('Invalid category path response:', jsonResponse);
                    self.showError('Fehler beim Laden der Kategorie-Daten');
                }
            } catch (e) {
                console.error('Category path JSON parse error:', e);
                console.error('Response was:', response.substring(0, 200));
                self.showError('Fehler beim Verarbeiten der Kategorie-Daten');
            }
        }).fail(function(xhr, status, error) {
            console.error('Category path AJAX error:', error);
            self.showError('Fehler beim Laden der Kategorie-Daten: ' + error);
        });
    }
    
    loadParentOptions(excludeId, selectedId) {
        var self = this;
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'get_parent_options',
                exclude_id: excludeId,
                selected_id: selectedId,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text'
        }).done(function(response) {
            console.log('Parent options raw response:', response);
            
            // Prüfen ob HTML-Response vorliegt und JSON am Ende extrahieren
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                var lines = response.split('\n');
                var jsonLine = '';
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                        jsonLine = lines[i].trim();
                        break;
                    }
                }
                if (jsonLine) {
                    response = jsonLine;
                    console.log('Extracted parent options JSON:', response);
                }
            }
            
            try {
                var jsonResponse = JSON.parse(response);
                console.log('Parsed parent options:', jsonResponse);
                
                if (jsonResponse.success && jsonResponse.data) {
                    if (jsonResponse.success && jsonResponse.data) {
                    var $select = $('#edit-parent-category');
                    $select.empty();
                    
                    // Sicherstellen dass data ein Array ist
                    var options = Array.isArray(jsonResponse.data) ? jsonResponse.data : [];
                    console.log('Processing', options.length, 'parent options');
                    
                    options.forEach(function(option, index) {
                        console.log('Option', index + ':', option);
                        
                        var displayText = option.display_name || option.name || 'Unbekannt';
                        
                        // HTML-Entities durch normale Zeichen ersetzen für bessere Darstellung
                        displayText = displayText.replace(/&nbsp;/g, '\u00A0'); // Non-breaking space
                        displayText = displayText.replace(/&lt;/g, '<');
                        displayText = displayText.replace(/&gt;/g, '>');
                        displayText = displayText.replace(/&amp;/g, '&');
                        
                        var $option = $('<option></option>');
                        $option.attr('value', option.id);
                        $option.text(displayText);
                        
                        // Data-Attribute für CSS-Styling hinzufügen
                        if (option.level !== undefined) {
                            $option.attr('data-level', option.level);
                        }
                        
                        // Icon für hierarchische Struktur hinzufügen
                        if (displayText.indexOf('└') > -1) {
                            $option.attr('data-icon', 'fa-folder-o');
                        }
                        
                        if (option.selected) {
                            $option.prop('selected', true);
                        }
                        $select.append($option);
                    });
                    
                    // Selectpicker initialisieren/aktualisieren
                    if ($select.hasClass('selectpicker')) {
                        $select.selectpicker('refresh');
                    } else {
                        $select.addClass('selectpicker')
                               .attr('data-live-search', 'true')
                               .attr('data-size', '8')
                               .attr('data-style', 'btn-default')
                               .attr('title', 'Elternkategorie auswählen...')
                               .selectpicker({
                                   liveSearch: true,
                                   size: 8,
                                   style: 'btn-default',
                                   title: 'Elternkategorie auswählen...',
                                   noneSelectedText: 'Kategorie wählen...',
                                   liveSearchPlaceholder: 'Kategorie suchen...',
                                   actionsBox: false
                               });
                    }
                    
                    console.log('Parent options loaded successfully:', options.length, 'options');
                } else {
                    console.error('Invalid response structure:', jsonResponse);
                    // Fallback: mindestens Root-Option hinzufügen
                    var $select = $('#edit-parent-category');
                    $select.empty();
                    $select.append('<option value="0">--- Keine Elternkategorie ---</option>');
                    
                    // Selectpicker aktualisieren
                    if ($select.hasClass('selectpicker')) {
                        $select.selectpicker('refresh');
                    }
                }
                } else {
                    console.error('Invalid response structure:', jsonResponse);
                    // Fallback: mindestens Root-Option hinzufügen
                    var $select = $('#edit-parent-category');
                    $select.empty();
                    $select.append('<option value="0">--- Keine Elternkategorie ---</option>');
                }
            } catch (e) {
                console.error('Parent options JSON parse error:', e);
                console.error('Response was:', response.substring(0, 200));
                
                // Fallback: Root-Option hinzufügen
                var $select = $('#edit-parent-category');
                $select.empty();
                $select.append('<option value="0">--- Keine Elternkategorie ---</option>');
                
                // Selectpicker aktualisieren
                if ($select.hasClass('selectpicker')) {
                    $select.selectpicker('refresh');
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('Parent options AJAX error:', error);
            
            // Fallback bei AJAX-Fehler
            var $select = $('#edit-parent-category');
            $select.empty();
            $select.append('<option value="0">--- Keine Elternkategorie ---</option>');
            
            // Selectpicker aktualisieren
            if ($select.hasClass('selectpicker')) {
                $select.selectpicker('refresh');
            }
        });
    }
    
    saveCategory() {
        var self = this;
        var formData = $('#edit-category-form').serialize();
        
        // CSRF Token hinzufügen falls nicht im Formular
        if (formData.indexOf('_csrf_token') === -1 && this.csrfToken) {
            formData += '&_csrf_token=' + encodeURIComponent(this.csrfToken);
        }
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            data: formData + '&action=update_category',
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                $('#edit-category-modal').modal('hide');
                self.showSuccess('Kategorie erfolgreich aktualisiert');
                // Tree neu laden
                self.loadRootCategories();
            } else {
                self.showError(response.message);
            }
        }).fail(function(xhr, status, error) {
            self.showError('Fehler beim Speichern der Kategorie: ' + error);
        });
    }
    
    /**
     * Öffnet das Modal zum Erstellen einer neuen Kategorie
     */
    openCreateModal() {
        // Modal zurücksetzen
        $('#create-category-form')[0].reset();
        $('#create-category-path').html('<em class="text-muted">Root-Ebene (keine Elternkategorie)</em>');
        
        // Elternkategorien für die neue Kategorie laden
        this.loadParentOptionsForCreate();
        
        // Modal anzeigen
        $('#create-category-modal').modal('show');
    }
    
    /**
     * Lädt Elternkategorie-Optionen für neue Kategorie
     */
    loadParentOptionsForCreate() {
        var self = this;
        
        $.ajax({
            url: this.ajaxUrl,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: {
                action: 'get_parent_options',
                exclude_id: 0, // Keine Ausschlüsse beim Erstellen
                selected_id: 0,
                '_csrf_token': this.csrfToken
            },
            dataType: 'text'
        }).done(function(response) {
            // JSON extrahieren falls HTML-Response
            if (response.indexOf('<!doctype html>') === 0 || response.indexOf('<html') === 0) {
                var lines = response.split('\n');
                var jsonLine = '';
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim().startsWith('{') && lines[i].trim().endsWith('}')) {
                        jsonLine = lines[i].trim();
                        break;
                    }
                }
                if (jsonLine) {
                    response = jsonLine;
                }
            }
            
            try {
                var jsonResponse = JSON.parse(response);
                
                if (jsonResponse.success && jsonResponse.data) {
                    var $select = $('#create-parent-category');
                    $select.empty();
                    
                    // Root-Option
                    $select.append('<option value="0" selected>--- Root-Ebene (keine Elternkategorie) ---</option>');
                    
                    // Kategorien hinzufügen
                    jsonResponse.data.forEach(function(option) {
                        if (option.id > 0) {
                            var $option = $('<option></option>')
                                .attr('value', option.id)
                                .text(option.display_name);
                            
                            if (option.level > 0) {
                                $option.attr('data-level', option.level);
                            }
                            
                            $select.append($option);
                        }
                    });
                    
                    // Selectpicker aktualisieren
                    $select.selectpicker('refresh');
                } else {
                    self.showError('Fehler beim Laden der Elternkategorien');
                }
            } catch (e) {
                console.error('JSON parse error in loadParentOptionsForCreate:', e);
                self.showError('Fehler beim Verarbeiten der Elternkategorien');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error in loadParentOptionsForCreate:', status, error);
            self.showError('Fehler beim Laden der Elternkategorien: ' + error);
        });
    }
    
    /**
     * Aktualisiert die Pfad-Vorschau für neue Kategorie
     */
    updateCreateCategoryPath() {
        var selectedParentId = $('#create-parent-category').val();
        var categoryName = $('#create-category-name').val() || '[Neuer Name]';
        
        if (selectedParentId == '0') {
            $('#create-category-path').html('<strong>' + categoryName + '</strong>');
        } else {
            var selectedText = $('#create-parent-category option:selected').text();
            var parentPath = selectedText.replace(/^[\s└│├]*/, '').trim();
            $('#create-category-path').html(parentPath + ' > <strong>' + categoryName + '</strong>');
        }
    }
    
    /**
     * Speichert die neue Kategorie
     */
    saveNewCategory() {
        var self = this;
        var categoryName = $('#create-category-name').val().trim();
        var parentId = $('#create-parent-category').val();
        
        if (!categoryName) {
            self.showError('Bitte geben Sie einen Kategorienamen ein');
            return;
        }
        
        // Button deaktivieren
        var $btn = $('#save-new-category-btn');
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Wird erstellt...');
        
        $.ajax({
            url: this.apiUrl,
            method: 'POST',
            data: {
                action: 'create_category',
                category_name: categoryName,
                parent_id: parentId,
                '_csrf_token': this.csrfToken
            },
            dataType: 'json'
        }).done(function(response) {
            if (response && response.success) {
                self.showSuccess(response.data.message || 'Kategorie wurde erfolgreich erstellt');
                $('#create-category-modal').modal('hide');
                
                // Tree neu laden
                self.loadRootCategories();
            } else {
                self.showError(response.message || 'Fehler beim Erstellen der Kategorie');
            }
        }).fail(function(xhr, status, error) {
            console.error('Create category error:', xhr, status, error);
            self.showError('Fehler beim Erstellen der Kategorie: ' + error);
        }).always(function() {
            // Button wieder aktivieren
            $btn.prop('disabled', false).html(originalText);
        });
    }
    
    showCategoryInTree(categoryId) {
        // TODO: Implementierung um Kategorie im Baum zu finden und sichtbar zu machen
        // Würde den Pfad zur Kategorie laden und alle Parent-Knoten expandieren
        console.log('Show category in tree:', categoryId);
        // Für jetzt einfach zur Kategorie scrollen wenn sichtbar
        var $categoryNode = $('[data-category-id="' + categoryId + '"]');
        if ($categoryNode.length) {
            $categoryNode[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            $categoryNode.addClass('highlight');
            setTimeout(function() {
                $categoryNode.removeClass('highlight');
            }, 2000);
        }
    }
    
    expandAll() {
        // Alle Toggle-Buttons finden und klicken
        $('.tree-toggle').each(function() {
            var $this = $(this);
            if (!$this.closest('li').hasClass('expanded')) {
                $this.trigger('click');
            }
        });
    }
    
    collapseAll() {
        // Alle expandierten Knoten kollabieren
        $('.expanded .tree-toggle').trigger('click');
    }
    
    showSuccess(message) {
        this.showAlert('success', message);
    }
    
    showError(message) {
        this.showAlert('danger', 'Fehler: ' + message);
    }
    
    showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span></button>' +
            message + '</div>';
        
        $('.media-cats-tree').before(alertHtml);
        
        // Alert nach 5 Sekunden ausblenden
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
}

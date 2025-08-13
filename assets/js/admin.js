/**
 * JavaScript d'administration pour UP WP All Import Form Imports
 */

(function($) {
    'use strict';
    
    // Initialisation au chargement du DOM
    $(document).ready(function() {
        initFileInputs();
        initFormSubmission();
        initImportPreview();
        initNotifications();
    });
    
    /**
     * Initialiser les inputs de fichier
     */
    function initFileInputs() {
        // Améliorer l'affichage des input file
        $('input[type="file"]').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            var label = $(this).siblings('label');
            var originalText = label.data('original-text') || label.text();
            
            // Stocker le texte original si pas déjà fait
            if (!label.data('original-text')) {
                label.data('original-text', originalText);
            }
            
            if (fileName) {
                // Limiter la longueur du nom de fichier affiché
                if (fileName.length > 30) {
                    fileName = fileName.substring(0, 27) + '...';
                }
                label.text(fileName);
                label.addClass('file-selected');
            } else {
                label.text(originalText);
                label.removeClass('file-selected');
            }
        });
        
        // Validation des types de fichiers
        $('input[type="file"]').on('change', function() {
            var file = this.files[0];
            if (file) {
                var allowedTypes = ['text/csv', 'application/xml', 'text/xml', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                var allowedExtensions = ['csv', 'xml', 'xls', 'xlsx'];
                var fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                    alert('Type de fichier non autorisé. Seuls les fichiers CSV, XML et Excel sont acceptés.');
                    $(this).val('');
                    $(this).trigger('change'); // Réinitialiser l'affichage
                    return false;
                }
                
                // Vérifier la taille du fichier (max 50MB)
                var maxSize = 50 * 1024 * 1024; // 50MB en bytes
                if (file.size > maxSize) {
                    alert('Le fichier est trop volumineux. Taille maximum autorisée : 50MB.');
                    $(this).val('');
                    $(this).trigger('change');
                    return false;
                }
            }
        });
    }
    
    /**
     * Initialiser la soumission des formulaires
     */
    function initFormSubmission() {
        // Confirmation avant soumission
        $('.upwai-upload-form').on('submit', function(e) {
            var $form = $(this);
            var fileInput = $form.find('input[type="file"]');
            var submitBtn = $form.find('.upwai-launch-btn');
            
            // Vérifier qu'un fichier est sélectionné
            if (!fileInput.val()) {
                e.preventDefault();
                alert('Veuillez sélectionner un fichier à uploader.');
                return false;
            }
            
            // Confirmation
            var confirmMsg = 'Êtes-vous sûr de vouloir lancer cet import ?\n\nCette action va :\n- Uploader le fichier sélectionné\n- Créer un nouvel import basé sur le modèle\n- Lancer automatiquement l\'import';
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
            
            // Désactiver le bouton et afficher le statut de traitement
            submitBtn.prop('disabled', true)
                     .addClass('upwai-processing')
                     .html('<span class="dashicons dashicons-update-alt"></span> Traitement en cours...');
            
            // Ajouter une classe de chargement au formulaire
            $form.addClass('upwai-loading');
            
            // Optionnel : afficher une barre de progression
            showProgressIndicator($form);
        });
        
        // Empêcher les doubles soumissions
        $('.upwai-upload-form').on('submit', function() {
            var $form = $(this);
            if ($form.data('submitted')) {
                return false;
            }
            $form.data('submitted', true);
        });
    }
    
    /**
     * Initialiser l'aperçu des imports
     */
    function initImportPreview() {
        // Mettre à jour l'aperçu quand on change de modèle
        $('#model_import_id').on('change', function() {
            var importId = $(this).val();
            var previewDiv = $('#import-preview');
            
            if (importId) {
                // Afficher un indicateur de chargement
                previewDiv.html('<div class="upwai-loading-preview">Chargement des informations...</div>').show();
                
                // Faire un appel AJAX pour récupérer les détails de l'import
                $.post(upwai_ajax.ajax_url, {
                    action: 'upwai_get_import_preview',
                    import_id: importId,
                    nonce: upwai_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        previewDiv.html(response.data).show();
                        previewDiv.addClass('upwai-fade-in');
                    } else {
                        previewDiv.html('<div class="upwai-error">Impossible de charger les informations de l\'import.</div>');
                    }
                }).fail(function() {
                    previewDiv.html('<div class="upwai-error">Erreur lors du chargement des informations.</div>');
                });
            } else {
                previewDiv.hide().removeClass('upwai-fade-in');
            }
        });
    }
    
    /**
     * Initialiser les notifications
     */
    function initNotifications() {
        // Auto-masquer les notifications après 5 secondes
        $('.notice.is-dismissible').each(function() {
            var $notice = $(this);
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        });
        
        // Améliorer l'accessibilité des notifications
        $('.notice').attr('role', 'alert');
    }
    
    /**
     * Afficher un indicateur de progression
     */
    function showProgressIndicator($form) {
        var progressHtml = '<div class="upwai-progress-container">' +
                          '<div class="upwai-progress-bar">' +
                          '<div class="upwai-progress-fill"></div>' +
                          '</div>' +
                          '<div class="upwai-progress-text">Upload en cours...</div>' +
                          '</div>';
        
        $form.append(progressHtml);
        
        // Animation de la barre de progression
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) {
                progress = 90; // Ne pas aller à 100% avant la fin réelle
            }
            $('.upwai-progress-fill').css('width', progress + '%');
        }, 200);
        
        // Nettoyer l'intervalle si le formulaire est soumis
        $form.data('progress-interval', interval);
    }
    
    /**
     * Fonctions utilitaires
     */
    
    // Formater la taille des fichiers
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Afficher un message toast
    function showToast(message, type) {
        type = type || 'info';
        var toastClass = 'upwai-toast upwai-toast-' + type;
        var toast = $('<div class="' + toastClass + '">' + message + '</div>');
        
        $('body').append(toast);
        
        setTimeout(function() {
            toast.addClass('upwai-toast-show');
        }, 100);
        
        setTimeout(function() {
            toast.removeClass('upwai-toast-show');
            setTimeout(function() {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    // Exposer certaines fonctions globalement si nécessaire
    window.upwaiAdmin = {
        showToast: showToast,
        formatFileSize: formatFileSize
    };
    
})(jQuery);

// Styles CSS pour les éléments JavaScript
jQuery(document).ready(function($) {
    // Ajouter les styles dynamiquement
    var styles = `
        <style id="upwai-dynamic-styles">
            .file-selected {
                background-color: #0073aa !important;
                color: white !important;
            }
            
            .upwai-loading-preview {
                text-align: center;
                padding: 20px;
                color: #666;
                font-style: italic;
            }
            
            .upwai-fade-in {
                animation: upwaiFadeIn 0.3s ease-in;
            }
            
            @keyframes upwaiFadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .upwai-progress-container {
                margin-top: 15px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
            }
            
            .upwai-progress-bar {
                width: 100%;
                height: 8px;
                background: #e0e0e0;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .upwai-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #0073aa, #005a87);
                width: 0%;
                transition: width 0.3s ease;
            }
            
            .upwai-progress-text {
                text-align: center;
                margin-top: 8px;
                font-size: 12px;
                color: #666;
            }
            
            .upwai-toast {
                position: fixed;
                top: 32px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 4px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }
            
            .upwai-toast-show {
                transform: translateX(0);
            }
            
            .upwai-toast-success {
                background: #46b450;
            }
            
            .upwai-toast-error {
                background: #dc3232;
            }
            
            .upwai-toast-info {
                background: #0073aa;
            }
            
            .upwai-error {
                color: #dc3232;
                font-style: italic;
                text-align: center;
                padding: 10px;
            }
        </style>
    `;
    
    if (!$('#upwai-dynamic-styles').length) {
        $('head').append(styles);
    }
});

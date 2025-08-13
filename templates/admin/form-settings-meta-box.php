<?php
/**
 * Template pour la meta box des paramètres de formulaire
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="model_import_id">
                <?php _e('Modèle d\'import WP All Import', 'up-wpai-form-imports'); ?>
                <span class="required">*</span>
            </label>
        </th>
        <td>
            <select name="model_import_id" id="model_import_id" class="regular-text" required>
                <option value="">
                    <?php _e('Sélectionner un modèle d\'import...', 'up-wpai-form-imports'); ?>
                </option>
                <?php if (!empty($imports)): ?>
                    <?php foreach ($imports as $import): ?>
                        <option value="<?php echo esc_attr($import->ID); ?>" 
                                <?php selected($model_import_id, $import->ID); ?>>
                            <?php echo esc_html($import->post_title); ?>
                            <?php if ($import->post_status !== 'publish'): ?>
                                (<?php echo esc_html($import->post_status); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <p class="description">
                <?php _e('Sélectionnez le modèle d\'import WP All Import qui sera cloné lors de l\'upload de fichiers.', 'up-wpai-form-imports'); ?>
                <?php if (empty($imports)): ?>
                    <br><strong style="color: #d63638;">
                        <?php _e('Aucun import WP All Import trouvé. Veuillez d\'abord créer un import dans WP All Import.', 'up-wpai-form-imports'); ?>
                    </strong>
                <?php endif; ?>
            </p>
            
            <?php if (!empty($imports) && $model_import_id): ?>
                <div id="import-preview" class="upwai-import-preview">
                    <?php
                    $selected_import = get_post($model_import_id);
                    if ($selected_import):
                        $import_post_type = get_post_meta($model_import_id, '_import_post_type', true);
                        $import_file = get_post_meta($model_import_id, '_import_file', true);
                        $import_template = get_post_meta($model_import_id, '_import_template', true);
                    ?>
                        <h4><?php _e('Aperçu du modèle sélectionné:', 'up-wpai-form-imports'); ?></h4>
                        <ul>
                            <li><strong><?php _e('Titre:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($selected_import->post_title); ?></li>
                            <li><strong><?php _e('Type de contenu:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($import_post_type ?: 'Non défini'); ?></li>
                            <li><strong><?php _e('Fichier source:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html(basename($import_file ?: 'Non défini')); ?></li>
                            <li><strong><?php _e('Statut:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($selected_import->post_status); ?></li>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <label for="form_description">
                <?php _e('Description du formulaire', 'up-wpai-form-imports'); ?>
            </label>
        </th>
        <td>
            <textarea name="form_description" id="form_description" 
                      rows="4" cols="50" class="large-text"
                      placeholder="<?php _e('Description optionnelle pour ce formulaire d\'import...', 'up-wpai-form-imports'); ?>"><?php echo esc_textarea($form_description); ?></textarea>
            <p class="description">
                <?php _e('Description optionnelle qui sera affichée sur la page de liste des formulaires.', 'up-wpai-form-imports'); ?>
            </p>
        </td>
    </tr>
</table>

<div class="upwai-help-section">
    <h3><?php _e('Comment utiliser ce formulaire', 'up-wpai-form-imports'); ?></h3>
    <ol>
        <li><?php _e('Sélectionnez un modèle d\'import WP All Import existant', 'up-wpai-form-imports'); ?></li>
        <li><?php _e('Ajoutez une description optionnelle', 'up-wpai-form-imports'); ?></li>
        <li><?php _e('Publiez le formulaire', 'up-wpai-form-imports'); ?></li>
        <li><?php _e('Les utilisateurs pourront ensuite uploader des fichiers qui utiliseront automatiquement la configuration du modèle sélectionné', 'up-wpai-form-imports'); ?></li>
    </ol>
    
    <div class="upwai-warning">
        <p><strong><?php _e('Important:', 'up-wpai-form-imports'); ?></strong></p>
        <ul>
            <li><?php _e('Le modèle d\'import sélectionné doit être correctement configuré dans WP All Import', 'up-wpai-form-imports'); ?></li>
            <li><?php _e('Les fichiers uploadés doivent avoir la même structure que le fichier utilisé dans le modèle', 'up-wpai-form-imports'); ?></li>
            <li><?php _e('Seuls les formats CSV, XML et Excel sont acceptés', 'up-wpai-form-imports'); ?></li>
        </ul>
    </div>
</div>

<style>
.upwai-import-preview {
    background: #f0f8ff;
    border: 1px solid #0073aa;
    border-radius: 4px;
    padding: 15px;
    margin-top: 10px;
}

.upwai-import-preview h4 {
    margin-top: 0;
    color: #0073aa;
}

.upwai-import-preview ul {
    margin-bottom: 0;
}

.upwai-help-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.upwai-help-section h3 {
    margin-top: 0;
    color: #23282d;
}

.upwai-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 10px;
    margin-top: 15px;
}

.upwai-warning p {
    margin-top: 0;
    color: #856404;
}

.upwai-warning ul {
    margin-bottom: 0;
    color: #856404;
}

.required {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Mettre à jour l'aperçu quand on change de modèle
    $('#model_import_id').on('change', function() {
        var importId = $(this).val();
        var previewDiv = $('#import-preview');
        
        if (importId) {
            // Faire un appel AJAX pour récupérer les détails de l'import
            $.post(ajaxurl, {
                action: 'upwai_get_import_preview',
                import_id: importId,
                nonce: '<?php echo wp_create_nonce('upwai_preview_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    previewDiv.html(response.data).show();
                } else {
                    previewDiv.hide();
                }
            });
        } else {
            previewDiv.hide();
        }
    });
});
</script>

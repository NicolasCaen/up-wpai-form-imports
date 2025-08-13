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
                            <?php echo esc_html(isset($import->display_title) ? $import->display_title : $import->post_title); ?>
                            <?php if (isset($import->post_status) && $import->post_status !== 'publish'): ?>
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
                    
                    <?php 
                    // Afficher le diagnostic pour aider au débogage
                    $admin = UP_WPAI_Admin::get_instance();
                    $diagnostic = $admin->get_wp_all_import_diagnostic();
                    ?>
                    <div style="margin-top: 10px; padding: 10px; background: #f1f1f1; border-left: 4px solid #0073aa;">
                        <h4><?php _e('Diagnostic WP All Import:', 'up-wpai-form-imports'); ?></h4>
                        <ul style="margin: 5px 0;">
                            <li><strong><?php _e('WP All Import actif:', 'up-wpai-form-imports'); ?></strong> 
                                <?php echo $diagnostic['wp_all_import_active'] ? '✅ Oui' : '❌ Non'; ?>
                            </li>
                            <?php if (isset($diagnostic['database_tables'])): ?>
                                <li><strong><?php _e('Tables de base de données:', 'up-wpai-form-imports'); ?></strong>
                                    <ul style="margin-left: 20px;">
                                        <?php foreach ($diagnostic['database_tables'] as $table => $exists): ?>
                                            <li><?php echo esc_html($table); ?>: <?php echo $exists ? '✅' : '❌'; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <?php if (isset($diagnostic['post_types_count'])): ?>
                                <li><strong><?php _e('Imports par post type:', 'up-wpai-form-imports'); ?></strong>
                                    <ul style="margin-left: 20px;">
                                        <?php foreach ($diagnostic['post_types_count'] as $post_type => $count): ?>
                                            <li><?php echo esc_html($post_type); ?>: <?php echo intval($count); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                            <?php if (isset($diagnostic['pmxi_imports_count'])): ?>
                                <li><strong><?php _e('Imports dans pmxi_imports:', 'up-wpai-form-imports'); ?></strong> 
                                    <?php echo intval($diagnostic['pmxi_imports_count']); ?>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </p>
            
            <?php if (!empty($imports) && $model_import_id): ?>
                <div id="import-preview" class="upwai-import-preview">
                    <?php
                    // Récupérer les vraies données du modèle depuis la table WP All Import
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'pmxi_imports';
                    $selected_import = null;
                    
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        $selected_import = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM $table_name WHERE id = %d",
                            $model_import_id
                        ));
                    }
                    
                    if ($selected_import):
                        // Décoder les options du modèle (stockées en JSON ou sérialisées)
                        $options = array();
                        if (!empty($selected_import->options)) {
                            $options = maybe_unserialize($selected_import->options);
                            if (!is_array($options)) {
                                $options = json_decode($selected_import->options, true);
                                if (!is_array($options)) {
                                    $options = array();
                                }
                            }
                        }
                        
                        // Extraire les informations importantes
                        $import_name = !empty($selected_import->friendly_name) ? $selected_import->friendly_name : $selected_import->name;
                        $import_post_type = isset($options['custom_type']) ? $options['custom_type'] : 
                                          (isset($options['post_type']) ? $options['post_type'] : 'Non défini');
                        $import_file = !empty($selected_import->path) ? basename($selected_import->path) : 'Non défini';
                        $import_template = isset($options['template']) ? $options['template'] : 'Non défini';
                    ?>
                        <h4><?php _e('Aperçu du modèle sélectionné:', 'up-wpai-form-imports'); ?></h4>
                        <ul>
                            <li><strong><?php _e('Titre:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($import_name); ?></li>
                            <li><strong><?php _e('Type de contenu:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($import_post_type); ?></li>
                            <li><strong><?php _e('Fichier source:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html($import_file); ?></li>
                            <li><strong><?php _e('Statut:', 'up-wpai-form-imports'); ?></strong> <?php echo esc_html(!empty($selected_import->registered) ? 'Actif' : 'Inactif'); ?></li>
                        </ul>
                        
                        <?php
                        // Afficher les options du modèle pour diagnostic (en mode debug)
                        if (defined('WP_DEBUG') && WP_DEBUG && !empty($options)) {
                            echo '<details style="margin-top: 10px;"><summary><strong>Debug: Options du modèle WP All Import</strong></summary>';
                            echo '<pre style="background: #f1f1f1; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: auto;">';
                            print_r($options);
                            echo '</pre></details>';
                        }
                        
                        // Afficher toutes les données du modèle pour diagnostic (en mode debug)
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            echo '<details style="margin-top: 10px;"><summary><strong>Debug: Données complètes du modèle</strong></summary>';
                            echo '<pre style="background: #f1f1f1; padding: 10px; font-size: 11px; max-height: 200px; overflow-y: auto;">';
                            print_r($selected_import);
                            echo '</pre></details>';
                        }
                    ?>
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

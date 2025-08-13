<?php
/**
 * Template pour la liste des formulaires d'import
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les formulaires
$forms = get_posts(array(
    'post_type'      => 'up_wpai_form',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
));

// Gérer les messages
$message = isset($_GET['message']) ? $_GET['message'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Formulaires d\'import', 'up-wpai-form-imports'); ?>
    </h1>
    
    <a href="<?php echo admin_url('post-new.php?post_type=up_wpai_form'); ?>" class="page-title-action">
        <?php _e('Ajouter nouveau', 'up-wpai-form-imports'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if ($message === 'import_launched'): ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php _e('Import lancé avec succès !', 'up-wpai-form-imports'); ?>
                <?php if (isset($_GET['import_id'])): ?>
                    <a href="<?php echo admin_url('admin.php?page=pmxi-admin-manage&id=' . intval($_GET['import_id'])); ?>">
                        <?php _e('Voir dans WP All Import', 'up-wpai-form-imports'); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html(urldecode($error)); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (empty($forms)): ?>
        <div class="notice notice-info">
            <p>
                <?php _e('Aucun formulaire d\'import créé.', 'up-wpai-form-imports'); ?>
                <a href="<?php echo admin_url('post-new.php?post_type=up_wpai_form'); ?>">
                    <?php _e('Créer votre premier formulaire', 'up-wpai-form-imports'); ?>
                </a>
            </p>
        </div>
    <?php else: ?>
        
        <div class="upwai-forms-grid">
            <?php foreach ($forms as $form): 
                $model_import_id = get_post_meta($form->ID, '_model_import_id', true);
                $form_description = get_post_meta($form->ID, '_form_description', true);
                
                // Récupérer le vrai nom du modèle depuis la table WP All Import
                $model_import_name = null;
                if ($model_import_id) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'pmxi_imports';
                    
                    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                        $model_data = $wpdb->get_row($wpdb->prepare(
                            "SELECT name, friendly_name FROM $table_name WHERE id = %d",
                            $model_import_id
                        ));
                        
                        if ($model_data) {
                            $model_import_name = !empty($model_data->friendly_name) ? $model_data->friendly_name : $model_data->name;
                        }
                    }
                }
            ?>
                <div class="upwai-form-card">
                    <div class="upwai-form-header">
                        <h3><?php echo esc_html($form->post_title); ?></h3>
                        <div class="upwai-form-actions">
                            <a href="<?php echo get_edit_post_link($form->ID); ?>" class="button button-small">
                                <?php _e('Modifier', 'up-wpai-form-imports'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="upwai-form-content">
                        <?php if ($form_description): ?>
                            <p class="upwai-form-description"><?php echo esc_html($form_description); ?></p>
                        <?php endif; ?>
                        
                        <div class="upwai-form-meta">
                            <strong><?php _e('Modèle lié:', 'up-wpai-form-imports'); ?></strong>
                            <?php if ($model_import_name): ?>
                                <span class="upwai-model-name"><?php echo esc_html($model_import_name); ?></span>
                            <?php elseif ($model_import_id): ?>
                                <span class="upwai-model-error"><?php echo sprintf(__('Modèle #%d (non trouvé dans WP All Import)', 'up-wpai-form-imports'), $model_import_id); ?></span>
                            <?php else: ?>
                                <span class="upwai-no-model"><?php _e('Aucun modèle sélectionné', 'up-wpai-form-imports'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="upwai-form-footer">
                        <?php if ($model_import_name): ?>
                            <form method="post" enctype="multipart/form-data" class="upwai-upload-form">
                                <?php wp_nonce_field('upwai_form_action', 'upwai_nonce'); ?>
                                <input type="hidden" name="upwai_action" value="upload_and_launch">
                                <input type="hidden" name="form_id" value="<?php echo $form->ID; ?>">
                                
                                <div class="upwai-file-input-wrapper">
                                    <input type="file" name="import_file" id="import_file_<?php echo $form->ID; ?>" 
                                           accept=".csv,.xml,.xlsx" required>
                                    <label for="import_file_<?php echo $form->ID; ?>" class="button button-secondary">
                                        <?php _e('Choisir un fichier', 'up-wpai-form-imports'); ?>
                                    </label>
                                </div>
                                
                                <button type="submit" class="button button-primary upwai-launch-btn">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php _e('Uploader et lancer', 'up-wpai-form-imports'); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="upwai-no-model-warning">
                                <?php _e('Veuillez d\'abord associer un modèle d\'import à ce formulaire.', 'up-wpai-form-imports'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
</div>

<style>
.upwai-forms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.upwai-form-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.upwai-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.upwai-form-header h3 {
    margin: 0;
    font-size: 16px;
}

.upwai-form-description {
    color: #666;
    font-style: italic;
    margin-bottom: 15px;
}

.upwai-form-meta {
    margin-bottom: 20px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 3px;
}

.upwai-model-name {
    color: #0073aa;
    font-weight: 500;
}

.upwai-no-model {
    color: #d63638;
}

.upwai-upload-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.upwai-file-input-wrapper {
    position: relative;
}

.upwai-file-input-wrapper input[type="file"] {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.upwai-launch-btn {
    display: flex;
    align-items: center;
    gap: 5px;
}

.upwai-no-model-warning {
    color: #d63638;
    font-style: italic;
    text-align: center;
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Améliorer l'affichage des input file
    $('input[type="file"]').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        var label = $(this).siblings('label');
        if (fileName) {
            label.text(fileName);
        } else {
            label.text('<?php _e('Choisir un fichier', 'up-wpai-form-imports'); ?>');
        }
    });
    
    // Confirmation avant soumission
    $('.upwai-upload-form').on('submit', function(e) {
        var fileInput = $(this).find('input[type="file"]');
        if (!fileInput.val()) {
            e.preventDefault();
            alert('<?php _e('Veuillez sélectionner un fichier à uploader.', 'up-wpai-form-imports'); ?>');
            return false;
        }
        
        var confirmMsg = '<?php _e('Êtes-vous sûr de vouloir lancer cet import ?', 'up-wpai-form-imports'); ?>';
        if (!confirm(confirmMsg)) {
            e.preventDefault();
            return false;
        }
        
        // Désactiver le bouton pour éviter les doubles soumissions
        $(this).find('.upwai-launch-btn').prop('disabled', true).text('<?php _e('Traitement en cours...', 'up-wpai-form-imports'); ?>');
    });
});
</script>

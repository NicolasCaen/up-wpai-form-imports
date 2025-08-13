<?php
/**
 * Clonage d'imports pour UP WP All Import Form Imports
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class UP_WPAI_Import_Cloner {
    
    /**
     * Instance unique
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur
     */
    private function __construct() {
        // Constructeur vide
    }
    
    /**
     * Cloner un import WP All Import
     */
    public function clone_import($model_import_id, $new_file_path) {
        // Récupérer l'import modèle
        $model_import = get_post($model_import_id);
        
        if (!$model_import || $model_import->post_type !== 'import') {
            return new WP_Error('invalid_import', __('Import modèle invalide.', 'up-wpai-form-imports'));
        }
        
        // Créer un nouveau post d'import
        $new_import_data = array(
            'post_title'    => $model_import->post_title . ' - ' . date('Y-m-d H:i:s'),
            'post_content'  => $model_import->post_content,
            'post_status'   => 'draft', // Commencer en brouillon
            'post_type'     => 'import',
            'post_author'   => get_current_user_id(),
            'menu_order'    => $model_import->menu_order,
        );
        
        $new_import_id = wp_insert_post($new_import_data);
        
        if (is_wp_error($new_import_id)) {
            return new WP_Error('clone_failed', __('Impossible de créer le nouvel import.', 'up-wpai-form-imports'));
        }
        
        // Cloner toutes les meta données
        $this->clone_import_meta($model_import_id, $new_import_id, $new_file_path);
        
        // Marquer l'import comme cloné depuis notre plugin
        update_post_meta($new_import_id, '_upwai_cloned_from', $model_import_id);
        update_post_meta($new_import_id, '_upwai_cloned_at', current_time('mysql'));
        update_post_meta($new_import_id, '_upwai_original_file', $new_file_path);
        
        return $new_import_id;
    }
    
    /**
     * Cloner les meta données d'un import
     */
    private function clone_import_meta($source_id, $target_id, $new_file_path) {
        // Récupérer toutes les meta données de l'import source
        $meta_data = get_post_meta($source_id);
        
        foreach ($meta_data as $meta_key => $meta_values) {
            // Ignorer certaines meta données spécifiques
            if (in_array($meta_key, array('_edit_last', '_edit_lock'))) {
                continue;
            }
            
            foreach ($meta_values as $meta_value) {
                $meta_value = maybe_unserialize($meta_value);
                
                // Remplacer le chemin du fichier d'import
                if ($meta_key === '_import_file' || $meta_key === 'path') {
                    $meta_value = $new_file_path;
                }
                
                // Réinitialiser certaines valeurs pour le nouvel import
                if ($meta_key === '_import_processing') {
                    $meta_value = 0;
                }
                
                if ($meta_key === '_import_processed') {
                    $meta_value = 0;
                }
                
                if ($meta_key === '_import_created') {
                    $meta_value = 0;
                }
                
                if ($meta_key === '_import_updated') {
                    $meta_value = 0;
                }
                
                if ($meta_key === '_import_skipped') {
                    $meta_value = 0;
                }
                
                if ($meta_key === '_import_deleted') {
                    $meta_value = 0;
                }
                
                // Réinitialiser les logs et historiques
                if (strpos($meta_key, '_log') !== false || strpos($meta_key, '_history') !== false) {
                    continue; // Ne pas copier les logs
                }
                
                // Ajouter la meta donnée au nouvel import
                add_post_meta($target_id, $meta_key, $meta_value);
            }
        }
        
        // Ajouter des meta données spécifiques au nouvel import
        update_post_meta($target_id, '_import_file', $new_file_path);
        update_post_meta($target_id, '_file_path', $new_file_path);
        update_post_meta($target_id, '_import_created_at', current_time('mysql'));
        
        // Marquer comme prêt pour l'import
        update_post_meta($target_id, '_import_ready', 1);
    }
    
    /**
     * Vérifier si un import peut être cloné
     */
    public function can_clone_import($import_id) {
        $import = get_post($import_id);
        
        if (!$import || $import->post_type !== 'import') {
            return false;
        }
        
        // Vérifier que l'import a les meta données nécessaires
        $required_meta = array('_import_file', '_import_template');
        
        foreach ($required_meta as $meta_key) {
            if (!get_post_meta($import_id, $meta_key, true)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtenir les informations d'un import
     */
    public function get_import_info($import_id) {
        $import = get_post($import_id);
        
        if (!$import) {
            return false;
        }
        
        return array(
            'id'          => $import->ID,
            'title'       => $import->post_title,
            'status'      => $import->post_status,
            'created'     => $import->post_date,
            'file_path'   => get_post_meta($import_id, '_import_file', true),
            'template'    => get_post_meta($import_id, '_import_template', true),
            'post_type'   => get_post_meta($import_id, '_import_post_type', true),
            'processed'   => get_post_meta($import_id, '_import_processed', true),
            'created_posts' => get_post_meta($import_id, '_import_created', true),
            'updated_posts' => get_post_meta($import_id, '_import_updated', true),
            'skipped_posts' => get_post_meta($import_id, '_import_skipped', true),
        );
    }
    
    /**
     * Nettoyer les anciens imports clonés
     */
    public function cleanup_old_cloned_imports($days_old = 30) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $old_imports = get_posts(array(
            'post_type'      => 'import',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_upwai_cloned_at',
                    'value'   => $cutoff_date,
                    'compare' => '<',
                    'type'    => 'DATETIME',
                ),
            ),
        ));
        
        foreach ($old_imports as $import) {
            // Supprimer le fichier associé
            $file_path = get_post_meta($import->ID, '_upwai_original_file', true);
            if ($file_path && file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Supprimer l'import
            wp_delete_post($import->ID, true);
        }
        
        return count($old_imports);
    }
}

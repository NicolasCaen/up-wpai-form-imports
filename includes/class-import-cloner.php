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
        // Vérifier que le modèle d'import existe dans la table WP All Import
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmxi_imports';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return new WP_Error('table_missing', __('Table WP All Import introuvable. Assurez-vous que le plugin WP All Import est installé et activé.', 'up-wpai-form-imports'));
        }
        
        // Vérifier que le modèle existe dans la table pmxi_imports et récupérer ses données
        $model_import_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $model_import_id
        ));
        
        if (!$model_import_data) {
            return new WP_Error('invalid_model', __('Modèle d\'import invalide. L\'ID ' . $model_import_id . ' n\'existe pas dans la table WP All Import.', 'up-wpai-form-imports'));
        }
        
        // Cloner le modèle d'import dans la table pmxi_imports
        $new_import_data = array(
            'name'              => $model_import_data->name . ' - ' . date('Y-m-d H:i:s'),
            'friendly_name'     => $model_import_data->friendly_name . ' - ' . date('Y-m-d H:i:s'),
            'type'              => $model_import_data->type,
            'path'              => $new_file_path,
            'options'           => $model_import_data->options,
            'root_element'      => $model_import_data->root_element,
            'processing'        => 0, // Pas en cours de traitement
            'executing'         => 0, // Pas en cours d'exécution
            'triggered'         => 0, // Pas déclenché
            'queue_chunk_number' => 0,
            'registered_on'     => current_time('mysql'),
            'iteration'         => 0
        );
        
        // Insérer le nouvel import dans la table pmxi_imports
        $result = $wpdb->insert($table_name, $new_import_data);
        
        if ($result === false) {
            return new WP_Error('clone_failed', __('Impossible de créer le nouvel import dans la table WP All Import.', 'up-wpai-form-imports'));
        }
        
        $new_import_id = $wpdb->insert_id;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UP WPAI Debug - New import created with ID: ' . $new_import_id);
        }
        
        return $new_import_id;
    }
    
    /**
     * Obtenir les informations d'un import depuis la table pmxi_imports
     */
    public function get_import_info($import_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmxi_imports';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return false;
        }
        
        // Récupérer les données de l'import
        $import_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $import_id
        ));
        
        if (!$import_data) {
            return false;
        }
        
        return array(
            'id'            => $import_data->id,
            'name'          => $import_data->name,
            'friendly_name' => $import_data->friendly_name,
            'type'          => $import_data->type,
            'path'          => $import_data->path,
            'registered_on' => $import_data->registered_on,
            'processing'    => $import_data->processing,
            'executing'     => $import_data->executing,
            'triggered'     => $import_data->triggered,
            'iteration'     => $import_data->iteration
        );
    }
    
    /**
     * Nettoyer les anciens imports clonés depuis la table pmxi_imports
     */
    public function cleanup_old_cloned_imports($days_old = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmxi_imports';
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        // Récupérer les anciens imports clonés
        $old_imports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE name LIKE %s AND registered_on < %s",
            '% - %', // Pattern pour identifier les imports clonés (contiennent ' - ' avec timestamp)
            $cutoff_date
        ));
        
        $deleted_count = 0;
        foreach ($old_imports as $import) {
            // Supprimer le fichier associé si il existe
            if (!empty($import->path) && file_exists($import->path)) {
                unlink($import->path);
            }
            
            // Supprimer l'import de la table
            $result = $wpdb->delete($table_name, array('id' => $import->id), array('%d'));
            if ($result !== false) {
                $deleted_count++;
            }
        }
        
        return $deleted_count;
    }
}

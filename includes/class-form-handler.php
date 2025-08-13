<?php
/**
 * Gestionnaire de formulaires pour UP WP All Import Form Imports
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class UP_WPAI_Form_Handler {
    
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
        $this->init();
    }
    
    /**
     * Initialiser le gestionnaire
     */
    private function init() {
        add_action('wp_ajax_upwai_upload_file', array($this, 'handle_file_upload'));
        add_action('wp_ajax_upwai_launch_import', array($this, 'handle_import_launch'));
        add_action('init', array($this, 'handle_form_submission'));
    }
    
    /**
     * Gérer la soumission de formulaire
     */
    public function handle_form_submission() {
        if (!isset($_POST['upwai_action']) || !isset($_POST['upwai_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['upwai_nonce'], 'upwai_form_action')) {
            wp_die(__('Erreur de sécurité. Veuillez réessayer.', 'up-wpai-form-imports'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes.', 'up-wpai-form-imports'));
        }
        
        switch ($_POST['upwai_action']) {
            case 'upload_and_launch':
                $this->process_upload_and_launch();
                break;
        }
    }
    
    /**
     * Traiter l'upload et le lancement
     */
    private function process_upload_and_launch() {
        $form_id = intval($_POST['form_id']);
        
        if (!$form_id || get_post_type($form_id) !== 'up_wpai_form') {
            $this->redirect_with_error(__('Formulaire invalide.', 'up-wpai-form-imports'));
            return;
        }
        
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect_with_error(__('Erreur lors de l\'upload du fichier.', 'up-wpai-form-imports'));
            return;
        }
        
        // Valider le type de fichier
        $allowed_types = array('text/csv', 'application/xml', 'text/xml', 'application/vnd.ms-excel');
        $file_type = $_FILES['import_file']['type'];
        $file_extension = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types) && !in_array($file_extension, array('csv', 'xml', 'xlsx'))) {
            $this->redirect_with_error(__('Type de fichier non autorisé. Seuls les fichiers CSV, XML et Excel sont acceptés.', 'up-wpai-form-imports'));
            return;
        }
        
        // Uploader le fichier
        $uploaded_file = $this->upload_file($_FILES['import_file']);
        if (is_wp_error($uploaded_file)) {
            $this->redirect_with_error($uploaded_file->get_error_message());
            return;
        }
        
        // Cloner l'import et lancer
        $result = $this->clone_and_launch_import($form_id, $uploaded_file);
        
        if (is_wp_error($result)) {
            $this->redirect_with_error($result->get_error_message());
            return;
        }
        
        // Redirection avec succès
        $redirect_url = add_query_arg(array(
            'page' => 'up-wpai-forms',
            'message' => 'import_launched',
            'import_id' => $result
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Uploader un fichier
     */
    private function upload_file($file) {
        $upload_dir = wp_upload_dir();
        $upwai_dir = $upload_dir['basedir'] . '/up-wpai-form-imports';
        
        // Créer un nom de fichier unique
        $filename = time() . '_' . sanitize_file_name($file['name']);
        $filepath = $upwai_dir . '/' . $filename;
        
        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return new WP_Error('upload_failed', __('Impossible de déplacer le fichier uploadé.', 'up-wpai-form-imports'));
        }
        
        return $filepath;
    }
    
    /**
     * Cloner un import et le lancer
     */
    private function clone_and_launch_import($form_id, $file_path) {
        $model_import_id = get_post_meta($form_id, '_model_import_id', true);
        
        if (!$model_import_id) {
            return new WP_Error('no_model', __('Aucun modèle d\'import associé à ce formulaire.', 'up-wpai-form-imports'));
        }
        
        // Vérifier que le modèle d'import existe dans la table WP All Import
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmxi_imports';
        
        // Debug : log des informations du modèle
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UP WPAI Debug - Validating Model Import ID: ' . $model_import_id);
            error_log('UP WPAI Debug - Checking table: ' . $table_name);
        }
        
        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('UP WPAI Debug - WP All Import table does not exist');
            }
            return new WP_Error('table_missing', __('Table WP All Import introuvable. Assurez-vous que le plugin WP All Import est installé et activé.', 'up-wpai-form-imports'));
        }
        
        // Vérifier que le modèle existe dans la table pmxi_imports
        $model_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE id = %d",
            $model_import_id
        ));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UP WPAI Debug - Model exists in pmxi_imports: ' . ($model_exists ? 'Yes' : 'No'));
        }
        
        if (!$model_exists) {
            return new WP_Error('invalid_model', __('Modèle d\'import invalide. L\'ID ' . $model_import_id . ' n\'existe pas dans la table WP All Import.', 'up-wpai-form-imports'));
        }
        
        // Le modèle a été validé via la table pmxi_imports
        // Plus besoin de logique basée sur les Custom Post Types
        
        // Cloner l'import
        $cloner = UP_WPAI_Import_Cloner::get_instance();
        $new_import_id = $cloner->clone_import($model_import_id, $file_path);
        
        if (is_wp_error($new_import_id)) {
            return $new_import_id;
        }
        
        // Déclencher l'import via les actions WordPress et WP All Import
        $this->trigger_import_execution($new_import_id);
        
        return $new_import_id;
    }
    
    /**
     * Déclencher l'exécution de l'import via la table pmxi_imports
     */
    private function trigger_import_execution($import_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pmxi_imports';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UP WPAI Debug - Triggering import execution for ID: ' . $import_id);
        }
        
        // Marquer l'import comme déclenché dans la table pmxi_imports
        $result = $wpdb->update(
            $table_name,
            array(
                'triggered' => 1,
                'processing' => 0, // Pas encore en cours de traitement
                'executing' => 0   // Pas encore en cours d'exécution
            ),
            array('id' => $import_id),
            array('%d', '%d', '%d'),
            array('%d')
        );
        
        if ($result === false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('UP WPAI Debug - Failed to trigger import execution');
            }
            return false;
        }
        
        // Déclencher les actions WordPress pour WP All Import
        do_action('wp_all_import_before_import', $import_id);
        do_action('pmxi_before_import', $import_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UP WPAI Debug - Import execution triggered successfully');
        }
        
        return true;
    }
    
    /**
     * Rediriger avec un message d'erreur
     */
    private function redirect_with_error($message) {
        $redirect_url = add_query_arg(array(
            'page' => 'up-wpai-forms',
            'error' => urlencode($message)
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Gérer l'upload de fichier via AJAX
     */
    public function handle_file_upload() {
        check_ajax_referer('upwai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes.', 'up-wpai-form-imports'));
        }
        
        // Traitement de l'upload AJAX ici si nécessaire
        wp_die();
    }
    
    /**
     * Gérer le lancement d'import via AJAX
     */
    public function handle_import_launch() {
        check_ajax_referer('upwai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes.', 'up-wpai-form-imports'));
        }
        
        // Traitement du lancement AJAX ici si nécessaire
        wp_die();
    }
}

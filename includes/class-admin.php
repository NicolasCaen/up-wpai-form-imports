<?php
/**
 * Classe d'administration pour UP WP All Import Form Imports
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class UP_WPAI_Admin {
    
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
     * Initialiser l'administration
     */
    private function init() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_form_meta'));
        add_action('wp_ajax_upwai_get_import_preview', array($this, 'ajax_get_import_preview'));
    }
    
    /**
     * Enregistrer le Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Formulaires d\'import', 'Post type general name', 'up-wpai-form-imports'),
            'singular_name'         => _x('Formulaire d\'import', 'Post type singular name', 'up-wpai-form-imports'),
            'menu_name'             => _x('Formulaires d\'import', 'Admin Menu text', 'up-wpai-form-imports'),
            'name_admin_bar'        => _x('Formulaire d\'import', 'Add New on Toolbar', 'up-wpai-form-imports'),
            'add_new'               => __('Ajouter nouveau', 'up-wpai-form-imports'),
            'add_new_item'          => __('Ajouter un nouveau formulaire', 'up-wpai-form-imports'),
            'new_item'              => __('Nouveau formulaire', 'up-wpai-form-imports'),
            'edit_item'             => __('Modifier le formulaire', 'up-wpai-form-imports'),
            'view_item'             => __('Voir le formulaire', 'up-wpai-form-imports'),
            'all_items'             => __('Tous les formulaires', 'up-wpai-form-imports'),
            'search_items'          => __('Rechercher des formulaires', 'up-wpai-form-imports'),
            'parent_item_colon'     => __('Formulaires parents:', 'up-wpai-form-imports'),
            'not_found'             => __('Aucun formulaire trouvé.', 'up-wpai-form-imports'),
            'not_found_in_trash'    => __('Aucun formulaire trouvé dans la corbeille.', 'up-wpai-form-imports'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Nous créons notre propre menu
            'query_var'          => true,
            'rewrite'            => array('slug' => 'up-wpai-form'),
            'capability_type'    => 'post',
            'capabilities'       => array(
                'create_posts' => 'manage_options',
                'edit_posts'   => 'manage_options',
                'delete_posts' => 'manage_options',
            ),
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'author'),
        );
        
        register_post_type('up_wpai_form', $args);
    }
    
    /**
     * Ajouter le menu d'administration
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Formulaires d\'import', 'up-wpai-form-imports'),
            __('Formulaires d\'import', 'up-wpai-form-imports'),
            'manage_options',
            'up-wpai-forms',
            array($this, 'forms_list_page'),
            'dashicons-upload',
            30
        );
        
        add_submenu_page(
            'up-wpai-forms',
            __('Tous les formulaires', 'up-wpai-form-imports'),
            __('Tous les formulaires', 'up-wpai-form-imports'),
            'manage_options',
            'up-wpai-forms',
            array($this, 'forms_list_page')
        );
        
        add_submenu_page(
            'up-wpai-forms',
            __('Ajouter un formulaire', 'up-wpai-form-imports'),
            __('Ajouter un formulaire', 'up-wpai-form-imports'),
            'manage_options',
            'post-new.php?post_type=up_wpai_form'
        );
    }
    
    /**
     * Page de liste des formulaires
     */
    public function forms_list_page() {
        include UPWAI_PLUGIN_PATH . 'templates/admin/forms-list.php';
    }
    
    /**
     * Charger les scripts d'administration
     */
    public function enqueue_admin_scripts($hook) {
        // Charger uniquement sur nos pages
        if (strpos($hook, 'up-wpai') === false && get_post_type() !== 'up_wpai_form') {
            return;
        }
        
        wp_enqueue_style(
            'upwai-admin-style',
            UPWAI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            UPWAI_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'upwai-admin-script',
            UPWAI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            UPWAI_PLUGIN_VERSION,
            true
        );
        
        // Localiser le script pour AJAX
        wp_localize_script('upwai-admin-script', 'upwai_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('upwai_nonce'),
        ));
    }
    
    /**
     * Ajouter les meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'upwai-form-settings',
            __('Paramètres du formulaire', 'up-wpai-form-imports'),
            array($this, 'form_settings_meta_box'),
            'up_wpai_form',
            'normal',
            'high'
        );
    }
    
    /**
     * Meta box pour les paramètres du formulaire
     */
    public function form_settings_meta_box($post) {
        wp_nonce_field('upwai_save_form_meta', 'upwai_form_meta_nonce');
        
        $model_import_id = get_post_meta($post->ID, '_model_import_id', true);
        $form_description = get_post_meta($post->ID, '_form_description', true);
        
        // Récupérer la liste des imports WP All Import
        $imports = $this->get_wp_all_import_models();
        
        include UPWAI_PLUGIN_PATH . 'templates/admin/form-settings-meta-box.php';
    }
    
    /**
     * Sauvegarder les meta données du formulaire
     */
    public function save_form_meta($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['upwai_form_meta_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['upwai_form_meta_nonce'], 'upwai_save_form_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'up_wpai_form') {
            return;
        }
        
        // Sauvegarder les données
        if (isset($_POST['model_import_id'])) {
            update_post_meta($post_id, '_model_import_id', sanitize_text_field($_POST['model_import_id']));
        }
        
        if (isset($_POST['form_description'])) {
            update_post_meta($post_id, '_form_description', sanitize_textarea_field($_POST['form_description']));
        }
    }
    
    /**
     * Récupérer les modèles d'import WP All Import
     */
    private function get_wp_all_import_models() {
        $imports = get_posts(array(
            'post_type'      => 'import',
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        
        return $imports;
    }
    
    /**
     * Gérer la requête AJAX pour l'aperçu d'import
     */
    public function ajax_get_import_preview() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['nonce'], 'upwai_preview_nonce')) {
            wp_die(__('Erreur de sécurité.', 'up-wpai-form-imports'));
        }
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes.', 'up-wpai-form-imports'));
        }
        
        $import_id = intval($_POST['import_id']);
        $import = get_post($import_id);
        
        if (!$import || $import->post_type !== 'import') {
            wp_send_json_error(__('Import invalide.', 'up-wpai-form-imports'));
        }
        
        // Récupérer les informations de l'import
        $import_post_type = get_post_meta($import_id, '_import_post_type', true);
        $import_file = get_post_meta($import_id, '_import_file', true);
        $import_template = get_post_meta($import_id, '_import_template', true);
        $import_created = get_post_meta($import_id, '_import_created', true);
        $import_updated = get_post_meta($import_id, '_import_updated', true);
        
        // Construire le HTML de l'aperçu
        $preview_html = '<h4>' . __('Aperçu du modèle sélectionné:', 'up-wpai-form-imports') . '</h4>';
        $preview_html .= '<ul>';
        $preview_html .= '<li><strong>' . __('Titre:', 'up-wpai-form-imports') . '</strong> ' . esc_html($import->post_title) . '</li>';
        $preview_html .= '<li><strong>' . __('Type de contenu:', 'up-wpai-form-imports') . '</strong> ' . esc_html($import_post_type ?: __('Non défini', 'up-wpai-form-imports')) . '</li>';
        $preview_html .= '<li><strong>' . __('Fichier source:', 'up-wpai-form-imports') . '</strong> ' . esc_html(basename($import_file ?: __('Non défini', 'up-wpai-form-imports'))) . '</li>';
        $preview_html .= '<li><strong>' . __('Statut:', 'up-wpai-form-imports') . '</strong> ' . esc_html($import->post_status) . '</li>';
        
        if ($import_created || $import_updated) {
            $preview_html .= '<li><strong>' . __('Statistiques:', 'up-wpai-form-imports') . '</strong> ';
            if ($import_created) {
                $preview_html .= sprintf(__('%d créé(s)', 'up-wpai-form-imports'), intval($import_created));
            }
            if ($import_updated) {
                if ($import_created) $preview_html .= ', ';
                $preview_html .= sprintf(__('%d mis à jour', 'up-wpai-form-imports'), intval($import_updated));
            }
            $preview_html .= '</li>';
        }
        
        $preview_html .= '</ul>';
        
        wp_send_json_success($preview_html);
    }
}

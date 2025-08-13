<?php
/**
 * Plugin Name: UP WP All Import Form Imports
 * Plugin URI: https://yourwebsite.com
 * Description: Module permettant de créer des formulaires d'import liés à WP All Import pour automatiser les imports de fichiers.
 * Version: 1.1.0
 * Author: Votre Nom
 * Author URI: https://yourwebsite.com
 * Text Domain: up-wpai-form-imports
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('UPWAI_PLUGIN_VERSION', '1.1.0');
define('UPWAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UPWAI_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('UPWAI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin
 */
class UP_WPAI_Form_Imports {
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialiser le plugin
     */
    private function init() {
        // Hooks d'activation et désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Charger le plugin après que WordPress soit complètement chargé
        add_action('plugins_loaded', array($this, 'load_plugin'));
        
        // Charger les traductions
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Charger le plugin
     */
    public function load_plugin() {
        // Vérifier si WP All Import est actif
        if (!$this->is_wp_all_import_active()) {
            add_action('admin_notices', array($this, 'wp_all_import_missing_notice'));
            return;
        }
        
        // Charger les fichiers nécessaires
        $this->load_dependencies();
        
        // Initialiser les composants
        $this->init_components();
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        require_once UPWAI_PLUGIN_PATH . 'includes/class-admin.php';
        require_once UPWAI_PLUGIN_PATH . 'includes/class-form-handler.php';
        require_once UPWAI_PLUGIN_PATH . 'includes/class-import-cloner.php';
    }
    
    /**
     * Initialiser les composants
     */
    private function init_components() {
        // Initialiser l'administration
        if (is_admin()) {
            UP_WPAI_Admin::get_instance();
        }
        
        // Initialiser le gestionnaire de formulaires
        UP_WPAI_Form_Handler::get_instance();
    }
    
    /**
     * Vérifier si WP All Import est actif
     */
    private function is_wp_all_import_active() {
        return class_exists('PMXI_Plugin');
    }
    
    /**
     * Notice si WP All Import n'est pas installé
     */
    public function wp_all_import_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('UP WP All Import Form Imports', 'up-wpai-form-imports'); ?></strong>
                <?php _e('nécessite que le plugin WP All Import soit installé et activé.', 'up-wpai-form-imports'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'up-wpai-form-imports',
            false,
            dirname(UPWAI_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables personnalisées si nécessaire
        $this->create_upload_directory();
        
        // Ajouter les options par défaut
        add_option('upwai_plugin_version', UPWAI_PLUGIN_VERSION);
        
        // Programmer les tâches cron si nécessaire
        // wp_schedule_event(time(), 'daily', 'upwai_daily_cleanup');
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Nettoyer les tâches cron
        wp_clear_scheduled_hook('upwai_daily_cleanup');
        
        // Flush les règles de réécriture
        flush_rewrite_rules();
    }
    
    /**
     * Créer le répertoire d'upload
     */
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $upwai_dir = $upload_dir['basedir'] . '/up-wpai-form-imports';
        
        if (!file_exists($upwai_dir)) {
            wp_mkdir_p($upwai_dir);
            
            // Créer un fichier .htaccess pour la sécurité
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($upwai_dir . '/.htaccess', $htaccess_content);
        }
    }
}

/**
 * Fonction helper pour obtenir l'instance du plugin
 */
function upwai() {
    return UP_WPAI_Form_Imports::get_instance();
}

// Initialiser le plugin
upwai();

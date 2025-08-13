<?php
/**
 * Fichier de désinstallation pour UP WP All Import Form Imports
 * 
 * Ce fichier est exécuté lors de la suppression du plugin
 */

// Empêcher l'accès direct
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Nettoyer les données du plugin lors de la désinstallation
 */
function upwai_uninstall_cleanup() {
    
    // Supprimer tous les formulaires d'import
    $forms = get_posts(array(
        'post_type'      => 'up_wpai_form',
        'post_status'    => 'any',
        'posts_per_page' => -1,
    ));
    
    foreach ($forms as $form) {
        wp_delete_post($form->ID, true);
    }
    
    // Supprimer les options du plugin
    delete_option('upwai_plugin_version');
    
    // Supprimer les fichiers uploadés
    $upload_dir = wp_upload_dir();
    $upwai_dir = $upload_dir['basedir'] . '/up-wpai-form-imports';
    
    if (file_exists($upwai_dir)) {
        upwai_remove_directory($upwai_dir);
    }
    
    // Nettoyer les tâches cron
    wp_clear_scheduled_hook('upwai_daily_cleanup');
    
    // Supprimer les transients
    delete_transient('upwai_import_models');
    
    // Flush les règles de réécriture
    flush_rewrite_rules();
}

/**
 * Supprimer récursivement un répertoire et son contenu
 */
function upwai_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            upwai_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

// Exécuter le nettoyage
upwai_uninstall_cleanup();

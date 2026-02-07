<?php
//includes/zip-acts.php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Fonctions pour zipper et gérer les fichiers ZIP des plugins
 */

if ( ! function_exists( 'zipper_ensure_upload_dir' ) ) {

    /**
     * Crée le dossier de stockage des ZIP si nécessaire et y ajoute un .htaccess
     */
    function zipper_ensure_upload_dir() {
        if ( ! file_exists( ZIPPER_UPLOAD_DIR ) ) {
            wp_mkdir_p( ZIPPER_UPLOAD_DIR );
            $ht = "Options -Indexes\n<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n";
            @file_put_contents( ZIPPER_UPLOAD_DIR . '/.htaccess', $ht );
        }
    }

    /**
     * Crée un ZIP pour un plugin (dossier ou fichier unique)
     * @param string $plugin_file ex: 'folder/file.php' comme retourné par get_plugins()
     * @return string|WP_Error chemin vers le zip ou WP_Error
     */
    function zipper_create_zip_for_plugin( $plugin_file ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'forbidden', 'Pas la permission' );
        }

        if ( ! extension_loaded( 'zip' ) && ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error( 'no_zip', 'Extension PHP Zip non disponible' );
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $all = get_plugins();
        if ( ! isset( $all[ $plugin_file ] ) ) {
            return new WP_Error( 'not_found', 'Plugin introuvable' );
        }

        $plugin_data = $all[ $plugin_file ];
        $folder = dirname( $plugin_file ); // nom du dossier
        $plugin_dir = WP_PLUGIN_DIR . '/' . $folder;

        // si plugin fichier unique à la racine
        if ( ! is_dir( $plugin_dir ) || $folder === '.' ) {
            $plugin_dir = WP_PLUGIN_DIR . '/' . basename( $plugin_file );
            $folder = basename( $plugin_file, '.php' );
        }

        // sécurité : s'assurer qu'on reste dans wp-content/plugins
        $real_plugin_dir = realpath( $plugin_dir );
        $real_plugins_root = realpath( WP_PLUGIN_DIR );
        if ( ! $real_plugin_dir || strpos( $real_plugin_dir, $real_plugins_root ) !== 0 ) {
            return new WP_Error( 'bad_path', 'Chemin du plugin invalide' );
        }

        zipper_ensure_upload_dir();

        $version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : 'v0';
        $timestamp = gmdate( 'Ymd-His' );
        $safe_folder = preg_replace( '/[^a-zA-Z0-9_-]/', '-', $folder );
        $safe_version = preg_replace( '/[^0-9a-zA-Z_-]/', '-', $version );

        $zip_name = sprintf( '%s-%s-v%s.zip', $safe_folder, $timestamp, $safe_version );
        $zip_path = ZIPPER_UPLOAD_DIR . '/' . $zip_name;

        $zip = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== true ) {
            return new WP_Error( 'zip_open', 'Impossible de créer le ZIP' );
        }

        // motifs à exclure
        $exclude_patterns = array( '/\.git(\/|$)/', '/node_modules(\/|$)/', '/\.DS_Store$/' );

        if ( is_dir( $real_plugin_dir ) ) {
            // ajout récursif des fichiers du dossier
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator( $real_plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ( $files as $file ) {
                $filePath = $file->getRealPath();
                $relativePath = substr( $filePath, strlen( $real_plugin_dir ) + 1 );

                // vérification des exclusions
                $skip = false;
                foreach ( $exclude_patterns as $pat ) {
                    if ( preg_match( $pat, $relativePath ) ) {
                        $skip = true;
                        break;
                    }
                }
                if ( $skip ) continue;

                if ( is_file( $filePath ) ) {
                    $localname = $folder . '/' . $relativePath;
                    $zip->addFile( $filePath, $localname );
                }
            }

        } elseif ( is_file( $real_plugin_dir ) ) {
            // plugin fichier unique
            $zip->addFile( $real_plugin_dir, $folder . '.php' );
        }

        $zip->close();

        // vérification
        if ( ! file_exists( $zip_path ) ) {
            return new WP_Error( 'zip_failed', 'Fichier ZIP non créé' );
        }

        return $zip_path;
    }

    /**
     * Sert un fichier pour téléchargement avec les bons headers puis exit
     * @param string $file_path chemin réel du fichier
     * @param string|null $download_name nom du fichier pour le téléchargement
     */
    function zipper_serve_file_and_exit( $file_path, $download_name = null ) {
        if ( ! file_exists( $file_path ) ) {
            wp_die( 'Fichier introuvable' );
        }

        if ( ob_get_level() ) {
            ob_end_clean();
        }

        $download_name = $download_name ? $download_name : basename( $file_path );

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . basename( $download_name ) . '"' );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );
        readfile( $file_path );
        exit;
    }
}

// -----------------------
// Handlers (admin-post)
// -----------------------

// Row action: zipper et télécharger immédiatement
add_action( 'admin_post_zipper_row_zip', function() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Pas la permission' );
    check_admin_referer( 'zipper_row_zip' );

    if ( empty( $_GET['plugin'] ) ) {
        wp_redirect( admin_url( 'plugins.php?zipper_error=1' ) );
        exit;
    }

    $plugin_file = sanitize_text_field( wp_unslash( $_GET['plugin'] ) );
    $res = zipper_create_zip_for_plugin( $plugin_file );
    if ( is_wp_error( $res ) ) wp_die( $res->get_error_message() );

    zipper_serve_file_and_exit( $res, basename( $res ) );
});

// Admin page: zipper plusieurs plugins et stocker
add_action( 'admin_post_zipper_zip_selected', function() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Pas la permission' );
    check_admin_referer( 'zipper_zip_selected' );

    if ( empty( $_POST['plugins'] ) || ! is_array( $_POST['plugins'] ) ) {
        wp_redirect( admin_url( 'tools.php?page=zipper&msg=no_selection' ) );
        exit;
    }

    $created = array();
    foreach ( $_POST['plugins'] as $plugin_file ) {
        $plugin_file = sanitize_text_field( wp_unslash( $plugin_file ) );
        $res = zipper_create_zip_for_plugin( $plugin_file );
        if ( ! is_wp_error( $res ) ) {
            $created[] = basename( $res );
        }
    }

    wp_redirect( admin_url( 'tools.php?page=zipper&msg=zipped&files=' . rawurlencode( implode( ',', $created ) ) ) );
    exit;
});

// Télécharger un ZIP stocké (GET ou POST)
add_action( 'admin_post_zipper_download', function() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Pas la permission' );

    $file_param = !empty($_POST['file']) ? $_POST['file'] : (!empty($_GET['file']) ? $_GET['file'] : '');
    if ( empty( $file_param ) ) wp_die( 'Fichier manquant' );

    check_admin_referer( 'zipper_download' );

    $file = basename( sanitize_text_field( wp_unslash( $file_param ) ) );
    $path = ZIPPER_UPLOAD_DIR . '/' . $file;

    if ( realpath( $path ) === false || strpos( realpath( $path ), realpath( ZIPPER_UPLOAD_DIR ) ) !== 0 ) {
        wp_die( 'Fichier invalide' );
    }

    zipper_serve_file_and_exit( $path, $file );
});

// Supprimer un ZIP
add_action( 'admin_post_zipper_delete', function() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Pas la permission' );
    check_admin_referer( 'zipper_delete' );

    if ( empty( $_POST['file'] ) ) {
        wp_redirect( admin_url( 'tools.php?page=zipper&msg=missing_file' ) );
        exit;
    }

    $file = basename( sanitize_text_field( wp_unslash( $_POST['file'] ) ) );
    $path = ZIPPER_UPLOAD_DIR . '/' . $file;

    if ( file_exists( $path ) ) {
        unlink( $path );
    }

    wp_redirect( admin_url( 'tools.php?page=zipper&msg=deleted' ) );
    exit;
});

// Supprimer tous les ZIP
add_action( 'admin_post_zipper_delete_all', function() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Pas la permission' );
    check_admin_referer( 'zipper_delete_all' );

    $files = glob( ZIPPER_UPLOAD_DIR . '/*.zip' );
    if ( $files ) {
        foreach ( $files as $f ) {
            @unlink( $f );
        }
    }

    wp_redirect( admin_url( 'tools.php?page=zipper&msg=all_deleted' ) );
    exit;
});

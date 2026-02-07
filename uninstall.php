
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$upload_dir = wp_upload_dir()['basedir'] . '/zipper';

function zipper_rrmdir( $dir ) {
    if ( ! file_exists( $dir ) ) {
        return;
    }
    if ( ! is_dir( $dir ) || is_link( $dir ) ) {
        @unlink( $dir );
        return;
    }
    foreach ( scandir( $dir ) as $item ) {
        if ( $item === '.' || $item === '..' ) continue;
        zipper_rrmdir( $dir . "/" . $item );
    }
    @rmdir( $dir );
}

zipper_rrmdir( $upload_dir );

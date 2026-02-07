<?php
//includes/list-table.php
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the table of stored zips
 */

function zipper_get_stored_zips() {
    $files = array();
    if ( ! file_exists( ZIPPER_UPLOAD_DIR ) ) {
        return $files;
    }
    foreach ( glob( ZIPPER_UPLOAD_DIR . '/*.zip' ) as $path ) {
        if ( is_file( $path ) ) {
            $files[] = array(
                'name' => basename( $path ),
                'path' => $path,
                'size' => filesize( $path ),
                'time' => filemtime( $path ),
            );
        }
    }
    // sort by time desc
    usort( $files, function( $a, $b ) {
        return $b['time'] - $a['time'];
    });
    return $files;
}

function zipper_render_stored_zips_table() {
    zipper_ensure_upload_dir();
    $files = zipper_get_stored_zips();
    ?>

    <?php if ( empty( $files ) ) : ?>
        <p><?php esc_html_e( 'Aucun fichier ZIP stocké.', 'zipper' ); ?></p>
    <?php else : ?>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Fichier', 'zipper' ); ?></th>
                    <th><?php esc_html_e( 'Taille', 'zipper' ); ?></th>
                    <th><?php esc_html_e( 'Créé le', 'zipper' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'zipper' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $files as $f ) : ?>
                <tr>
                    <td><?php echo esc_html( $f['name'] ); ?></td>
                    <td><?php echo size_format( $f['size'], 2 ); ?></td>
                    <td><?php echo esc_html( date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $f['time'] ) ); ?></td>
                    <td>
                        <!-- DOWNLOAD -->
                        <form style="display:inline-block;margin-right:6px;" method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <input type="hidden" name="action" value="zipper_download" />
                            <input type="hidden" name="file" value="<?php echo esc_attr( $f['name'] ); ?>" />
                            <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'zipper_download' ); ?>" />
                            <button class="button" type="submit">Télécharger</button>
                        </form>

                        <!-- DELETE ONE -->
                        <form style="display:inline-block;" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'zipper_delete' ); ?>
                            <input type="hidden" name="action" value="zipper_delete" />
                            <input type="hidden" name="file" value="<?php echo esc_attr( $f['name'] ); ?>" />
                            <button class="button button-secondary" type="submit" onclick="return confirm('Supprimer <?php echo esc_js( $f['name'] ); ?> ?');">
                                Supprimer
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- DELETE ALL — FORMULAIRE INDÉPENDANT -->
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
            <?php wp_nonce_field( 'zipper_delete_all' ); ?>
            <input type="hidden" name="action" value="zipper_delete_all" />
            <button class="button button-danger" type="submit" onclick="return confirm('Supprimer tous les ZIP stockés ?');">
                Supprimer tous les ZIP
            </button>
        </form>

    <?php endif; ?>

    <?php
}


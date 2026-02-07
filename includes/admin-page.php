<?php
//includes/admin-page.php
if (!defined('WPINC')) {
    die;
}

/**
 * Renders the Tools -> Zipper admin page
 */

add_action('admin_menu', function () {
    add_management_page(
        __('Zipper', 'zipper'),
        __('Zipper', 'zipper'),
        'manage_options',
        'zipper',
        'zipper_render_admin_page'
    );
});

function zipper_render_admin_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('No permission');
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    $plugins = get_plugins(); // returns array key => data

    /* -------------- PAGINATION -------------- */
    $per_page = 10;  // Nombre de plugins par page
    $total_plugins = count($plugins);
    $total_pages = ceil($total_plugins / $per_page);

    $current_page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    // Slice des plugins pour la page courante
    $plugins_paged = array_slice($plugins, $offset, $per_page, true);

    // URL de pagination
    $page_url = admin_url('tools.php?page=zipper');

    /* ---------------------------------------- */

    $msg = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : '';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Zipper', 'zipper'); ?></h1>

        <?php if ($msg === 'no_selection'): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Aucun plugin sélectionné.', 'zipper'); ?></p>
            </div>
        <?php elseif ($msg === 'zipped'): ?>
            <div class="notice notice-success">
                <p><?php esc_html_e('Zip(s) créé(s). Voir tableau en bas de page. Dossier : wp-content/uploads/zipper/', 'zipper'); ?></p>
            </div>
        <?php elseif ($msg === 'deleted'): ?>
            <div class="notice notice-success">
                <p><?php esc_html_e('Fichier supprimé.', 'zipper'); ?></p>
            </div>
        <?php elseif ($msg === 'all_deleted'): ?>
            <div class="notice notice-success">
                <p><?php esc_html_e('Tous les ZIP ont été supprimés.', 'zipper'); ?></p>
            </div>
        <?php endif; ?>


        <!-- FORMULAIRE PRINCIPAL -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('zipper_zip_selected'); ?>
            <input type="hidden" name="action" value="zipper_zip_selected" />

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="zipper-check-all" /></th>
                        <th><?php esc_html_e('Plugin', 'zipper'); ?></th>
                        <th><?php esc_html_e('Description / Version', 'zipper'); ?></th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($plugins_paged as $file => $data):
                        $name = $data['Name'];
                        $ver = isset($data['Version']) ? $data['Version'] : '';
                        $desc = isset($data['Description']) ? $data['Description'] : '';
                        $esc_file = esc_attr($file);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="plugins[]" value="<?php echo $esc_file; ?>"
                                    class="zipper-plugin-checkbox" /></td>
                            <td><strong><?php echo esc_html($name); ?></strong><br />
                                <small><?php echo esc_html($file); ?></small>
                            </td>
                            <td><?php echo wp_kses_post(wp_trim_words($desc, 25)); ?>
                                <br /><em><?php esc_html_e('Version:', 'zipper'); ?>         <?php echo esc_html($ver); ?></em>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    // Pagination simple style WordPress
                    if ($total_pages > 1):

                        $range = 2; // nombre de pages autour de la page courante
                        $links = [];

                        // << première page et page précédente
                        if ($current_page > 1) {
                            echo '<a href="' . esc_url($page_url . '&p=1') . '" style="margin-right:4px;">«</a>';
                            echo '<a href="' . esc_url($page_url . '&p=' . ($current_page - 1)) . '" style="margin-right:4px;">‹</a>';
                        }

                        for ($i = 1; $i <= $total_pages; $i++) {
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                                if ($i == $current_page) {
                                    $links[] = '<span style="font-weight:bold; margin:0 3px; color:#0073aa;">[' . $i . ']</span>';
                                } else {
                                    $links[] = '<a href="' . esc_url($page_url . '&p=' . $i) . '" style="margin:0 3px;">' . $i . '</a>';
                                }
                            } elseif ($i == 2 && $current_page > $range + 2) {
                                $links[] = '…';
                            } elseif ($i == $total_pages - 1 && $current_page < $total_pages - $range - 1) {
                                $links[] = '…';
                            }
                        }

                        echo implode(' ', $links);

                        // page suivante et dernière page >>
                        if ($current_page < $total_pages) {
                            echo '<a href="' . esc_url($page_url . '&p=' . ($current_page + 1)) . '" style="margin-left:4px;">›</a>';
                            echo '<a href="' . esc_url($page_url . '&p=' . $total_pages) . '" style="margin-left:4px;">»</a>';
                        }

                    endif;
                    ?>
                </div>
            </div>


            <p style="margin-top:10px;">
                <button type="submit" class="button button-primary">
                    <?php esc_html_e('Zipper les plugins sélectionnés', 'zipper'); ?>
                </button>
            </p>
        </form>

        <h2 style="margin-top:30px;"><?php esc_html_e('ZIPs stockés', 'zipper'); ?></h2>
        <?php zipper_render_stored_zips_table(); ?>

    </div>
    <?php
}
/**
 * Affiche une pagination simple pour WordPress admin
 */
function zipper_render_pagination($current, $total, $base_url)
{
    if ($total <= 1)
        return;

    echo '<div style="margin:10px 0;font-size:14px;">';

    // << page précédente
    if ($current > 1) {
        echo '<a href="' . esc_url($base_url . '&p=1') . '" style="margin-right:4px;">«</a>';
        echo '<a href="' . esc_url($base_url . '&p=' . ($current - 1)) . '" style="margin-right:4px;">‹</a>';
    }

    $links = [];
    $range = 2; // nombre de pages autour de la page courante
    for ($i = 1; $i <= $total; $i++) {
        if ($i == 1 || $i == $total || ($i >= $current - $range && $i <= $current + $range)) {
            if ($i == $current) {
                $links[] = '<span style="font-weight:bold; margin:0 3px; color:#0073aa;">[' . $i . ']</span>';
            } else {
                $links[] = '<a href="' . esc_url($base_url . '&p=' . $i) . '" style="margin:0 3px;">' . $i . '</a>';
            }
        } elseif ($i == 2 && $current > $range + 2) {
            $links[] = '…';
        } elseif ($i == $total - 1 && $current < $total - $range - 1) {
            $links[] = '…';
        }
    }

    echo implode(' ', $links);

    // page suivante >>
    if ($current < $total) {
        echo '<a href="' . esc_url($base_url . '&p=' . ($current + 1)) . '" style="margin-left:4px;">›</a>';
        echo '<a href="' . esc_url($base_url . '&p=' . $total) . '" style="margin-left:4px;">»</a>';
    }

    echo '</div>';
}

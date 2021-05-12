<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Piano Composer Options', 'revengine-sync-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-sync-options-group' ); ?>
        <?php do_settings_sections( 'revengine-sync-options-group' ); ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable RevEngine Sync</th>
                    <td>
                        <label for="revengine_sync_active">
                            <input name="revengine_sync_active" type="checkbox" id="revengine_sync_active" value="1" <?= (get_option("revengine_sync_active")) ? 'checked="checked"' : "" ?>>
                        </label>
                        <p>Next run: <?= (wp_next_scheduled( 'revengine_sync_all_users' )) ? date("Y-m-d H:i:s +Z", wp_next_scheduled( 'revengine_sync_all_users' )) : "" ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Test Mode</th>
                    <td>
                        <label for="revengine_sync_test_mode">
                            <input name="revengine_sync_test_mode" type="checkbox" id="revengine_sync_test_mode" value="1" <?= (get_option("revengine_sync_test_mode")) ? 'checked="checked"' : "" ?>>
                        </label>
                        <p><strong>Note:</strong> In Test Mode, we will still add RevEngine users to Wordpress, but will assign wordpress IDs to "test_wordpress_id" on RevEngine.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RevEngine Sync API Server Address</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_sync_api_url" placeholder="https://wp_sync.revengine.dailymaverick.com" id="revengine_sync_api_url" type="url" value="<?php echo esc_attr( get_option('revengine_sync_api_url') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">RevEngine Sync API Key</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_sync_api_key" placeholder="" id="revengine_sync_api_key" type="password">
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
        <a href="admin.php?page=revengine-sync-run" class="button button-primary">Run</a>
    </form>
</div><!-- /.wrap -->
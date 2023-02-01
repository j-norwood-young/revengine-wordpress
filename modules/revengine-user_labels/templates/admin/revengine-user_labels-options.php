<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine User Label Options', 'revengine' ); ?></h2>
    <?php settings_errors(); ?>

    <?php
    $as_currently_running = as_next_scheduled_action( 'revengine_user_label_sync' );
    ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-user_label-options-group' ); ?>
        <?php do_settings_sections( 'revengine-user_label-options-group' ); ?>
        <h2><?php esc_html_e("RevEngine Settings", "revengine-settings") ?></h2>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable RevEngine User Label Automated Sync</th>
                    <td>
                        <label for="revengine_user_label_schedule_enabled">
                            <input name="revengine_user_label_schedule_enabled" type="checkbox" id="revengine_user_label_schedule_enabled" value="1" <?php esc_attr_e(get_option("revengine_user_label_schedule_enabled") ? 'checked="checked"' : "") ?>>
                        </label>
                        <p>
                            <?php
                            if (!empty($as_currently_running)) {
                                echo 'Next due at ' . esc_html(gmdate('Y-m-d H:i:s', $as_currently_running));
                            } else {
                                echo 'Not currently scheduled';
                            }
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Run Now</th>
                    <td>
                        <?php
                        if ($as_currently_running == 1) {
                            echo '<p>Currently running</p>';
                        } else {
                        ?>
                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=revengine_user_label_sync')); ?>" class="button button-primary">Run Now</a>
                        <?php
                            }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div><!-- /.wrap -->
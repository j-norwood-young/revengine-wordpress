<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Options', 'revengine-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-options-group' ); ?>
        <?php do_settings_sections( 'revengine-options-group' ); ?>
        <h2><?= _("RevEngine Settings", "revengine-settings") ?></h2>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">RevEngine Server Address</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_server_address" placeholder="https://revengine.dailymaverick.com" id="revengine_server_address" type="text" value="<?php echo esc_attr( get_option('revengine_server_address') ); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable RevEngine Tracking</th>
                    <td>
                        <label for="revengine_enable_tracking">
                            <input name="revengine_enable_tracking" type="checkbox" id="revengine_enable_tracking" value="1" <?= (get_option("revengine_enable_tracking")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div><!-- /.wrap -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Callback Settings', 'revengine' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-callback-options-group' ); ?>
        <?php do_settings_sections( 'revengine-callback-options-group' ); ?>
        <h2><?= _("RevEngine Callback Settings", "revengine") ?></h2>
        <hr>
        <table class="form-table">
            <tbody>
                <!-- Enable callback? -->
                <tr>
                    <th scope="row">
                        <label for="revengine_callback_enable"><?= _("Enable callback", "revengine") ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="revengine_callback_enable" id="revengine_callback_enable" value="1" <?= get_option('revengine_callback_enable') ? "checked" : "" ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Callback URL</th>
                    <td>
                        <label for="revengine_callback_url">
                            <input name="revengine_callback_url" type="url" id="revengine_callback_url" value="<?= get_option("revengine_callback_url") ?>">
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Callback Bearer Token</th>
                    <td>
                        <label for="revengine_callback_token">
                            <input name="revengine_callback_token" type="password" id="revengine_callback_token" value="<?= get_option("revengine_callback_token") ?>">
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Debug</th>
                    <td>
                        <label for="revengine_callback_debug">
                            <input name="revengine_callback_debug" type="checkbox" id="revengine_callback_debug" value="1" <?= (get_option("revengine_callback_debug")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
        <?php if ($callback_result) { ?>
            <h2><?= _("Callback result", "revengine") ?></h2>
            <hr>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="revengine_callback_result"><?= _("Result", "revengine") ?></label>
                        </th>
                        <td>
                            <textarea name="revengine_callback_result" id="revengine_callback_result" cols="30" rows="10"><?= $callback_result ?></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        <?php } ?>
    </form>
</div><!-- /.wrap -->
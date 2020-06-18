<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine API Options', 'revengine-api-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-api-options-group' ); ?>
        <?php do_settings_sections( 'revengine-api-options-group' ); ?>
        <h2><?= _("RevEngine Settings", "revengine-settings") ?></h2>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable RevEngine API</th>
                    <td>
                        <label for="revengine_enable_api">
                            <input name="revengine_enable_api" type="checkbox" id="revengine_enable_api" value="1" <?= (get_option("revengine_enable_api")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Content Types to Index</th>
                    <td>
                        <label for="revengine_api_types">
                            <input style="width: 600px; height: 40px;" name="revengine_api_types" placeholder="article, cartoon, opinion" id="revengine_api_types" type="text" value="<?php echo esc_attr( get_option('revengine_api_types') ); ?>">
                            <p>Comma-separated list of api types to index</p>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Debug</th>
                    <td>
                        <label for="revengine_api_debug">
                            <input name="revengine_api_debug" type="checkbox" id="revengine_api_debug" value="1" <?= (get_option("revengine_api_debug")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div><!-- /.wrap -->
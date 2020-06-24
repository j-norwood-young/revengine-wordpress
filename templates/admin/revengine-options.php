<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Options', 'revengine-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-options-group' ); ?>
        <?php do_settings_sections( 'revengine-options-group' ); ?>
        <h2>RevEngine Settings</h2>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">RevEngine API Key</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_api_key" id="revengine_api_key" type="text" value="<?php echo esc_attr( get_option('revengine_api_key') ); ?>" readonly="readonly">
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div><!-- /.wrap -->
<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Content Promoter Options', 'revengine-content_promoter-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-content_promoter-options-group' ); ?>
        <?php do_settings_sections( 'revengine-content_promoter-options-group' ); ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable Content Promoter</th>
                    <td>
                        <label for="revengine_content_promoter_active">
                            <input name="revengine_content_promoter_active" type="checkbox" id="revengine_content_promoter_active" value="1" <?php esc_attr_e(get_option("revengine_content_promoter_active") ? 'checked="checked"' : "") ?>>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Url</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_content_promoter_api_url" placeholder="https://revengine:4019/" id="revengine_content_promoter_api_url" type="text" value="<?php echo esc_attr( get_option('revengine_content_promoter_api_url') ); ?>">
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div><!-- /.wrap -->
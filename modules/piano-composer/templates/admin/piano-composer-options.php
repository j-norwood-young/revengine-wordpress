<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Piano Composer Options', 'revengine-piano_composer-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-piano_composer-options-group' ); ?>
        <?php do_settings_sections( 'revengine-piano_composer-options-group' ); ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable Piano Compose Integration</th>
                    <td>
                        <label for="revengine_piano_active">
                            <input name="revengine_piano_active" type="checkbox" id="revengine_piano_active" value="1" <?= (get_option("revengine_piano_active")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sandbox Mode</th>
                    <td>
                        <label for="revengine_piano_sandbox_mode">
                            <input name="revengine_piano_sandbox_mode" type="checkbox" id="revengine_piano_sandbox_mode" value="1" <?= (get_option("revengine_piano_sandbox_mode")) ? 'checked="checked"' : "" ?>>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Piano ID</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_piano_id" placeholder="" id="revengine_piano_id" type="text" value="<?php echo esc_attr( get_option('revengine_piano_id') ); ?>">
                        <p class="description">Find your key in Piano Compose under "Integrate". It should look like "aid=&lt; your key &gt;". Just leave out the "aid=" bit.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Exclude URLs</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_exclude_urls" placeholder="/url1, -url2-, http://blah.com/url3" id="revengine_exclude_urls" value="<?php echo esc_attr( get_option('revengine_exclude_urls') ); ?>">
                        <p>Comma-separated, eg. "/insider"</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?=	submit_button(); ?>
    </form>
</div><!-- /.wrap -->
<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">

    <h2><?php _e( 'RevEngine Tracker Options', 'revengine-tracker-options' ); ?></h2>
    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'revengine-tracker-options-group' ); ?>
        <?php do_settings_sections( 'revengine-tracker-options-group' ); ?>
        <h2>RevEngine Settings</h2>
        <hr>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Enable RevEngine Tracking</th>
                    <td>
                        <label for="revengine_enable_tracking">
                            <input name="revengine_enable_tracking" type="checkbox" id="revengine_enable_tracking" value="1" <?php esc_attr_e(get_option("revengine_enable_tracking") ? 'checked="checked"' : "") ?>>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RevEngine Tracker Method</th>
                    <td>
                        <select name="revengine_tracker_method" id="revengine_tracker_method">
                            <option value="javascript" <?php esc_attr_e(get_option("revengine_tracker_method") == "javascript" ? 'selected="selected"' : "") ?>>Javascript (Experimental)</option>
                            <option value="amp" <?php esc_attr_e(get_option("revengine_tracker_method") == "amp" ? 'selected="selected"' : "") ?>>AMP</option>
                            <option value="iframe" <?php esc_attr_e(get_option("revengine_tracker_method") == "iframe" ? 'selected="selected"' : "") ?>>IFrame</option>
                            <option value="img" <?php esc_attr_e(get_option("revengine_tracker_method") == "img" ? 'selected="selected"' : "") ?>>Image</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">RevEngine Tracker URL</th>
                    <td>
                        <input style="width: 600px; height: 40px;" name="revengine_tracker_url" placeholder="https://revengine.dailymaverick.com" id="revengine_tracker_url" type="text" value="<?php esc_attr_e(get_option('revengine_tracker_url')); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">RevEngine Tracker Timeout</th>
                    <td>
                        <input style="width: 60px; height: 40px;" name="revengine_tracker_timeout" placeholder="1" id="revengine_tracker_timeout" type="number" value="<?php esc_attr_e(get_option('revengine_tracker_timeout')); ?>">
                        <p>Timeout in seconds</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Debug</th>
                    <td>
                        <label for="revengine_tracker_debug">
                            <input name="revengine_tracker_debug" type="checkbox" id="revengine_tracker_debug" value="1" <?php esc_attr_e(get_option("revengine_tracker_debug") ? 'checked="checked"' : "") ?>>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div><!-- /.wrap -->
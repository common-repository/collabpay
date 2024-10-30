<div class="wrap">
    <div id="icon-themes" class="icon32"></div>
    <h2>CollabPay Settings</h2>

    <?php settings_errors(); ?>

    <form method="POST" action="options.php">
        <?php settings_fields( 'settings_page_general_settings' ); ?>
        <?php do_settings_sections( 'settings_page_general_settings' ); ?>

        <div>
            <div style="display: inline-block"><?php submit_button(); ?></div>

            <?php if (get_option('collabpay_api_key') == '') : ?>
                <div style="display: inline-block; margin-left: 5px;">
                    <p class="submit">
                        <?php get_option('collabpay_api_key'); ?>
                        <a href="https://collabpay.app/register" target="_blank" class="button button-secondary">Register for CollabPay</a>
                    </p>
                </div>
            <? else: ?>
                <div style="display: inline-block; margin-left: 5px;">
                    <p class="submit">
                        <?php get_option('collabpay_api_key'); ?>
                        <a href="https://collabpay.app/home" target="_blank" class="button button-secondary">Go to CollabPay</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <a href="#" onclick="document.getElementById('advanceOptions').classList.toggle('hidden'); this.classList.add('hidden')">Show advanced options</a>

    <?php if (get_option('collabpay_api_key') !== '') : ?>
        <form method="POST" action="<?php echo esc_html(admin_url('admin-post.php')); ?>" id="advanceOptions" class="hidden">
            <input type="hidden" name="option_page" value="settings_page_general_settings">
            <input type="hidden" name="action" value="update_roll">
            <input type="hidden" name="api_key" value="<?php esc_html(get_option('collabpay_api_key')) ?>">

            <h2>Advanced options</h2>

            <p>
                Roll API keys and webhooks - this will provide new API keys to CollabPay and regenerate webhooks.
            </p>

            <div>
                <div style="display: inline-block"><?php submit_button('Roll API keys'); ?></div>
            </div>
        </form>

        <?php
            $form_errors = get_transient("settings_errors");
            delete_transient("settings_errors");

            if (! empty($form_errors)){
                foreach($form_errors as $error){
                    echo '<div style="margin-top: 15px;">'.$error['message'].'</div>';
                }
            }
        ?>
    <?php endif; ?>
</div>

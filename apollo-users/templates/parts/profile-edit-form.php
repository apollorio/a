<?php

/**
 * Profile Edit Form Part
 *
 * Used by [apollo_profile_edit] shortcode
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

$user    = wp_get_current_user();
$user_id = $user->ID;

// Get current data
$social_name     = get_user_meta($user_id, '_apollo_social_name', true);
$bio             = get_user_meta($user_id, '_apollo_bio', true);
$phone           = get_user_meta($user_id, '_apollo_phone', true);
$location        = get_user_meta($user_id, 'user_location', true);
$instagram       = get_user_meta($user_id, 'instagram', true);
$website         = get_user_meta($user_id, '_apollo_website', true);
$privacy_profile = get_user_meta($user_id, '_apollo_privacy_profile', true) ?: 'public';
$privacy_email   = get_user_meta($user_id, '_apollo_privacy_email', true);
?>

<script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>
<form id="apollo-profile-edit-inline" class="apollo-form apollo-form-inline">
    <?php wp_nonce_field('apollo_profile_nonce', 'nonce'); ?>

    <div class="input-group">
        <input type="text" id="inline_social_name" name="social_name" class="apollo-input"
            value="<?php echo esc_attr($social_name); ?>" placeholder=" " />
        <label class="apollo-label" for="inline_social_name"><?php esc_html_e('Nome Social'); ?></label>
    </div>

    <div class="input-group">
        <textarea id="inline_bio" name="bio" rows="3" maxlength="500" class="apollo-input" placeholder=" "><?php echo esc_textarea($bio); ?></textarea>
        <label class="apollo-label" for="inline_bio"><?php esc_html_e('Bio'); ?></label>
    </div>

    <div class="input-group">
        <input type="text" id="inline_location" name="location" class="apollo-input"
            value="<?php echo esc_attr($location); ?>" placeholder=" " />
        <label class="apollo-label" for="inline_location"><?php esc_html_e('Cidade'); ?></label>
    </div>

    <div class="input-group">
        <input type="text" id="inline_instagram" name="instagram" class="apollo-input"
            value="<?php echo esc_attr($instagram); ?>" placeholder=" " />
        <label class="apollo-label" for="inline_instagram"><?php esc_html_e('Instagram'); ?></label>
    </div>

    <div class="input-group">
        <input type="url" id="inline_website" name="website" class="apollo-input"
            value="<?php echo esc_attr($website); ?>" placeholder=" " />
        <label class="apollo-label" for="inline_website"><?php esc_html_e('Site'); ?></label>
    </div>

    <div class="input-group">
        <select id="inline_privacy" name="privacy_profile" class="apollo-input">
            <option value="public" <?php selected($privacy_profile, 'public'); ?>><?php esc_html_e('Público'); ?></option>
            <option value="members" <?php selected($privacy_profile, 'members'); ?>><?php esc_html_e('Membros'); ?></option>
            <option value="private" <?php selected($privacy_profile, 'private'); ?>><?php esc_html_e('Privado'); ?></option>
        </select>
        <label class="apollo-label" for="inline_privacy"><?php esc_html_e('Privacidade'); ?></label>
    </div>

    <div class="apollo-form-actions">
        <button type="submit" class="apollo-btn apollo-btn-primary">
            <?php esc_html_e('Salvar'); ?>
        </button>
    </div>

    <div class="apollo-form-message" style="display: none;"></div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('apollo-profile-edit-inline');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            formData.append('action', 'apollo_update_profile');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const msg = form.querySelector('.apollo-form-message');
                    msg.style.display = 'block';
                    msg.className = 'apollo-form-message ' + (data.success ? 'success' : 'error');
                    msg.textContent = data.data?.message || (data.success ? 'Salvo!' : 'Erro');
                });
        });
    });
</script>
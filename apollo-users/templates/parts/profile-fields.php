<?php

/**
 * Profile Fields Part
 *
 * Used by [apollo_profile_fields] shortcode
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// $user_id and $atts available from shortcode
$edit = isset($atts['edit']) && ($atts['edit'] === 'true' || $atts['edit'] === true);
$user = get_user_by('ID', $user_id);

if (! $user) {
    return;
}

// Field definitions
$fields = array(
    '_apollo_social_name' => array(
        'label' => __('Nome Social'),
        'type'  => 'text',
        'icon'  => 'admin-users',
    ),
    '_apollo_bio'         => array(
        'label' => __('Bio'),
        'type'  => 'textarea',
        'icon'  => 'format-quote',
    ),
    'user_location'       => array(
        'label' => __('Cidade'),
        'type'  => 'text',
        'icon'  => 'location',
    ),
    'instagram'           => array(
        'label' => __('Instagram'),
        'type'  => 'text',
        'icon'  => 'instagram',
    ),
    '_apollo_website'     => array(
        'label' => __('Site'),
        'type'  => 'url',
        'icon'  => 'admin-site',
    ),
);
?>

<div class="apollo-profile-fields">
    <?php
    foreach ($fields as $key => $field) :
        $value = get_user_meta($user_id, $key, true);
    ?>
        <div class="apollo-field-row">
            <span class="apollo-field-label">
                <span class="dashicons dashicons-<?php echo esc_attr($field['icon']); ?>"></span>
                <?php echo esc_html($field['label']); ?>
            </span>

            <?php if ($edit && get_current_user_id() === $user_id) : ?>
                <?php if ($field['type'] === 'textarea') : ?>
                    <textarea name="<?php echo esc_attr($key); ?>" class="apollo-textarea" class="apollo-field-input"><?php echo esc_textarea($value); ?></textarea>
                <?php else : ?>
                    <input type="<?php echo esc_attr($field['type']); ?>"
                        name="<?php echo esc_attr($key); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        class="apollo-field-input" />
                <?php endif; ?>
            <?php else : ?>
                <span class="apollo-field-value">
                    <?php if ($value) : ?>
                        <?php if ($key === 'instagram') : ?>
                            <a href="https://instagram.com/<?php echo esc_attr(ltrim($value, '@')); ?>" target="_blank">
                                <?php echo esc_html($value); ?>
                            </a>
                        <?php elseif ($key === '_apollo_website') : ?>
                            <a href="<?php echo esc_url($value); ?>" target="_blank">
                                <?php echo esc_html(wp_parse_url($value, PHP_URL_HOST)); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html($value); ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <em><?php esc_html_e('Não informado'); ?></em>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .apollo-profile-fields {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .apollo-field-row {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .apollo-field-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--apollo-text-muted, #64748b);
    }

    .apollo-field-label .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }

    .apollo-field-value {
        font-size: 15px;
        color: var(--apollo-text, #1e293b);
    }

    .apollo-field-value a {
        color: var(--apollo-primary, #6366f1);
        text-decoration: none;
    }

    .apollo-field-value a:hover {
        text-decoration: underline;
    }

    .apollo-field-value em {
        color: var(--apollo-text-muted, #64748b);
    }

    .apollo-field-input {
        padding: 10px 0;
        border: none;
        border-bottom: 1px solid var(--apollo-border, rgba(var(--rgb-d), 0.15));
        border-radius: 0;
        background: transparent;
        font-size: 14px;
        width: 100%;
        outline: none;
        transition: border-color 0.3s;
    }

    .apollo-field-input:focus {
        border-bottom-color: var(--primary, FF9820);
    }
</style>
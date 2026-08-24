<?php
/**
 * Meus Eventos — Manage card (form.html card language)
 *
 * Expected: $ev (id, title, banner, status, date_display, loc_name,
 *                edit_url, view_url, coauthors, is_coauthor)
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ev_id     = absint( $ev['id'] ?? 0 );
$ev_status = sanitize_key( $ev['status'] ?? 'draft' );

$status_labels = array(
	'publish'   => __( 'Publicado', 'apollo-events' ),
	'draft'     => __( 'Rascunho', 'apollo-events' ),
	'pending'   => __( 'Pendente', 'apollo-events' ),
	'future'    => __( 'Agendado', 'apollo-events' ),
	'scheduled' => __( 'Agendado', 'apollo-events' ),
	'private'   => __( 'Privado', 'apollo-events' ),
);
$status_css = array(
	'publish'   => 'published',
	'draft'     => 'draft',
	'pending'   => 'draft',
	'future'    => 'scheduled',
	'scheduled' => 'scheduled',
	'private'   => 'draft',
);

$badge_label = $status_labels[ $ev_status ] ?? ucfirst( $ev_status );
$badge_css   = $status_css[ $ev_status ] ?? 'draft';

$ev_banner   = $ev['banner'] ?? '';
$ev_title    = $ev['title'] ?? '';
$ev_meta_str = trim( ( $ev['date_display'] ?? '' ) . ( ! empty( $ev['loc_name'] ) ? ' • ' . $ev['loc_name'] : '' ) );
$edit_url    = $ev['edit_url'] ?? '';
$view_url    = $ev['view_url'] ?? '';
$coauthors   = is_array( $ev['coauthors'] ?? null ) ? $ev['coauthors'] : array();
$ca_count    = count( $coauthors );
?>
                <div class="card sh01 ev-mcard" data-status="<?php echo esc_attr( $ev_status ); ?>" data-id="<?php echo esc_attr( (string) $ev_id ); ?>">
                    <div class="ev-mcard__thumb">
						<?php if ( $ev_banner ) : ?>
                            <img src="<?php echo esc_url( $ev_banner ); ?>" alt="<?php echo esc_attr( $ev_title ); ?>" loading="lazy">
						<?php else : ?>
                            <div class="ev-mcard__thumb-ph"><i class="ri-calendar-event-line"></i></div>
						<?php endif; ?>
                        <span class="ev-badge ev-badge--<?php echo esc_attr( $badge_css ); ?>"><?php echo esc_html( $badge_label ); ?></span>
                    </div>
                    <div class="ev-mcard__body">
                        <h3 class="ev-mcard__name"><?php echo esc_html( $ev_title ); ?></h3>
						<?php if ( $ev_meta_str ) : ?>
                            <span class="ev-mcard__meta"><?php echo esc_html( strtoupper( $ev_meta_str ) ); ?></span>
						<?php endif; ?>

                        <div class="ev-ca">
                            <div class="ev-ca__list" data-event-id="<?php echo esc_attr( (string) $ev_id ); ?>">
								<?php foreach ( $coauthors as $ca ) : ?>
									<?php if ( ! empty( $ca['avatar'] ) ) : ?>
                                        <img class="ev-ca__av" src="<?php echo esc_url( $ca['avatar'] ); ?>" alt="<?php echo esc_attr( $ca['name'] ?? '' ); ?>" title="<?php echo esc_attr( $ca['name'] ?? '' ); ?>">
									<?php endif; ?>
								<?php endforeach; ?>
                                <span class="ev-ca__add" aria-label="<?php esc_attr_e( 'Co-autores', 'apollo-events' ); ?>"><i class="ri-team-line"></i></span>
                            </div>
                            <span class="ev-ca__count">
								<?php
								/* translators: %d: number of co-authors */
								printf( esc_html( _n( '%d co-autor', '%d co-autores', $ca_count, 'apollo-events' ) ), (int) $ca_count );
								?>
                            </span>
                        </div>

                        <div class="ev-mcard__actions">
							<?php if ( $edit_url ) : ?>
                                <a href="<?php echo esc_url( $edit_url ); ?>" class="btn btn-secondary btn-sm"><i class="ri-edit-line"></i> <?php esc_html_e( 'Editar', 'apollo-events' ); ?></a>
							<?php endif; ?>
							<?php if ( 'publish' === $ev_status && $view_url ) : ?>
                                <a href="<?php echo esc_url( $view_url ); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener"><i class="ri-eye-line"></i> <?php esc_html_e( 'Ver', 'apollo-events' ); ?></a>
							<?php elseif ( in_array( $ev_status, array( 'draft', 'pending' ), true ) ) : ?>
                                <button type="button" class="btn btn-primary btn-sm" data-action="publish" data-event-id="<?php echo esc_attr( (string) $ev_id ); ?>"><i class="ri-send-plane-line"></i> <?php esc_html_e( 'Publicar', 'apollo-events' ); ?></button>
							<?php endif; ?>
                        </div>
                    </div>
                </div>

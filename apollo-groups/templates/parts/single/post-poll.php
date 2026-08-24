<?php
/**
 * Single Part — Post Poll
 *
 * Poll component inside a post.
 * Expects: $poll_data_for_part (array) with keys: question, options (array), total_votes, ends_at
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $poll_data_for_part ) ) {
	return;
}

$question    = $poll_data_for_part['question'] ?? '';
$options     = $poll_data_for_part['options'] ?? array();
$total_votes = (int) ( $poll_data_for_part['total_votes'] ?? 0 );
$ends_at     = $poll_data_for_part['ends_at'] ?? '';
?>
<div class="poll-question"><?php echo esc_html( $question ); ?></div>
<div class="poll-options">
	<?php
	foreach ( $options as $opt ) :
		$pct = $total_votes > 0 ? round( ( (int) ( $opt['votes'] ?? 0 ) / $total_votes ) * 100 ) : 0;
		?>
		<div class="poll-option" data-option-id="<?php echo esc_attr( $opt['id'] ?? '' ); ?>">
			<div class="poll-option-bar" style="width:<?php echo esc_attr( $pct ); ?>%;"></div>
			<span class="poll-option-text"><?php echo esc_html( $opt['text'] ?? '' ); ?></span>
			<span class="poll-option-pct"><?php echo esc_html( $pct ); ?>%</span>
		</div>
	<?php endforeach; ?>
</div>
<div class="poll-meta">
	<span><?php echo esc_html( $total_votes ); ?> votos</span>
	<?php if ( $ends_at ) : ?>
		<span>Encerra em <?php echo esc_html( $ends_at ); ?></span>
	<?php endif; ?>
</div>

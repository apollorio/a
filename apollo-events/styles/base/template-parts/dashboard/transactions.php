<?php
/**
 * Meus Eventos — Recent transactions (hidden when empty)
 *
 * Expected: $transactions (array of {type,name,time,amount,currency})
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $transactions ) ) {
	return;
}
?>
        <div class="card sh01 ev-tx">
            <div class="tref-sec-lbl"><i class="ri-exchange-funds-line"></i> <?php esc_html_e( 'Transações Recentes', 'apollo-events' ); ?></div>
            <div style="margin-top: 10px;">
				<?php
				foreach ( $transactions as $tx ) :
					$is_income = ( $tx['type'] ?? 'income' ) === 'income';
					?>
                    <div class="ev-tx__row">
                        <div class="ev-tx__l">
                            <div class="ev-tx__ic"><i class="<?php echo esc_attr( $is_income ? 'ri-arrow-down-line' : 'ri-arrow-up-line' ); ?>"></i></div>
                            <div>
                                <div class="ev-tx__name"><?php echo esc_html( $tx['name'] ?? '' ); ?></div>
                                <div class="ev-tx__time"><?php echo esc_html( $tx['time'] ?? '' ); ?></div>
                            </div>
                        </div>
                        <span class="ev-tx__amt <?php echo $is_income ? 'ev-tx__amt--income' : ''; ?>"><?php echo esc_html( ( $is_income ? '+' : '-' ) . ( $tx['currency'] ?? 'R$' ) . ' ' . ( $tx['amount'] ?? '0' ) ); ?></span>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>

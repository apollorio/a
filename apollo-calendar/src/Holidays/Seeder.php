<?php
/**
 * Apollo Calendar — Holiday Seeder
 *
 * Seeds apollo_holidays for a given year with:
 *   • 9 BR national fixed holidays
 *   • 3 movable holidays derived from Easter (Carnaval, Sexta-feira Santa, Corpus Christi)
 *   • 1 Rio de Janeiro municipal fixed holiday (São Sebastião 20/01)
 *
 * Rows use INSERT IGNORE semantics via HolidayModel::upsert() so re-runs are idempotent.
 * A yearly WP-Cron event (`apollo_calendar_seed_holidays`) keeps next-year data fresh.
 *
 * @package Apollo\Calendar\Holidays
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\Holidays;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Seeder {

	// ─── Entry points ──────────────────────────────────────────────────

	/**
	 * Seed a range of years (activation + cron).
	 *
	 * @param int ...$years One or more years to seed.
	 */
	public static function seed_years( int ...$years ): void {
		foreach ( $years as $year ) {
			self::seed_year( $year );
		}
	}

	/**
	 * Seed a single year.
	 *
	 * @param int $year Four-digit year.
	 */
	public static function seed_year( int $year ): void {
		foreach ( self::national_fixed( $year ) as $holiday ) {
			HolidayModel::upsert( $holiday );
		}
		foreach ( self::national_movable( $year ) as $holiday ) {
			HolidayModel::upsert( $holiday );
		}
		foreach ( self::rio_municipal( $year ) as $holiday ) {
			HolidayModel::upsert( $holiday );
		}
	}

	// ─── Fixed national holidays ───────────────────────────────────────

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function national_fixed( int $year ): array {
		$holidays = array(
			array( 'date' => '01-01', 'title' => 'Confraternização Universal' ),
			array( 'date' => '21-04', 'title' => 'Tiradentes' ),
			array( 'date' => '01-05', 'title' => 'Dia do Trabalho' ),
			array( 'date' => '07-09', 'title' => 'Independência do Brasil' ),
			array( 'date' => '12-10', 'title' => 'Nossa Senhora Aparecida' ),
			array( 'date' => '02-11', 'title' => 'Finados' ),
			array( 'date' => '15-11', 'title' => 'Proclamação da República' ),
			array( 'date' => '20-11', 'title' => 'Dia da Consciência Negra' ),
			array( 'date' => '25-12', 'title' => 'Natal' ),
		);

		$result = array();
		foreach ( $holidays as $h ) {
			list( $day, $month ) = explode( '-', $h['date'] );
			$result[] = array(
				'holiday_date' => sprintf( '%04d-%02d-%02d', $year, (int) $month, (int) $day ),
				'title'        => $h['title'],
				'scope'        => 'national',
				'region'       => '',
				'recurring'    => 1,
				'year'         => $year,
				'source'       => 'seeded',
			);
		}

		return $result;
	}

	// ─── Movable national holidays (Easter-based) ─────────────────────

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function national_movable( int $year ): array {
		$easter = easter_date( $year ); // Unix timestamp of Easter Sunday.

		$movable = array(
			array(
				'offset' => -47,
				'title'  => 'Carnaval',
			),
			array(
				'offset' => -2,
				'title'  => 'Sexta-feira Santa',
			),
			array(
				'offset' => 60,
				'title'  => 'Corpus Christi',
			),
		);

		$result = array();
		foreach ( $movable as $m ) {
			$ts     = $easter + ( $m['offset'] * DAY_IN_SECONDS );
			$result[] = array(
				'holiday_date' => gmdate( 'Y-m-d', $ts ),
				'title'        => $m['title'],
				'scope'        => 'national',
				'region'       => '',
				'recurring'    => 1,
				'year'         => $year,
				'source'       => 'seeded',
			);
		}

		return $result;
	}

	// ─── Rio de Janeiro municipal holidays ────────────────────────────

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private static function rio_municipal( int $year ): array {
		$holidays = array(
			array( 'date' => '01-20', 'title' => 'São Sebastião — Padroeiro do Rio' ),
			array( 'date' => '11-20', 'title' => 'Zumbi dos Palmares (Municipal RJ)' ),
		);

		$result = array();
		foreach ( $holidays as $h ) {
			list( $month, $day ) = explode( '-', $h['date'] );
			$result[] = array(
				'holiday_date' => sprintf( '%04d-%02d-%02d', $year, (int) $month, (int) $day ),
				'title'        => $h['title'],
				'scope'        => 'municipal',
				'region'       => 'RJ',
				'recurring'    => 1,
				'year'         => $year,
				'source'       => 'seeded',
			);
		}

		return $result;
	}
}

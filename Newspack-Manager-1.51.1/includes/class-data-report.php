<?php
/**
 * Newspack Manager Data_Report
 *
 * @package Newspack
 */

namespace Newspack_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Newspack Manager Data_Report Class.
 *
 * We want this job to run once a day, at the first hour of the day so that we can report the previous day's data.
 * To achieve that, we use an hourly cron job and make sure it will run only once, the first time it's invoked in a day.
 */
final class Data_Report {
	/**
	 * The cron hook name that will run daily to report data.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'newspack_manager_data_report';

	/**
	 * The option name where we store the last time this job ran.
	 */
	const LAST_RUN_OPTION_NAME = 'newspack_manager_data_report_last_run';

	/**
	 * Initializer
	 */
	public static function init() {
		add_action( self::CRON_HOOK, [ __CLASS__, 'export_available_data' ] );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Report ESP data.
	 */
	private static function get_esp_data() {
		if ( method_exists( '\Newspack_Newsletters', 'get_service_provider' ) ) {
			$provider = \Newspack_Newsletters::get_service_provider();
			if ( null !== $provider && method_exists( $provider, 'get_usage_report' ) ) {
				$report = $provider->get_usage_report();
				if ( \is_wp_error( $report ) ) {
					Logger::log(
						'data_report_esp_data',
						$report->get_error_message(),
						[
							'type'      => 'error',
							'log_level' => 3,
						]
					);
					return [];
				}
				return [ $report->to_array() ];
			}
		}
		return [];
	}

	/**
	 * Parse donations data.
	 *
	 * @param array $orders The orders data.
	 */
	public static function parse_donations_data( $orders ) {
		if ( ! function_exists( 'wcs_order_contains_renewal' ) || ! method_exists( '\Newspack\Donations', 'get_recurrence_of_order' ) ) {
			return [];
		}
		$results     = [];
		$currencies  = [];
		$recurrences = [];
		$renewals    = [
			'yes' => [],
			'no'  => [],
		];
		$dates       = [];

		$orders_map = [];

		foreach ( $orders as $order ) {
			if ( ! $order->get_date_paid() ) {
				continue;
			}
			$id = $order->get_id();

			$recurrence                               = \Newspack\Donations::get_recurrence_of_order( $order );
			$recurrences[ $recurrence ][]             = $id;
			$currencies[ $order->get_currency() ][]   = $id;
			$date_paid                                = $order->get_date_created()->date( 'Y-m-d' );
			$dates[ $date_paid ][]                    = $id;
			$is_renewal                               = \wcs_order_contains_renewal( $order );
			$renewals[ $is_renewal ? 'yes' : 'no' ][] = $id;

			$orders_map[ $id ] = $order;
		}
		foreach ( $dates as $date => $orders_with_date ) {
			foreach ( $currencies as $currency => $orders_with_currency ) {
				foreach ( $recurrences as $recurrence => $orders_with_recurrence ) {
					foreach ( $renewals as $is_renewal => $orders_with_renewal ) {
						$order_ids_for_metric = array_intersect( $orders_with_date, $orders_with_currency, $orders_with_recurrence, $orders_with_renewal );
						if ( empty( $order_ids_for_metric ) ) {
							continue;
						}
						$results[] = [
							'date'                => $date,
							'donation_currency'   => $currency,
							'new_donations'       => count( $order_ids_for_metric ),
							'donation_revenue'    => array_reduce(
								$order_ids_for_metric,
								function( $carry, $order_id ) use ( $orders_map ) {
									$order = $orders_map[ $order_id ];
									return $carry + $order->get_total();
								},
								0
							),
							'donation_recurrence' => $recurrence,
							'cancelled_donations' => 0,
							'donation_renewal'    => $is_renewal,
						];
					}
				}
			}
		}
		return $results;
	}

	/**
	 * Get Donations data for the given date range. By default, it will return data for the previous day.
	 *
	 * The format is one record per donation metric, where a metric is a unique combination of:
	 *   - recurrence status (one-time / monthly / yearly)
	 *   - currency
	 *   - renewal status (is a renewal or not)
	 *
	 * @param string $date_start The start date (included).
	 * @param string $date_end The end date (included).
	 */
	public static function get_donations_data( $date_start = '-1 day', $date_end = '-1 day' ) {

		if ( ! function_exists( 'wc_get_orders' ) ) {
			return [];
		}

		$start_date = gmdate( 'Y-m-d', strtotime( $date_start ) );
		$end_date   = gmdate( 'Y-m-d', strtotime( $date_end ) );

		// Get orders.
		$orders                 = wc_get_orders(
			[
				'status'       => [ 'completed', 'processing' ],
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
			]
		);
		$donations_metrics_data = self::parse_donations_data( $orders );

		// Get subscription cancellations.
		if ( function_exists( 'wcs_get_subscriptions' ) ) {
			$meta_query    = [
				'relation' => 'AND',
				[
					'key'     => '_schedule_cancelled',
					'compare' => '>=',
					'value'   => $start_date . ' 00:00:00',
				],
				[
					'key'     => '_schedule_cancelled',
					'compare' => '<=',
					'value'   => $end_date . ' 00:00:00',
				],
			];
			$subscriptions = wcs_get_subscriptions(
				[
					'subscription_status'    => 'cancelled',
					'subscriptions_per_page' => -1,
					'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				]
			);
			$by_date       = [];
			foreach ( $subscriptions as $subscription ) {
				$end_date = gmdate( 'Y-m-d', $subscription->get_time( 'end', 'gmt' ) );
				if ( isset( $by_date[ $end_date ] ) ) {
					$by_date[ $end_date ][] = $subscription;
				} else {
					$by_date[ $end_date ] = [ $subscription ];
				}
			}
			foreach ( $by_date as $date => $subscriptions ) {
				$donations_metrics_data[] = [
					'date'                => $date,
					'cancelled_donations' => count( $subscriptions ),
					'donation_currency'   => '',
					'new_donations'       => 0,
					'donation_revenue'    => 0,
					'donation_recurrence' => '',
					'donation_renewal'    => '',
				];
			}
		}

		return $donations_metrics_data;
	}

	/**
	 * Check if we should run this job based on the last time it ran.
	 *
	 * @return boolean
	 */
	private static function should_run() {
		if ( ! \Newspack_Manager::is_connected_to_production_manager() ) {
			return false;
		}

		$last_run = get_option( self::LAST_RUN_OPTION_NAME );
		if ( ! $last_run ) {
			// If this is the first time this job is running and the option does not exist, save the option and return.
			// This should run as close to midnight as possible.
			update_option( self::LAST_RUN_OPTION_NAME, gmdate( 'Y-m-d' ) );
			return false;
		}
		if ( $last_run >= gmdate( 'Y-m-d' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Report all available data.
	 */
	public static function export_available_data() {
		if ( ! self::should_run() ) {
			return;
		}

		$esp_data       = self::get_esp_data();
		$donations_data = self::get_donations_data();

		$result = \wp_safe_remote_post(
			\Newspack_Manager::authenticate_manager_client_url(
				'/wp-json/newspack-manager-client/v1/data-report'
			),
			[
				'body'    => wp_json_encode(
					[
						[
							'type' => 'esp',
							'data' => $esp_data,
						],
						[
							'type' => 'donation_metrics',
							'data' => $donations_data,
						],
					]
				),
				'timeout' => 30, // phpcs:ignore
			]
		);

		if ( ! is_wp_error( $result ) && 200 === $result['response']['code'] ) {
			update_option( self::LAST_RUN_OPTION_NAME, gmdate( 'Y-m-d' ) );
			return true;
		} else {
			if ( is_wp_error( $result ) ) {
				$message = $result->get_error_message();
			} else {
				$message = wp_remote_retrieve_body( $result );
			}

			Logger::log(
				'data_report_send',
				$message,
				[
					'type'      => 'error',
					'log_level' => 3,
					'data'      => [
						'esp_data'       => $esp_data,
						'donations_data' => $donations_data,
					],
				]
			);
			return new \WP_Error( 'newspack_manager_data_report', __( 'Error sending data to Newspack Manager', 'newspack-manager' ) );
		}
	}
}
Data_Report::init();


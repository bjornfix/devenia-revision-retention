<?php
/**
 * Plugin Name: Devenia Revision Retention
 * Description: Keeps recent WordPress revisions plus older anchor revisions so the database stays controlled without losing useful history.
 * Version: 0.1.4
 * Author: Devenia
 * License: GPL-2.0-or-later
 * Text Domain: devenia-revision-retention
 * Requires at least: 6.9
 * Requires PHP: 7.2
 *
 * @package DeveniaRevisionRetention
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DEVENIA_REVISION_RETENTION_VERSION', '0.1.4' );
define( 'DEVENIA_REVISION_RETENTION_OPTION', 'devenia_revision_retention_options' );
define( 'DEVENIA_REVISION_RETENTION_LAST_RUN', 'devenia_revision_retention_last_run' );
define( 'DEVENIA_REVISION_RETENTION_HOOK', 'devenia_revision_retention_cron' );

/**
 * Revision retention controller.
 */
final class Devenia_Revision_Retention {
	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'ensure_schedule' ) );
		add_action( DEVENIA_REVISION_RETENTION_HOOK, array( __CLASS__, 'run_scheduled_prune' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'option_page_capability_devenia_revision_retention', array( __CLASS__, 'settings_capability' ) );
		add_action( 'update_option_' . DEVENIA_REVISION_RETENTION_OPTION, array( __CLASS__, 'after_settings_update' ), 10, 2 );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
	}

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		if ( false === get_option( DEVENIA_REVISION_RETENTION_OPTION, false ) ) {
			add_option( DEVENIA_REVISION_RETENTION_OPTION, self::default_options(), '', false );
		}

		self::reschedule();
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( DEVENIA_REVISION_RETENTION_HOOK );
	}

	/**
	 * Default options.
	 *
	 * @return array
	 */
	private static function default_options() {
		return array(
			'enabled'          => 1,
			'interval_minutes' => 1440,
			'latest_keep'      => 10,
			'anchor_days'      => '7,14,21,28,70',
			'post_types'       => array( 'post', 'page' ),
			'parent_limit'     => 100,
			'delete_limit'     => 500,
		);
	}

	/**
	 * Get sanitized options.
	 *
	 * @return array
	 */
	private static function options() {
		$options = get_option( DEVENIA_REVISION_RETENTION_OPTION, array() );

		return self::sanitize_options( is_array( $options ) ? $options : array() );
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Raw options.
	 * @return array
	 */
	public static function sanitize_options( $input ) {
		$defaults = self::default_options();
		$output   = array();

		$output['enabled']          = empty( $input['enabled'] ) ? 0 : 1;
		$output['interval_minutes'] = max( 60, absint( $input['interval_minutes'] ?? $defaults['interval_minutes'] ) );
		$output['latest_keep']      = max( 1, absint( $input['latest_keep'] ?? $defaults['latest_keep'] ) );
		$output['parent_limit']     = max( 1, min( 1000, absint( $input['parent_limit'] ?? $defaults['parent_limit'] ) ) );
		$output['delete_limit']     = max( 1, min( 5000, absint( $input['delete_limit'] ?? $defaults['delete_limit'] ) ) );
		$output['anchor_days']      = self::sanitize_anchor_days( $input['anchor_days'] ?? $defaults['anchor_days'] );
		$output['post_types']       = self::sanitize_post_types( $input['post_types'] ?? $defaults['post_types'] );

		return $output;
	}

	/**
	 * Sanitize anchor-day list.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function sanitize_anchor_days( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}

		$days = array_filter(
			array_map(
				static function ( $part ) {
					$day = absint( trim( (string) $part ) );
					return $day > 0 ? $day : null;
				},
				explode( ',', (string) $value )
			)
		);

		$days = array_values( array_unique( $days ) );
		sort( $days, SORT_NUMERIC );

		return implode( ',', $days );
	}

	/**
	 * Convert anchor-day option to integers.
	 *
	 * @param array $options Plugin options.
	 * @return array
	 */
	private static function anchor_days( $options ) {
		return array_values(
			array_filter(
				array_map(
					'absint',
					explode( ',', (string) ( $options['anchor_days'] ?? '' ) )
				)
			)
		);
	}

	/**
	 * Sanitize post types.
	 *
	 * @param mixed $value Raw value.
	 * @return array
	 */
	private static function sanitize_post_types( $value ) {
		$value = is_array( $value ) ? $value : array();
		$valid = array();

		foreach ( get_post_types( array(), 'names' ) as $post_type ) {
			if ( post_type_supports( $post_type, 'revisions' ) ) {
				$valid[] = $post_type;
			}
		}

		$selected = array_values( array_intersect( array_map( 'sanitize_key', $value ), $valid ) );

		return empty( $selected ) ? array( 'post', 'page' ) : $selected;
	}

	/**
	 * Register custom cron interval.
	 *
	 * @param array $schedules Cron schedules.
	 * @return array
	 */
	public static function add_cron_schedule( $schedules ) {
		$options = self::options();
		$seconds = max( HOUR_IN_SECONDS, absint( $options['interval_minutes'] ) * MINUTE_IN_SECONDS );

		$schedules['devenia_revision_retention_interval'] = array(
			'interval' => $seconds,
			'display'  => sprintf(
				/* translators: %d: minutes. */
				__( 'Every %d minutes', 'devenia-revision-retention' ),
				absint( $options['interval_minutes'] )
			),
		);

		return $schedules;
	}

	/**
	 * Ensure scheduled task exists.
	 */
	public static function ensure_schedule() {
		$options = self::options();
		if ( empty( $options['enabled'] ) ) {
			wp_clear_scheduled_hook( DEVENIA_REVISION_RETENTION_HOOK );
			return;
		}

		if ( ! wp_next_scheduled( DEVENIA_REVISION_RETENTION_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'devenia_revision_retention_interval', DEVENIA_REVISION_RETENTION_HOOK );
		}
	}

	/**
	 * Reschedule cron task.
	 */
	private static function reschedule() {
		wp_clear_scheduled_hook( DEVENIA_REVISION_RETENTION_HOOK );
		self::ensure_schedule();
	}

	/**
	 * React to settings updates.
	 */
	public static function after_settings_update() {
		self::reschedule();
	}

	/**
	 * Run scheduled prune.
	 */
	public static function run_scheduled_prune() {
		self::run_prune( false );
	}

	/**
	 * Register MCP abilities when the WordPress Abilities API is available.
	 */
	public static function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			'revision-retention/get-settings',
			array(
				'label'               => 'Get Revision Retention Settings',
				'description'         => 'Returns the active revision retention settings, last run, and next scheduled run.',
				'category'            => 'site',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				),
				'output_schema'       => self::ability_output_schema(),
				'execute_callback'    => function () {
					return self::status_payload();
				},
				'permission_callback' => array( __CLASS__, 'ability_permission_callback' ),
				'meta'                => array(
					'annotations' => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);

		wp_register_ability(
			'revision-retention/run',
			array(
				'label'               => 'Run Revision Retention',
				'description'         => 'Runs revision retention. Defaults to dry-run; set dry_run false only when deletion is intended.',
				'category'            => 'site',
				'input_schema'        => array(
					'type'                 => 'object',
					'properties'           => array(
						'dry_run'     => array(
							'type'        => 'boolean',
							'description' => 'When true, report revisions that would be deleted without deleting them. Default true.',
							'default'     => true,
						),
					),
					'additionalProperties' => false,
				),
				'output_schema'       => self::ability_output_schema(),
				'execute_callback'    => function ( $input = array() ) {
					$input   = is_array( $input ) ? $input : array();
					$dry_run = array_key_exists( 'dry_run', $input ) ? (bool) $input['dry_run'] : true;
					$result  = self::run_prune( $dry_run );

					return self::status_payload(
						array(
							'result' => $result,
						)
					);
				},
				'permission_callback' => array( __CLASS__, 'ability_permission_callback' ),
				'meta'                => array(
					'annotations' => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Capability callback for abilities.
	 *
	 * @return bool
	 */
	public static function ability_permission_callback() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Shared broad output schema for revision retention abilities.
	 *
	 * @return array
	 */
	private static function ability_output_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'                => array( 'type' => 'boolean' ),
				'version'                => array( 'type' => 'string' ),
				'settings'               => array( 'type' => 'object' ),
				'last_run'               => array( 'type' => 'object' ),
				'next_scheduled'         => array( 'type' => 'integer' ),
				'next_scheduled_display' => array( 'type' => 'string' ),
				'result'                 => array( 'type' => 'object' ),
			),
		);
	}

	/**
	 * Build a status payload for abilities.
	 *
	 * @param array $extra Extra response fields.
	 * @return array
	 */
	private static function status_payload( $extra = array() ) {
		$next = wp_next_scheduled( DEVENIA_REVISION_RETENTION_HOOK );
		$data = array(
			'success'                => true,
			'version'                => DEVENIA_REVISION_RETENTION_VERSION,
			'settings'               => self::options(),
			'last_run'               => get_option( DEVENIA_REVISION_RETENTION_LAST_RUN, array() ),
			'next_scheduled'         => $next ? (int) $next : 0,
			'next_scheduled_display' => $next ? wp_date( 'Y-m-d H:i:s', (int) $next ) : '',
		);

		return array_merge( $data, $extra );
	}

	/**
	 * Get revision count.
	 *
	 * @return int
	 */
	private static function revision_count() {
		$count = wp_count_posts( 'revision' );
		if ( is_object( $count ) && isset( $count->inherit ) ) {
			return absint( $count->inherit );
		}

		return 0;
	}

	/**
	 * Describe anchor retention in plain language.
	 *
	 * @param array $options Plugin options.
	 * @return string
	 */
	private static function anchor_description( $options ) {
		$parts = array();
		foreach ( self::anchor_days( $options ) as $days ) {
			if ( 0 === $days % 7 ) {
				$weeks = (int) ( $days / 7 );
				$parts[] = sprintf(
					/* translators: %d: number of weeks. */
					_n( '%d week', '%d weeks', $weeks, 'devenia-revision-retention' ),
					$weeks
				);
			} else {
				$parts[] = sprintf(
					/* translators: %d: number of days. */
					_n( '%d day', '%d days', $days, 'devenia-revision-retention' ),
					$days
				);
			}
		}

		return implode( ', ', $parts );
	}

	/**
	 * Format a yes/no value for admin display.
	 *
	 * @param bool $value Value.
	 * @return string
	 */
	private static function yes_no( $value ) {
		return $value ? __( 'Yes', 'devenia-revision-retention' ) : __( 'No', 'devenia-revision-retention' );
	}

	/**
	 * Run revision retention.
	 *
	 * @param bool $dry_run Whether to avoid deletion.
	 * @return array
	 */
	public static function run_prune( $dry_run = false ) {
		$options = self::options();
		$result  = array(
			'time'              => current_time( 'mysql' ),
			'dry_run'           => (bool) $dry_run,
			'post_types'        => $options['post_types'],
			'latest_keep'       => $options['latest_keep'],
			'anchor_days'       => self::anchor_days( $options ),
			'parents_scanned'   => 0,
			'revisions_seen'    => 0,
			'revisions_kept'    => 0,
			'revisions_deleted' => 0,
			'delete_limit'      => $options['delete_limit'],
			'stopped_at_limit'  => false,
			'errors'            => array(),
		);

		$parents = get_posts(
			array(
				'post_type'              => $options['post_types'],
				'post_status'            => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page'         => $options['parent_limit'],
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $parents as $parent_id ) {
			$result['parents_scanned']++;

			$revisions = wp_get_post_revisions(
				$parent_id,
				array(
					'posts_per_page' => -1,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);

			if ( empty( $revisions ) ) {
				continue;
			}

			$result['revisions_seen'] += count( $revisions );
			$keep = self::revision_ids_to_keep( $revisions, $options );
			$result['revisions_kept'] += count( $keep );

			foreach ( $revisions as $revision ) {
				if ( in_array( $revision->ID, $keep, true ) ) {
					continue;
				}

				if ( $result['revisions_deleted'] >= $options['delete_limit'] ) {
					$result['stopped_at_limit'] = true;
					break 2;
				}

				if ( ! $dry_run ) {
					$deleted = wp_delete_post( $revision->ID, true );
					if ( ! $deleted ) {
						$result['errors'][] = sprintf( 'Failed deleting revision %d for parent %d.', $revision->ID, $parent_id );
						continue;
					}
				}

				$result['revisions_deleted']++;
			}
		}

		update_option( DEVENIA_REVISION_RETENTION_LAST_RUN, $result, false );

		return $result;
	}

	/**
	 * Compute revision IDs to retain.
	 *
	 * @param array $revisions Revision posts, newest first.
	 * @param array $options Plugin options.
	 * @return array
	 */
	private static function revision_ids_to_keep( $revisions, $options ) {
		$keep = array();
		$list = array_values( $revisions );

		foreach ( array_slice( $list, 0, absint( $options['latest_keep'] ) ) as $revision ) {
			$keep[] = $revision->ID;
		}

		$now = current_time( 'timestamp' );
		foreach ( self::anchor_days( $options ) as $days ) {
			$target     = $now - ( $days * DAY_IN_SECONDS );
			$best_id    = 0;
			$best_delta = null;

			foreach ( $list as $revision ) {
				$timestamp = mysql2date( 'U', $revision->post_date, false );
				if ( ! $timestamp || $timestamp > $now ) {
					continue;
				}

				$delta = abs( $timestamp - $target );
				if ( null === $best_delta || $delta < $best_delta ) {
					$best_delta = $delta;
					$best_id    = $revision->ID;
				}
			}

			if ( $best_id ) {
				$keep[] = $best_id;
			}
		}

		return array_values( array_unique( $keep ) );
	}

	/**
	 * Settings capability.
	 *
	 * @return string
	 */
	public static function settings_capability() {
		return 'manage_options';
	}

	/**
	 * Add settings page.
	 */
	public static function add_admin_page() {
		add_management_page(
			__( 'Revision Retention', 'devenia-revision-retention' ),
			__( 'Revision Retention', 'devenia-revision-retention' ),
			'manage_options',
			'devenia-revision-retention',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting(
			'devenia_revision_retention',
			DEVENIA_REVISION_RETENTION_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => self::default_options(),
			)
		);
	}

	/**
	 * Render admin page.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage revision retention.', 'devenia-revision-retention' ) );
		}

		$message      = '';
		$message_type = 'success';
		if ( isset( $_POST['devenia_revision_retention_action'] ) ) {
			check_admin_referer( 'devenia_revision_retention_run' );
			$action = sanitize_key( wp_unslash( $_POST['devenia_revision_retention_action'] ) );

			if ( 'dry_run' === $action ) {
				$result  = self::run_prune( true );
				$message = sprintf(
					/* translators: 1: revisions seen, 2: revisions that would be deleted. */
					__( 'Dry-run complete. Saw %1$d revisions; would delete %2$d.', 'devenia-revision-retention' ),
					absint( $result['revisions_seen'] ),
					absint( $result['revisions_deleted'] )
				);
			} elseif ( 'run' === $action ) {
				$confirmed = isset( $_POST['devenia_revision_retention_confirm_run'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['devenia_revision_retention_confirm_run'] ) );
				if ( ! $confirmed ) {
					$message_type = 'error';
					$message      = __( 'Cleanup was not run. Tick the confirmation box first.', 'devenia-revision-retention' );
				} else {
					$result  = self::run_prune( false );
					$message = sprintf(
						/* translators: 1: revisions seen, 2: revisions deleted. */
						__( 'Cleanup complete. Saw %1$d revisions; deleted %2$d.', 'devenia-revision-retention' ),
						absint( $result['revisions_seen'] ),
						absint( $result['revisions_deleted'] )
					);
				}
			}
		}

		$options  = self::options();
		$last_run = get_option( DEVENIA_REVISION_RETENTION_LAST_RUN, array() );
		$next     = wp_next_scheduled( DEVENIA_REVISION_RETENTION_HOOK );
		?>
		<style>
			.drr-grid{display:grid;grid-template-columns:repeat(4,minmax(150px,1fr));gap:12px;max-width:1100px;margin:18px 0}
			.drr-card{background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:14px}
			.drr-card strong{display:block;font-size:22px;line-height:1.2;margin-top:6px}
			.drr-panel{max-width:1100px;background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:18px;margin:18px 0}
			.drr-actions{display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap}
			.drr-danger{margin-top:12px;padding:12px;border-left:4px solid #d63638;background:#fcf0f1;max-width:680px}
			@media (max-width: 960px){.drr-grid{grid-template-columns:repeat(2,minmax(150px,1fr))}}
			@media (max-width: 600px){.drr-grid{grid-template-columns:1fr}}
		</style>
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Revision Retention', 'devenia-revision-retention' ); ?></h1>
			<p><?php esc_html_e( 'Keep useful revision history without letting old revisions grow forever.', 'devenia-revision-retention' ); ?></p>
			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( $message_type ); ?>"><p><?php echo esc_html( $message ); ?></p></div>
			<?php endif; ?>

			<div class="drr-grid">
				<div class="drr-card">
					<span><?php esc_html_e( 'Schedule', 'devenia-revision-retention' ); ?></span>
					<strong><?php echo empty( $options['enabled'] ) ? esc_html__( 'Off', 'devenia-revision-retention' ) : esc_html__( 'On', 'devenia-revision-retention' ); ?></strong>
					<p><?php echo $next ? esc_html( sprintf(
						/* translators: %s: next scheduled cleanup date and time. */
						__( 'Next run: %s', 'devenia-revision-retention' ),
						wp_date( 'Y-m-d H:i', (int) $next )
					) ) : esc_html__( 'No scheduled run.', 'devenia-revision-retention' ); ?></p>
				</div>
				<div class="drr-card">
					<span><?php esc_html_e( 'Revisions now', 'devenia-revision-retention' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( self::revision_count() ) ); ?></strong>
					<p><?php esc_html_e( 'Current revision records in WordPress.', 'devenia-revision-retention' ); ?></p>
				</div>
				<div class="drr-card">
					<span><?php esc_html_e( 'Keep latest', 'devenia-revision-retention' ); ?></span>
					<strong><?php echo esc_html( number_format_i18n( absint( $options['latest_keep'] ) ) ); ?></strong>
					<p><?php esc_html_e( 'Newest revisions per post or page.', 'devenia-revision-retention' ); ?></p>
				</div>
				<div class="drr-card">
					<span><?php esc_html_e( 'Older anchors', 'devenia-revision-retention' ); ?></span>
					<strong><?php echo esc_html( count( self::anchor_days( $options ) ) ); ?></strong>
					<p><?php echo esc_html( self::anchor_description( $options ) ); ?></p>
				</div>
			</div>

			<div class="drr-panel">
				<h2><?php esc_html_e( 'How cleanup works', 'devenia-revision-retention' ); ?></h2>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: latest revision count, 2: anchor description. */
							__( 'For each supported post or page, the plugin keeps the latest %1$d revisions and one older revision closest to each anchor: %2$s.', 'devenia-revision-retention' ),
							absint( $options['latest_keep'] ),
							self::anchor_description( $options )
						)
					);
					?>
				</p>
				<p><?php esc_html_e( 'Run a dry-run first. It shows how many revisions would be deleted without deleting anything.', 'devenia-revision-retention' ); ?></p>
			</div>

			<div class="drr-panel">
				<h2><?php esc_html_e( 'Actions', 'devenia-revision-retention' ); ?></h2>
				<form method="post">
					<?php wp_nonce_field( 'devenia_revision_retention_run' ); ?>
					<div class="drr-actions">
						<button class="button" name="devenia_revision_retention_action" value="dry_run"><?php esc_html_e( 'Run dry-run', 'devenia-revision-retention' ); ?></button>
						<button class="button button-primary" name="devenia_revision_retention_action" value="run"><?php esc_html_e( 'Run cleanup now', 'devenia-revision-retention' ); ?></button>
					</div>
					<div class="drr-danger">
						<label>
							<input type="checkbox" name="devenia_revision_retention_confirm_run" value="yes" />
							<?php esc_html_e( 'I understand that "Run cleanup now" permanently deletes revisions not kept by the policy.', 'devenia-revision-retention' ); ?>
						</label>
					</div>
				</form>
			</div>

			<?php if ( is_array( $last_run ) && ! empty( $last_run ) ) : ?>
				<div class="drr-panel">
					<h2><?php esc_html_e( 'Last run', 'devenia-revision-retention' ); ?></h2>
					<table class="widefat striped" style="max-width:760px">
						<tbody>
							<tr><th scope="row"><?php esc_html_e( 'Time', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( (string) ( $last_run['time'] ?? '' ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Dry-run', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( self::yes_no( ! empty( $last_run['dry_run'] ) ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Parents scanned', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( number_format_i18n( absint( $last_run['parents_scanned'] ?? 0 ) ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Revisions seen', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( number_format_i18n( absint( $last_run['revisions_seen'] ?? 0 ) ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Revisions kept', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( number_format_i18n( absint( $last_run['revisions_kept'] ?? 0 ) ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Revisions deleted', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( number_format_i18n( absint( $last_run['revisions_deleted'] ?? 0 ) ) ); ?></td></tr>
							<tr><th scope="row"><?php esc_html_e( 'Stopped at limit', 'devenia-revision-retention' ); ?></th><td><?php echo esc_html( self::yes_no( ! empty( $last_run['stopped_at_limit'] ) ) ); ?></td></tr>
						</tbody>
					</table>
					<?php if ( ! empty( $last_run['errors'] ) && is_array( $last_run['errors'] ) ) : ?>
						<h3><?php esc_html_e( 'Errors', 'devenia-revision-retention' ); ?></h3>
						<ul>
							<?php foreach ( $last_run['errors'] as $error ) : ?>
								<li><?php echo esc_html( (string) $error ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="drr-panel">
				<h2><?php esc_html_e( 'Settings', 'devenia-revision-retention' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'devenia_revision_retention' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enabled', 'devenia-revision-retention' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[enabled]" value="1" <?php checked( $options['enabled'], 1 ); ?> /> <?php esc_html_e( 'Run scheduled revision retention.', 'devenia-revision-retention' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="drr_latest_keep"><?php esc_html_e( 'Latest revisions to keep', 'devenia-revision-retention' ); ?></label></th>
						<td><input id="drr_latest_keep" type="number" min="1" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[latest_keep]" value="<?php echo esc_attr( $options['latest_keep'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="drr_anchor_days"><?php esc_html_e( 'Anchor days', 'devenia-revision-retention' ); ?></label></th>
						<td><input id="drr_anchor_days" class="regular-text" type="text" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[anchor_days]" value="<?php echo esc_attr( $options['anchor_days'] ); ?>" /><p class="description"><?php esc_html_e( 'Comma-separated days back. Default 7,14,21,28,70 keeps one older revision near 1, 2, 3, 4, and 10 weeks.', 'devenia-revision-retention' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types', 'devenia-revision-retention' ); ?></th>
						<td>
							<?php foreach ( get_post_types( array(), 'objects' ) as $post_type ) : ?>
								<?php if ( ! post_type_supports( $post_type->name, 'revisions' ) ) { continue; } ?>
								<label style="display:block"><input type="checkbox" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $options['post_types'], true ) ); ?> /> <?php echo esc_html( $post_type->labels->singular_name ); ?> <code><?php echo esc_html( $post_type->name ); ?></code></label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="drr_parent_limit"><?php esc_html_e( 'Parent posts per run', 'devenia-revision-retention' ); ?></label></th>
						<td><input id="drr_parent_limit" type="number" min="1" max="1000" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[parent_limit]" value="<?php echo esc_attr( $options['parent_limit'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="drr_delete_limit"><?php esc_html_e( 'Revision delete limit per run', 'devenia-revision-retention' ); ?></label></th>
						<td><input id="drr_delete_limit" type="number" min="1" max="5000" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[delete_limit]" value="<?php echo esc_attr( $options['delete_limit'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="drr_interval_minutes"><?php esc_html_e( 'Interval minutes', 'devenia-revision-retention' ); ?></label></th>
						<td><input id="drr_interval_minutes" type="number" min="60" name="<?php echo esc_attr( DEVENIA_REVISION_RETENTION_OPTION ); ?>[interval_minutes]" value="<?php echo esc_attr( $options['interval_minutes'] ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			</div>
		</div>
		<?php
	}
}

Devenia_Revision_Retention::init();
register_activation_hook( __FILE__, array( 'Devenia_Revision_Retention', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Devenia_Revision_Retention', 'deactivate' ) );

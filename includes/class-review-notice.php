<?php
/**
 * Review Notice Handler
 *
 * Displays an admin notice asking for a review after the user has
 * successfully marked a certain number of orders as "shipped".
 *
 * Usage:
 * 1. Place this file in your plugin folder (e.g. includes/class-review-notice.php)
 * 2. In your main plugin file add:
 *    require_once plugin_dir_path( __FILE__ ) . 'includes/class-review-notice.php';
 *    new MSOT_Review_Notice();
 * 3. Call MSOT_Review_Notice::increment_shipped_count(); wherever an order
 *    is successfully marked as "shipped" in your code.
 *
 * All user-facing strings use the 'milans-shipped-order-tracking-for-woo' text domain
 * and are wrapped in translation functions, so the file is ready for a .pot
 * file and translation via WordPress.org's translation platform (or Loco
 * Translate / Poedit).
 *
 * @package Milans_Shipped_Order_Tracking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

class MSOT_Review_Notice {

	/**
	 * Text domain used for all translations in this class.
	 * Should match the Text Domain header in your main plugin file.
	 *
	 * @var string
	 */
	const TEXT_DOMAIN = 'milans-shipped-order-tracking-for-woo';

	/**
	 * Number of successfully shipped orders after which the notice is shown.
	 *
	 * @var int
	 */
	private $threshold = 20;

	/**
	 * Number of days to snooze the notice when the user clicks "Remind me later".
	 *
	 * @var int
	 */
	private $snooze_days = 14;

	/**
	 * Plugin slug, used to build the review URL and option names.
	 *
	 * @var string
	 */
	private $plugin_slug = 'milans-shipped-order-tracking-for-woo';

	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_notice_actions' ) );
	}

	/**
	 * Call this static method wherever an order is successfully marked as
	 * "shipped" (e.g. in your is_customer_email() / status change logic).
	 */
	public static function increment_shipped_count() {
		$count = (int) get_option( 'msot_shipped_count', 0 );
		update_option( 'msot_shipped_count', $count + 1 );
	}

	/**
	 * Checks whether the notice should be displayed.
	 */
	public function maybe_show_notice() {

		// Don't show if the user already responded permanently (rated or dismissed for good).
		if ( get_option( 'msot_review_dismissed_forever' ) ) {
			return;
		}

		// Don't show while we're in a "snooze" period.
		$snoozed_until = (int) get_option( 'msot_review_snoozed_until', 0 );
		if ( $snoozed_until && time() < $snoozed_until ) {
			return;
		}

		// Check whether the shipped-order threshold has been reached.
		$shipped_count = (int) get_option( 'msot_shipped_count', 0 );
		if ( $shipped_count < $this->threshold ) {
			return;
		}

		// Only show to users who can manage plugins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->render_notice( $shipped_count );
	}

	/**
	 * Renders the notice HTML with action buttons.
	 *
	 * @param int $shipped_count Current number of successfully shipped orders.
	 */
	private function render_notice( $shipped_count ) {

		$review_url = sprintf(
			'https://wordpress.org/support/plugin/%s/reviews/#new-post',
			$this->plugin_slug
		);

		$base_url = ( isset( $_SERVER['REQUEST_URI'] ) ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();
		?>
		<div class="notice notice-info is-dismissible msot-review-notice">
			<p>
				<strong>🚀 <?php esc_html_e( 'Nice work!', 'milans-shipped-order-tracking-for-woo' ); ?></strong>
				<?php
				printf(
					/* translators: %d: number of successfully shipped orders */
					esc_html__( 'You have successfully shipped %d orders using Milans Shipped Order Tracking. If the plugin has been useful to you, leaving a review on WordPress.org would really help me keep improving it.', 'milans-shipped-order-tracking-for-woo' ),
					intval( $shipped_count )
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary msot-leave-review-link" data-dismiss-url="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msot_review_action' => 'already_rated' ), $base_url ), 'msot_review_notice_action', 'msot_nonce' ) ); ?>">
    <?php esc_html_e( 'Sure, happy to leave a review', 'milans-shipped-order-tracking-for-woo' ); ?> ⭐
</a>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var link = document.querySelector('.msot-leave-review-link');
	if (link) {
		link.addEventListener('click', function () {
			fetch(link.getAttribute('data-dismiss-url'), { credentials: 'same-origin' });
		});
	}
});
</script>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msot_review_action' => 'already_rated' ), $base_url ), 'msot_review_notice_action', 'msot_nonce' ) ); ?>" class="button">
					<?php esc_html_e( 'I already left a review', 'milans-shipped-order-tracking-for-woo' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msot_review_action' => 'later' ), $base_url ), 'msot_review_notice_action', 'msot_nonce' ) ); ?>" class="button">
					<?php esc_html_e( 'Remind me later', 'milans-shipped-order-tracking-for-woo' ); ?>
				</a>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'msot_review_action' => 'never' ), $base_url ), 'msot_review_notice_action', 'msot_nonce' ) ); ?>" class="button-link">
					<?php esc_html_e( 'No, thanks', 'milans-shipped-order-tracking-for-woo' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles clicks on the notice buttons (already_rated / later / never).
	 */
	public function handle_notice_actions() {

		if ( empty( $_GET['msot_review_action'] ) || empty( $_GET['msot_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_GET['msot_nonce'] ), 'msot_review_notice_action' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_GET['msot_review_action'] );

		switch ( $action ) {

			case 'already_rated':
			case 'never':
				update_option( 'msot_review_dismissed_forever', 1 );
				break;

			case 'later':
				update_option( 'msot_review_snoozed_until', time() + ( $this->snooze_days * DAY_IN_SECONDS ) );
				break;
		}

		// Strip query args from the URL and redirect (clean redirect, no leftover action in the URL).
		wp_safe_redirect( remove_query_arg( array( 'msot_review_action', 'msot_nonce' ) ) );
		exit;
	}
}
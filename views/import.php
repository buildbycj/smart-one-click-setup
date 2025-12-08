<?php
namespace SMARTOCS;
/**
 * The import page view.
 *
 * @package smartocs
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="smartocs smartocs--import">

	<?php echo wp_kses_post( ViewHelpers::plugin_header_output() ); ?>

	<div class="smartocs__content-container">

		<div class="smartocs__admin-notices js-smartocs-admin-notices-container"></div>

		<div class="smartocs__content-container-content">
			<div class="smartocs__content-container-content--main">
				<?php
				// Verify nonce and user capability for GET parameter.
				$smartocs_import_index = null;
				if ( isset( $_GET['import'] ) ) {
					// Check nonce and user capability.
					if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'smartocs-import-action' ) && current_user_can( 'import' ) ) {
						$smartocs_import_index = absint( wp_unslash( $_GET['import'] ) );
					}
				}

				if ( null !== $smartocs_import_index ) :
					if ( ! empty( $this->import_files[ $smartocs_import_index ]['import_notice'] ) ) :
						?>
						<div class="notice notice-info">
							<p><?php echo wp_kses_post( $this->import_files[ $smartocs_import_index ]['import_notice'] ); ?></p>
						</div>
					<?php endif; ?>
					<div class="js-smartocs-auto-start-import"></div>
				<?php else : ?>
					<div class="js-smartocs-auto-start-manual-import"></div>
				<?php endif; ?>

				<div class="smartocs-importing js-smartocs-importing">
					<div class="smartocs-importing-header">
						<h2><?php esc_html_e( 'Importing Content' , 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please sit tight while we import your content. Do not refresh the page or hit the back button.' , 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="smartocs-importing-content">
						<img class="smartocs-importing-content-importing" src="<?php echo esc_url( SMARTOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Importing animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="smartocs-imported js-smartocs-imported">
					<div class="smartocs-imported-header">
						<h2 class="js-smartocs-ajax-response-title"><?php esc_html_e( 'Import Complete!' , 'smart-one-click-setup' ); ?></h2>
						<div class="js-smartocs-ajax-response-subtitle">
							<p>
								<?php esc_html_e( 'Congrats, your demo was imported successfully. You can now begin editing your site.' , 'smart-one-click-setup' ); ?>
							</p>
						</div>
					</div>
					<div class="smartocs-imported-content">
						<div class="smartocs__response  js-smartocs-ajax-response"></div>
					</div>
					<div class="smartocs-imported-footer">
						<?php echo wp_kses(
							$this->get_import_successful_buttons_html(),
							[
								'a' => [
									'href'   => [],
									'class'  => [],
									'target' => [],
								],
							]
						); ?>
					</div>
				</div>
			</div>
			<div class="smartocs__content-container-content--side">
				<?php
					// Verify nonce and user capability for GET parameter.
					$smartocs_selected = null;
					if ( isset( $_GET['import'] ) ) {
						// Check nonce and user capability.
						if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'smartocs-import-action' ) && current_user_can( 'import' ) ) {
							$smartocs_selected = absint( wp_unslash( $_GET['import'] ) );
						}
					}
					echo wp_kses_post( ViewHelpers::small_theme_card( $smartocs_selected ) );
				?>
			</div>
		</div>

	</div>
</div>

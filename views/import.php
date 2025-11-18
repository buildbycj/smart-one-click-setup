<?php
/**
 * The import page view.
 *
 * @package socs
 */

namespace SOCS;
?>

<div class="socs socs--import">

	<?php echo wp_kses_post( ViewHelpers::plugin_header_output() ); ?>

	<div class="socs__content-container">

		<div class="socs__admin-notices js-socs-admin-notices-container"></div>

		<div class="socs__content-container-content">
			<div class="socs__content-container-content--main">
				<?php
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( isset( $_GET['import'] ) ) :
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$socs_import_index = absint( wp_unslash( $_GET['import'] ) );
					if ( ! empty( $this->import_files[ $socs_import_index ]['import_notice'] ) ) :
						?>
						<div class="notice notice-info">
							<p><?php echo wp_kses_post( $this->import_files[ $socs_import_index ]['import_notice'] ); ?></p>
						</div>
					<?php endif; ?>
					<div class="js-socs-auto-start-import"></div>
				<?php else : ?>
					<div class="js-socs-auto-start-manual-import"></div>
				<?php endif; ?>

				<div class="socs-importing js-socs-importing">
					<div class="socs-importing-header">
						<h2><?php esc_html_e( 'Importing Content' , 'smart-one-click-setup' ); ?></h2>
						<p><?php esc_html_e( 'Please sit tight while we import your content. Do not refresh the page or hit the back button.' , 'smart-one-click-setup' ); ?></p>
					</div>
					<div class="socs-importing-content">
						<img class="socs-importing-content-importing" src="<?php echo esc_url( SOCS_URL . 'assets/images/importing.svg' ); ?>" alt="<?php esc_attr_e( 'Importing animation', 'smart-one-click-setup' ); ?>">
					</div>
				</div>

				<div class="socs-imported js-socs-imported">
					<div class="socs-imported-header">
						<h2 class="js-socs-ajax-response-title"><?php esc_html_e( 'Import Complete!' , 'smart-one-click-setup' ); ?></h2>
						<div class="js-socs-ajax-response-subtitle">
							<p>
								<?php esc_html_e( 'Congrats, your demo was imported successfully. You can now begin editing your site.' , 'smart-one-click-setup' ); ?>
							</p>
						</div>
					</div>
					<div class="socs-imported-content">
						<div class="socs__response  js-socs-ajax-response"></div>
					</div>
					<div class="socs-imported-footer">
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
			<div class="socs__content-container-content--side">
				<?php
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$socs_selected = isset( $_GET['import'] ) ? absint( wp_unslash( $_GET['import'] ) ) : null;
					echo wp_kses_post( ViewHelpers::small_theme_card( $socs_selected ) );
				?>
			</div>
		</div>

	</div>
</div>

<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Resolve and boot optional Events modules without making the foundation optional. */
class Module_Registry {
	private $registered = [];
	private $loaded     = [];
	private $errors     = [];

	public function boot( Plugin $plugin ) {
		$modules = (array) apply_filters( 'cinderwell_events_modules', [] );
		foreach ( $modules as $module ) {
			if ( $module instanceof Module_Interface ) {
				$this->registered[ sanitize_key( $module->get_slug() ) ] = $module;
			}
		}

		$pending = $this->registered;
		do {
			$progress = false;
			foreach ( $pending as $slug => $module ) {
				$dependencies = array_map( 'sanitize_key', (array) $module->get_dependencies() );
				$missing      = array_diff( $dependencies, array_keys( $this->registered ) );
				if ( $missing ) {
					$this->errors[ $slug ] = sprintf(
						/* translators: %s: module slugs. */
						__( 'Missing module dependencies: %s', 'cinderwell-events' ),
						implode( ', ', $missing )
					);
					unset( $pending[ $slug ] );
					continue;
				}
				if ( array_diff( $dependencies, array_keys( $this->loaded ) ) ) {
					continue;
				}
				$module->register( $plugin );
				$this->loaded[ $slug ] = $module;
				unset( $pending[ $slug ] );
				$progress = true;
			}
		} while ( $pending && $progress );

		foreach ( $pending as $slug => $module ) {
			$this->errors[ $slug ] = __( 'Circular or unresolved module dependency.', 'cinderwell-events' );
		}

		if ( $this->errors ) {
			add_action( 'admin_notices', [ $this, 'render_errors' ] );
		}
		do_action( 'cinderwell_events_modules_loaded', $this->loaded, $plugin );
	}

	public function render_errors() {
		foreach ( $this->errors as $slug => $message ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( '%s: %s', $slug, $message ) ) . '</p></div>';
		}
	}

	public function is_loaded( $slug ) {
		return isset( $this->loaded[ sanitize_key( $slug ) ] );
	}
}


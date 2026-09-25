<?php
namespace Cinderwell_Events;

defined( 'ABSPATH' ) || exit;

/** Contract for optional Events feature modules. */
interface Module_Interface {
	public function get_slug();
	public function get_dependencies();
	public function register( Plugin $plugin );
}


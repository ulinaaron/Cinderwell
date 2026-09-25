<?php
/**
 * Dependency-free test for Extension_API. Run: php tests/extension-api-test.php
 */

define( 'ABSPATH', __DIR__ );
define( 'CINDERWELL_DIR', dirname( __DIR__ ) . '/' );

$GLOBALS['cw_filters'] = [];
$GLOBALS['cw_child']   = sys_get_temp_dir() . '/cw-test-child';
$GLOBALS['cw_parent']  = sys_get_temp_dir() . '/cw-test-parent';

function add_action() {}
function add_filter( $tag, $fn ) { $GLOBALS['cw_filters'][ $tag ][] = $fn; }
function apply_filters( $tag, $value, ...$args ) {
    foreach ( $GLOBALS['cw_filters'][ $tag ] ?? [] as $fn ) {
        $value = $fn( $value, ...$args );
    }
    return $value;
}
function esc_html( $s ) { return htmlspecialchars( $s, ENT_QUOTES ); }
function get_stylesheet_directory() { return $GLOBALS['cw_child']; }
function get_template_directory() { return $GLOBALS['cw_parent']; }

require dirname( __DIR__ ) . '/includes/class-extension-api.php';

function put( $dir, $block, $html ) {
    @mkdir( "$dir/cinderwell/$block", 0777, true );
    file_put_contents( "$dir/cinderwell/$block/index.html", $html );
}
function check( $label, $ok ) {
    echo ( $ok ? 'PASS' : 'FAIL' ) . " $label\n";
    if ( ! $ok ) { $GLOBALS['cw_failed'] = true; }
}

foreach ( [ $GLOBALS['cw_child'], $GLOBALS['cw_parent'] ] as $d ) {
    shell_exec( 'rm -rf ' . escapeshellarg( $d ) );
}
put( $GLOBALS['cw_child'], 'hero', '<h1>{{title}}</h1>{{content}}' );
put( $GLOBALS['cw_parent'], 'hero', '<p>parent</p>' );
put( $GLOBALS['cw_parent'], 'cta', '<a>{{label}}</a>' );

$api  = new Cinderwell\Extension_API();
$api->register_hooks();
$meta = fn( $n ) => [ 'name' => "cinderwell/$n" ];

// Child theme wins over parent; attributes (escaped) and content are used.
$s  = $api->maybe_override_render( [], $meta( 'hero' ) );
$cb = $s['render_callback'];
check( 'child theme override wins, attrs and content rendered',
    $cb( [ 'title' => 'A<b>' ], '<i>x</i>' ) === '<h1>A&lt;b&gt;</h1><i>x</i>' );
check( 'instances differ', $cb( [ 'title' => 'B' ], '' ) !== $cb( [ 'title' => 'A' ], '' ) );

// Parent fallback.
$s = $api->maybe_override_render( [], $meta( 'cta' ) );
check( 'parent theme fallback', $s['render_callback']( [ 'label' => 'Go' ], '' ) === '<a>Go</a>' );

// No override: settings untouched.
check( 'no override leaves settings alone', $api->maybe_override_render( [], $meta( 'none' ) ) === [] );
check( 'non-cinderwell block ignored', $api->maybe_override_render( [], [ 'name' => 'core/paragraph' ] ) === [] );

// Render filter fires without an override file.
add_filter( 'cinderwell_render_none', fn( $html, $attrs ) => $html . $attrs['x'] );
$out = apply_filters( 'render_block', '<div></div>', [ 'blockName' => 'cinderwell/none', 'attrs' => [ 'x' => '!' ] ] );
check( 'render filter fires with no override', $out === '<div></div>!' );
check( 'render filter skips other blocks', apply_filters( 'render_block', 'z', [ 'blockName' => 'core/x', 'attrs' => [] ] ) === 'z' );

exit( empty( $GLOBALS['cw_failed'] ) ? 0 : 1 );

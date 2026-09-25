const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const fs = require( 'fs' );
const CopyPlugin = require( 'copy-webpack-plugin' );

// Auto-discover block and atom entry points.
const getEntryPoints = () => {
	const entryPoints = {};

	// Public capability styles. Blocks opt into the layers they can render.
	[ 'base', 'actions', 'responsive', 'media', 'forms', 'commerce' ].forEach(
		( capability ) => {
			entryPoints[ `shared/${ capability }` ] = path.resolve(
				__dirname,
				`src/shared/${ capability }.css`
			);
		}
	);

	// Facets pair their conditionally loaded presentation with a small
	// progressive-enhancement script in one build entry.
	entryPoints[ 'shared/facets' ] = path.resolve(
		__dirname,
		'src/shared/facets.js'
	);

	// Gutenberg editor-level tools.
	entryPoints[ 'editor/index' ] = path.resolve(
		__dirname,
		'src/editor/index.js'
	);
	entryPoints[ 'admin/settings' ] = path.resolve(
		__dirname,
		'src/admin/settings.js'
	);
	entryPoints[ 'admin/location-map' ] = path.resolve(
		__dirname,
		'src/admin/location-map.js'
	);

	// Optional core modules. Their assets are built with core but enqueued only
	// while the corresponding module is enabled.
	const modulesDir = path.resolve( __dirname, 'src/modules' );
	if ( fs.existsSync( modulesDir ) ) {
		fs.readdirSync( modulesDir ).forEach( ( module ) => {
			const stylePath = path.join( modulesDir, module, 'frontend.css' );
			const scriptPath = path.join( modulesDir, module, 'frontend.js' );
			if ( fs.existsSync( scriptPath ) ) {
				entryPoints[ `modules/${ module }/frontend` ] = scriptPath;
			} else if ( fs.existsSync( stylePath ) ) {
				entryPoints[ `modules/${ module }/frontend` ] = stylePath;
			}
		} );
	}

	// Blocks.
	const blocksDir = path.resolve( __dirname, 'src/blocks' );
	fs.readdirSync( blocksDir ).forEach( ( block ) => {
		const indexPath = path.join( blocksDir, block, 'index.js' );
		if ( fs.existsSync( indexPath ) ) {
			entryPoints[ `blocks/${ block }/index` ] = indexPath;
		}

		const viewPath = path.join( blocksDir, block, 'view.js' );
		if ( fs.existsSync( viewPath ) ) {
			entryPoints[ `blocks/${ block }/view` ] = viewPath;
		}
	} );

	// Atoms.
	const atomsDir = path.resolve( __dirname, 'src/atoms' );
	fs.readdirSync( atomsDir ).forEach( ( atom ) => {
		const indexPath = path.join( atomsDir, atom, 'index.js' );
		if ( fs.existsSync( indexPath ) ) {
			entryPoints[ `atoms/${ atom }/index` ] = indexPath;
		}

		const viewPath = path.join( atomsDir, atom, 'view.js' );
		if ( fs.existsSync( viewPath ) ) {
			entryPoints[ `atoms/${ atom }/view` ] = viewPath;
		}
	} );

	return entryPoints;
};

// Collect copy patterns for block.json, style.css, editor.css, render.php.
const getCopyPatterns = () => {
	const patterns = [];

	// Blocks.
	const blocksDir = path.resolve( __dirname, 'src/blocks' );
	fs.readdirSync( blocksDir ).forEach( ( block ) => {
		const blockDir = path.join( blocksDir, block );
		[ 'block.json', 'style.css', 'editor.css', 'render.php' ].forEach(
			( file ) => {
				const filePath = path.join( blockDir, file );
				if ( fs.existsSync( filePath ) ) {
					patterns.push( {
						from: filePath,
						to: path.resolve(
							__dirname,
							'build/blocks',
							block,
							file
						),
					} );
				}
			}
		);
	} );

	// Atoms.
	const atomsDir = path.resolve( __dirname, 'src/atoms' );
	fs.readdirSync( atomsDir ).forEach( ( atom ) => {
		const atomDir = path.join( atomsDir, atom );
		[ 'block.json', 'style.css', 'editor.css' ].forEach( ( file ) => {
			const filePath = path.join( atomDir, file );
			if ( fs.existsSync( filePath ) ) {
				patterns.push( {
					from: filePath,
					to: path.resolve( __dirname, 'build/atoms', atom, file ),
				} );
			}
		} );
	} );

	// Shared editor CSS.
	patterns.push( {
		from: path.resolve( __dirname, 'src/shared/editor-layout.css' ),
		to: path.resolve( __dirname, 'build/shared/editor-layout.css' ),
	} );
	patterns.push( {
		from: path.resolve( __dirname, 'src/shared/editor-controls.css' ),
		to: path.resolve( __dirname, 'build/shared/editor-controls.css' ),
	} );
	patterns.push( {
		from: path.resolve( __dirname, 'node_modules/leaflet/dist/leaflet.css' ),
		to: path.resolve( __dirname, 'build/vendor/leaflet.css' ),
	} );

	return patterns;
};

module.exports = {
	...defaultConfig,
	entry: getEntryPoints(),
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyPlugin( {
			patterns: getCopyPatterns(),
		} ),
	],
};

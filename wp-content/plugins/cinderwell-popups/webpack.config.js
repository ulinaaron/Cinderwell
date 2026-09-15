const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const fs = require( 'fs' );
const path = require( 'path' );

const blocksDirectory = path.resolve( __dirname, 'src/blocks' );
const entries = {
	'popup-controller': path.resolve(
		__dirname,
		'src/shared/popup-controller.js'
	),
	'button-actions': path.resolve( __dirname, 'src/shared/button-actions.js' ),
};
const copyPatterns = [];

fs.readdirSync( blocksDirectory ).forEach( ( block ) => {
	const blockDirectory = path.join( blocksDirectory, block );
	const entry = path.join( blockDirectory, 'index.js' );
	if ( fs.existsSync( entry ) ) {
		entries[ `blocks/${ block }/index` ] = entry;
	}
	copyPatterns.push( {
		from: blockDirectory,
		to: path.resolve( __dirname, 'build/blocks', block ),
		filter: ( resource ) => ! resource.endsWith( 'index.js' ),
	} );
} );

module.exports = {
	...defaultConfig,
	entry: entries,
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyPlugin( { patterns: copyPatterns } ),
	],
};

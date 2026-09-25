const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'blocks/upcoming-sessions/index': path.resolve(
			__dirname,
			'src/blocks/upcoming-sessions/index.js'
		),
		'editor/sessions-panel': path.resolve(
			__dirname,
			'src/editor/sessions-panel.js'
		),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'build' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins,
		new CopyPlugin( {
			patterns: [
				{
					from: path.resolve(
						__dirname,
						'src/blocks/upcoming-sessions'
					),
					to: path.resolve(
						__dirname,
						'build/blocks/upcoming-sessions'
					),
					filter: ( resource ) => ! resource.endsWith( 'index.js' ),
				},
			],
		} ),
	],
};

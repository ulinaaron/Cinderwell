const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const fs = require( 'fs' );
const CopyPlugin = require( 'copy-webpack-plugin' );

// Auto-discover block and atom entry points.
const getEntryPoints = () => {
    const entryPoints = {};

    // Public capability styles. Blocks opt into the layers they can render.
    [ 'base', 'actions', 'responsive', 'media' ].forEach( ( capability ) => {
        entryPoints[ `shared/${ capability }` ] = path.resolve( __dirname, `src/shared/${ capability }.css` );
    } );

    // Gutenberg editor-level tools.
    entryPoints[ 'editor/index' ] = path.resolve( __dirname, 'src/editor/index.js' );

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
        [ 'block.json', 'style.css', 'editor.css', 'render.php' ].forEach( ( file ) => {
            const filePath = path.join( blockDir, file );
            if ( fs.existsSync( filePath ) ) {
                patterns.push( {
                    from: filePath,
                    to: path.resolve( __dirname, 'build/blocks', block, file ),
                } );
            }
        } );
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

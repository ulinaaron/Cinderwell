#!/usr/bin/env node

import {
	cpSync,
	existsSync,
	mkdirSync,
	mkdtempSync,
	readFileSync,
	rmSync,
	writeFileSync,
} from 'node:fs';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repoRoot = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const outputDirectory = join( repoRoot, 'dist' );
const baseUrl = ( process.env.CINDERWELL_UPDATE_BASE_URL || 'https://cinderwell-updates.surge.sh' ).replace( /\/$/, '' );

const components = [
	{
		type: 'plugin',
		name: 'Cinderwell',
		slug: 'cinderwell',
		source: join( repoRoot, 'wp-content/plugins/cinderwell' ),
		header: 'cinderwell.php',
		pluginFile: 'cinderwell/cinderwell.php',
		exclude: new Set( [ '.gitignore', 'AGENTS.md', 'node_modules', 'src', 'package.json', 'package-lock.json', 'webpack.config.js' ] ),
	},
	{
		type: 'addon',
		name: 'Cinderwell Alerts',
		slug: 'cinderwell-alerts',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-alerts' ),
		header: 'cinderwell-alerts.php',
		pluginFile: 'cinderwell-alerts/cinderwell-alerts.php',
		exclude: new Set(),
	},
	{
		type: 'theme',
		name: 'Cinderwell Base',
		slug: 'cinderwell-starter',
		source: join( repoRoot, 'wp-content/themes/cinderwell-starter' ),
		header: 'style.css',
		exclude: new Set(),
	},
];

const parseHeader = ( file, field ) => {
	const contents = readFileSync( file, 'utf8' );
	const match = contents.match( new RegExp( `^\\s*(?:\\*\\s*)?${ field }:\\s*(.+?)\\s*$`, 'mi' ) );

	if ( ! match ) {
		throw new Error( `Missing ${ field } header in ${ file }` );
	}

	return match[ 1 ].trim();
};

const sha256 = ( file ) => createHash( 'sha256' ).update( readFileSync( file ) ).digest( 'hex' );

const copyComponent = ( component, destination ) => {
	cpSync( component.source, destination, {
		recursive: true,
		filter: ( source ) => {
			const relative = source.slice( component.source.length + 1 );
			const firstSegment = relative.split( /[\\/]/ )[ 0 ];

			return ! component.exclude.has( firstSegment ) && ! source.endsWith( '.map' );
		},
	} );
};

rmSync( outputDirectory, { force: true, recursive: true } );
mkdirSync( join( outputDirectory, 'packages' ), { recursive: true } );

const manifest = {
	generated_at: new Date().toISOString(),
	plugin: null,
	addons: {},
	theme: null,
};

for ( const component of components ) {
	if ( ! existsSync( component.source ) ) {
		throw new Error( `Missing component directory: ${ component.source }` );
	}

	if ( component.type === 'plugin' && ! existsSync( join( component.source, 'build' ) ) ) {
		throw new Error( 'Cinderwell build directory is missing. Run npm run build before packaging.' );
	}

	const version = parseHeader( join( component.source, component.header ), 'Version' );
	const stagingRoot = mkdtempSync( join( tmpdir(), 'cinderwell-release-' ) );
	const stagedComponent = join( stagingRoot, component.slug );
	const archiveName = `${ component.slug }-${ version }.zip`;
	const archivePath = join( outputDirectory, 'packages', archiveName );

	copyComponent( component, stagedComponent );
	execFileSync( 'zip', [ '-q', '-r', archivePath, component.slug ], { cwd: stagingRoot } );
	rmSync( stagingRoot, { force: true, recursive: true } );

	const data = {
		name: component.name,
		slug: component.slug,
		version,
		homepage: 'https://github.com/ulinaaron/Cinderwell',
		download_url: `${ baseUrl }/packages/${ archiveName }`,
		sha256: sha256( archivePath ),
	};

	if ( component.type === 'theme' ) {
		data.requires = parseHeader( join( component.source, component.header ), 'Requires at least' );
		data.requires_php = parseHeader( join( component.source, component.header ), 'Requires PHP' );
		manifest.theme = data;
	} else {
		data.plugin_file = component.pluginFile;
		data.requires = parseHeader( join( component.source, component.header ), 'Requires at least' );
		data.requires_php = parseHeader( join( component.source, component.header ), 'Requires PHP' );
		data.sections = {
			description: component.type === 'plugin'
				? 'Cinderwell block system and extension layer.'
				: 'Scheduled, condition-aware alert bars composed with Cinderwell blocks.',
		};

		if ( component.type === 'plugin' ) {
			manifest.plugin = data;
		} else {
			manifest.addons[ component.slug ] = data;
		}
	}
}

writeFileSync( join( outputDirectory, 'info.json' ), `${ JSON.stringify( manifest, null, 2 ) }\n` );
writeFileSync(
	join( outputDirectory, 'index.html' ),
	'<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Cinderwell Updates</title><body><h1>Cinderwell Updates</h1><p>WordPress release service.</p></body></html>\n'
);

console.log( `Built ${ components.length } packages in ${ outputDirectory }` );
for ( const component of components ) {
	console.log( `- ${ component.name } ${ parseHeader( join( component.source, component.header ), 'Version' ) }` );
}

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
		description: 'Scheduled, condition-aware alerts composed with Cinderwell blocks.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Cookie Consent',
		slug: 'cinderwell-cookie-consent',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-cookie-consent' ),
		header: 'cinderwell-cookie-consent.php',
		pluginFile: 'cinderwell-cookie-consent/cinderwell-cookie-consent.php',
		exclude: new Set(),
		description: 'Accessible category consent, prior blocking, withdrawal controls, and privacy-policy guidance.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Help',
		slug: 'cinderwell-help',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-help' ),
		header: 'cinderwell-help.php',
		pluginFile: 'cinderwell-help/cinderwell-help.php',
		exclude: new Set(),
		description: 'Client-facing, searchable presentation for package-owned Cinderwell documentation.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Performance',
		slug: 'cinderwell-performance',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-performance' ),
		header: 'cinderwell-performance.php',
		pluginFile: 'cinderwell-performance/cinderwell-performance.php',
		exclude: new Set( [ 'docs', 'node_modules', 'package.json', 'package-lock.json' ] ),
		description: 'Block-aware HTML, image, resource-hint, and optional hydration optimizations.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Popups',
		slug: 'cinderwell-popups',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-popups' ),
		header: 'cinderwell-popups.php',
		pluginFile: 'cinderwell-popups/cinderwell-popups.php',
		exclude: new Set( [ 'docs', 'node_modules', 'package.json', 'package-lock.json', 'src', 'webpack.config.js' ] ),
		description: 'Accessible block-built modals with automatic display rules and manual button triggers.',
		requiresBuild: true,
	},
	{
		type: 'addon',
		name: 'Cinderwell Members Portal',
		slug: 'cinderwell-portal',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-portal' ),
		header: 'cinderwell-portal.php',
		pluginFile: 'cinderwell-portal/cinderwell-portal.php',
		exclude: new Set( [ 'docs', 'node_modules', 'package.json', 'package-lock.json', 'src', 'webpack.config.js' ] ),
		description: 'Private member content, moderated registration, member profiles, and administrator-issued accounts.',
		requiresBuild: true,
	},
	{
		type: 'addon',
		name: 'Cinderwell Events',
		slug: 'cinderwell-events',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-events' ),
		header: 'cinderwell-events.php',
		pluginFile: 'cinderwell-events/cinderwell-events.php',
		exclude: new Set( [ 'docs', 'node_modules', 'package.json', 'package-lock.json', 'src', 'webpack.config.js' ] ),
		description: 'Session-first Event publishing with chronological listings and independently editable occurrences.',
		requiresBuild: true,
	},
	{
		type: 'addon',
		name: 'Cinderwell SEO',
		slug: 'cinderwell-seo',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-seo' ),
		header: 'cinderwell-seo.php',
		pluginFile: 'cinderwell-seo/cinderwell-seo.php',
		exclude: new Set( [ 'node_modules', 'package.json', 'package-lock.json', 'src', 'webpack.config.js' ] ),
		description: 'Accessible search metadata, explainable content analysis, social previews, schema, and native sitemap controls.',
		requiresBuild: true,
	},
	{
		type: 'addon',
		name: 'Cinderwell Forms',
		slug: 'cinderwell-forms',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-forms' ),
		header: 'cinderwell-forms.php',
		pluginFile: 'cinderwell-forms/cinderwell-forms.php',
		exclude: new Set(),
		description: 'Accessible form building, stored submissions, notifications, privacy tools, and spam protection.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Snippet Manager',
		slug: 'cinderwell-snippets',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-snippets' ),
		header: 'cinderwell-snippets.php',
		pluginFile: 'cinderwell-snippets/cinderwell-snippets.php',
		exclude: new Set( [ 'node_modules', 'package.json' ] ),
		description: 'Administrator-only PHP, JavaScript, CSS, and HTML snippets with conditions, Safe Mode, and fatal recovery.',
	},
	{
		type: 'addon',
		name: 'Cinderwell Site Utilities',
		slug: 'cinderwell-utilities',
		source: join( repoRoot, 'wp-content/plugins/cinderwell-utilities' ),
		header: 'cinderwell-utilities.php',
		pluginFile: 'cinderwell-utilities/cinderwell-utilities.php',
		exclude: new Set( [ 'docs', 'node_modules', 'package.json', 'package-lock.json', 'src', 'templates', 'vendor' ] ),
		description: 'Optional content, media, and site-administration utilities for Cinderwell sites.',
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

	if ( ( component.type === 'plugin' || component.requiresBuild ) && ! existsSync( join( component.source, 'build' ) ) ) {
		throw new Error( `${ component.name } build directory is missing. Run npm run build before packaging.` );
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
				: component.description,
		};
		if ( component.type === 'addon' ) {
			data.distribution = 'plugin';
		}

		if ( component.type === 'plugin' ) {
			manifest.plugin = data;
		} else {
			manifest.addons[ component.slug ] = data;
		}
	}
}

manifest.addons[ 'teams' ] = {
	name: 'Teams',
	slug: 'teams',
	module: 'teams',
	distribution: 'bundled',
	version: manifest.plugin.version,
	requires: manifest.plugin.requires,
	requires_php: manifest.plugin.requires_php,
	sections: {
		description: 'Manage people, team categories, optional profile pages, and focused People loops.',
	},
};

manifest.addons[ 'portfolio' ] = {
	name: 'Portfolio',
	slug: 'portfolio',
	module: 'portfolio',
	distribution: 'bundled',
	version: manifest.plugin.version,
	requires: manifest.plugin.requires,
	requires_php: manifest.plugin.requires_php,
	sections: {
		description: 'Manage project work, configurable fields, categories, templates, and focused Portfolio loops.',
	},
};

manifest.addons[ 'company-details' ] = {
	name: 'Company Details',
	slug: 'company-details',
	module: 'company-details',
	distribution: 'bundled',
	version: manifest.plugin.version,
	requires: manifest.plugin.requires,
	requires_php: manifest.plugin.requires_php,
	sections: {
		description: 'Define reusable company identity, contact, address, hours, and social-profile information.',
	},
};

manifest.addons.locations = {
	name: 'Locations',
	slug: 'locations',
	module: 'locations',
	distribution: 'bundled',
	version: manifest.plugin.version,
	requires: manifest.plugin.requires,
	requires_php: manifest.plugin.requires_php,
	dependencies: [ 'company-details' ],
	sections: {
		description: 'Manage reusable branch, office, campus, or service-area details and optional public location pages.',
	},
};

manifest.addons.animations = {
	name: 'Animations',
	slug: 'animations',
	module: 'animations',
	distribution: 'bundled',
	version: manifest.plugin.version,
	requires: manifest.plugin.requires,
	requires_php: manifest.plugin.requires_php,
	sections: {
		description: 'Add token-based entrance animations to whole blocks or their content sections.',
	},
};

writeFileSync( join( outputDirectory, 'info.json' ), `${ JSON.stringify( manifest, null, 2 ) }\n` );
writeFileSync(
	join( outputDirectory, 'index.html' ),
	'<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Cinderwell Updates</title><body><h1>Cinderwell Updates</h1><p>WordPress release service.</p></body></html>\n'
);

console.log( `Built ${ components.length } packages in ${ outputDirectory }` );
for ( const component of components ) {
	console.log( `- ${ component.name } ${ parseHeader( join( component.source, component.header ), 'Version' ) }` );
}

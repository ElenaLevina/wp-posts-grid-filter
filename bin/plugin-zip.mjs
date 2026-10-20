/**
 * Builds the release zip: only the files the plugin needs at runtime, in a
 * root folder named after the plugin slug.
 *
 * `wp-scripts plugin-zip` is not used because it always adds package.json
 * and README.md. README.md is for GitHub; the plugin's readme is readme.txt.
 *
 * Usage: npm run release (builds first, then runs this script).
 */
import { existsSync, rmSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import AdmZip from 'adm-zip';

const { name: slug, files } = JSON.parse(
	await readFile( new URL( '../package.json', import.meta.url ) )
);
const root = new URL( '..', import.meta.url ).pathname;
const output = `${ slug }.zip`;

if ( ! existsSync( `${ root }build` ) ) {
	throw new Error( 'build/ is missing. Run `npm run build` first.' );
}

rmSync( `${ root }${ output }`, { force: true } );

const zip = new AdmZip();

for ( const file of files ) {
	const path = `${ root }${ file }`;
	if ( ! existsSync( path ) ) {
		throw new Error( `Missing file from package.json "files": ${ file }` );
	}
	if ( file.includes( '.' ) ) {
		zip.addLocalFile( path, slug );
	} else {
		zip.addLocalFolder(
			path,
			`${ slug }/${ file }`,
			( entry ) => ! /(^|\/)\.[^/]+$/.test( entry )
		);
	}
}

zip.writeZip( `${ root }${ output }` );
// eslint-disable-next-line no-console
console.log( `${ output } is ready (${ zip.getEntries().length } entries).` );

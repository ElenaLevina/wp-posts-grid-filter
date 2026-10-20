/**
 * Registers the Posts Filter block in the editor.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	// Dynamic block: render.php builds the form, nothing is saved.
	save: () => null,
} );

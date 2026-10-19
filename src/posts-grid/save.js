/**
 * The grid is a dynamic block: render.php builds the markup on every request.
 * Only the inner blocks (the pagination) are saved, so WordPress can pass
 * them to render.php as $content.
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function save() {
	return <InnerBlocks.Content />;
}

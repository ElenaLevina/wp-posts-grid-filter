/**
 * One card in the editor preview. Mirrors the markup in render.php.
 */
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Turns the rendered excerpt (HTML) into plain text.
 *
 * @param {string} html Rendered excerpt.
 * @return {string} Text without tags.
 */
function toText( html ) {
	const doc = new window.DOMParser().parseFromString(
		html ?? '',
		'text/html'
	);
	return doc.body.textContent.trim();
}

/**
 * Picks a medium-size image URL, falling back to the original file.
 *
 * @param {Object|undefined} image Media record from the core data store.
 * @return {string|undefined} Image URL.
 */
function imageUrl( image ) {
	const sizes = image?.media_details?.sizes ?? {};
	return (
		( sizes.medium_large ?? sizes.large ?? sizes.full )?.source_url ??
		image?.source_url
	);
}

export default function PostCard( { post, image, categories, tags } ) {
	const title = decodeEntities( post.title?.rendered ?? '' );
	// Links do not navigate away from the editor.
	const stay = ( event ) => event.preventDefault();

	return (
		<li className="elvpg-card">
			<article className="elvpg-card__inner">
				{ image && (
					<a
						className="elvpg-card__media"
						href={ post.link }
						onClick={ stay }
						tabIndex={ -1 }
						aria-hidden="true"
					>
						<img
							src={ imageUrl( image ) }
							alt={ image.alt_text ?? '' }
						/>
					</a>
				) }
				<div className="elvpg-card__body">
					{ categories.length > 0 && (
						<p className="elvpg-card__categories">
							{ categories.join( ', ' ) }
						</p>
					) }
					<h3 className="elvpg-card__title">
						<a href={ post.link } onClick={ stay }>
							{ title }
						</a>
					</h3>
					<p className="elvpg-card__excerpt">
						{ toText( post.excerpt?.rendered ) }
					</p>
					{ tags.length > 0 && (
						<ul className="elvpg-card__tags">
							{ tags.map( ( tag ) => (
								<li key={ tag } className="elvpg-card__tag">
									{ tag }
								</li>
							) ) }
						</ul>
					) }
				</div>
			</article>
		</li>
	);
}

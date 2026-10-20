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

/**
 * @param {Object}   props            Component props.
 * @param {Object}   props.post       Post record.
 * @param {Object}   props.image      Media record of the featured image.
 * @param {string[]} props.categories Category names.
 * @param {string[]} props.tags       Tag slugs, shown as #slug.
 * @param {number}   props.titleLevel Heading level of the title, 2–6.
 */
export default function PostCard( {
	post,
	image,
	categories,
	tags,
	titleLevel = 3,
} ) {
	const TitleTag = `h${ titleLevel }`;
	const title = decodeEntities( post.title?.rendered ?? '' );
	// Links do not navigate away from the editor.
	const stay = ( event ) => event.preventDefault();

	return (
		<li className="elvpg-card">
			<article className="elvpg-card__inner">
				{ image && (
					<div className="elvpg-card__media">
						{ /* Empty alt: the title next to it already names the post. */ }
						<img src={ imageUrl( image ) } alt="" />
					</div>
				) }
				<div className="elvpg-card__body">
					{ categories.length > 0 && (
						<p className="elvpg-card__categories">
							<svg
								className="elvpg-card__category-icon"
								viewBox="0 0 24 24"
								width="14"
								height="14"
								aria-hidden="true"
								focusable="false"
							>
								<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H9l2 2h8.5A1.5 1.5 0 0 1 21 8.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 17.5z" />
							</svg>
							<span>{ categories.join( ', ' ) }</span>
						</p>
					) }
					<TitleTag className="elvpg-card__title">
						<a href={ post.link } onClick={ stay }>
							{ title }
						</a>
					</TitleTag>
					<p className="elvpg-card__excerpt">
						{ toText( post.excerpt?.rendered ) }
					</p>
					{ tags.length > 0 && (
						<ul className="elvpg-card__tags">
							{ tags.map( ( tag ) => (
								<li key={ tag } className="elvpg-card__tag">
									#{ tag }
								</li>
							) ) }
						</ul>
					) }
				</div>
			</article>
		</li>
	);
}

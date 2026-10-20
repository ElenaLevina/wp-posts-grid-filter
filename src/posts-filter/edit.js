/**
 * Editor preview of the filter.
 *
 * Shows the real categories and tags, but the checkboxes are disabled:
 * filtering works on the published page only (ADR-003).
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useInstanceId } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';

import { SETTINGS } from '../posts-grid/constants';

const TERM_QUERY = {
	per_page: -1,
	hide_empty: true,
	orderby: 'name',
	order: 'asc',
};

function Group( { legend, terms } ) {
	const instanceId = useInstanceId( Group, 'elvpg-filter-preview' );

	if ( ! terms?.length ) {
		return null;
	}
	return (
		<fieldset className="elvpg-filter__group" disabled>
			<legend className="elvpg-filter__legend">{ legend }</legend>
			<ul className="elvpg-filter__options">
				{ terms.map( ( term ) => (
					<li key={ term.id }>
						<label
							className="elvpg-filter__option"
							htmlFor={ `${ instanceId }-${ term.id }` }
						>
							<input
								type="checkbox"
								id={ `${ instanceId }-${ term.id }` }
							/>
							<span>{ term.name }</span>
						</label>
					</li>
				) ) }
			</ul>
		</fieldset>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { showCategories, showTags } = attributes;

	const { categories, tags } = useSelect(
		( select ) => ( {
			categories: select( coreStore ).getEntityRecords(
				'taxonomy',
				SETTINGS.categoryTaxonomy,
				TERM_QUERY
			),
			tags: select( coreStore ).getEntityRecords(
				'taxonomy',
				SETTINGS.tagTaxonomy,
				TERM_QUERY
			),
		} ),
		[]
	);

	const blockProps = useBlockProps( { className: 'elvpg-filter' } );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Filters', 'elvpg-posts-grid-filter' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show categories',
							'elvpg-posts-grid-filter'
						) }
						checked={ showCategories }
						onChange={ ( value ) =>
							setAttributes( { showCategories: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Show tags', 'elvpg-posts-grid-filter' ) }
						checked={ showTags }
						onChange={ ( value ) =>
							setAttributes( { showTags: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="elvpg-filter__form">
					{ showCategories && (
						<Group
							legend={ __(
								'Categories',
								'elvpg-posts-grid-filter'
							) }
							terms={ categories }
						/>
					) }
					{ showTags && (
						<Group
							legend={ __( 'Tags', 'elvpg-posts-grid-filter' ) }
							terms={ tags }
						/>
					) }
				</div>
				<p className="elvpg-filter__editor-note">
					{ __(
						'Preview. Filtering works on the published page.',
						'elvpg-posts-grid-filter'
					) }
				</p>
			</div>
		</>
	);
}

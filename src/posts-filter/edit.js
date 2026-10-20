/**
 * Editor preview of the filter.
 *
 * Mirrors render.php with JavaScript enabled: a "Filter" label and one
 * dropdown per group. The dropdowns open in the editor so the author can see
 * the real terms, but the checkboxes are disabled: filtering works on the
 * published page only (ADR-003).
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useInstanceId } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

import { SETTINGS } from '../posts-grid/constants';

const TERM_QUERY = {
	per_page: -1,
	hide_empty: true,
	orderby: 'name',
	order: 'asc',
};

function Chevron() {
	return (
		<svg
			className="elvpg-filter__chevron"
			viewBox="0 0 24 24"
			width="16"
			height="16"
			aria-hidden="true"
			focusable="false"
		>
			<path d="M6 9l6 6 6-6" />
		</svg>
	);
}

function Dropdown( { label, terms, isOpen, onToggle } ) {
	const instanceId = useInstanceId( Dropdown, 'elvpg-filter-preview' );

	if ( ! terms?.length ) {
		return null;
	}

	return (
		<div
			className={ `elvpg-filter__dropdown${ isOpen ? ' is-open' : '' }` }
		>
			<button
				type="button"
				className="elvpg-filter__toggle has-background has-text-color"
				aria-expanded={ isOpen }
				aria-controls={ instanceId }
				onClick={ onToggle }
			>
				<span>{ label }</span>
				<Chevron />
			</button>
			<fieldset
				className="elvpg-filter__panel"
				id={ instanceId }
				disabled
			>
				<legend className="elvpg-filter__legend">{ label }</legend>
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
				<div className="elvpg-filter__panel-footer">
					<span className="elvpg-filter__selected">
						{ __(
							'Preview. Filtering works on the published page.',
							'elvpg-posts-grid-filter'
						) }
					</span>
				</div>
			</fieldset>
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const { showCategories, showTags } = attributes;
	const [ open, setOpen ] = useState( null );

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

	const toggle = ( group ) => setOpen( open === group ? null : group );
	const blockProps = useBlockProps( {
		className: 'elvpg-filter is-enhanced',
	} );

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
				<div className="elvpg-filter__bar">
					<p className="elvpg-filter__title">
						<svg
							className="elvpg-filter__icon"
							viewBox="0 0 24 24"
							width="18"
							height="18"
							aria-hidden="true"
							focusable="false"
						>
							<path d="M3 5h18l-7 8v6l-4 2v-8z" />
						</svg>
						{ __( 'Filter', 'elvpg-posts-grid-filter' ) }
					</p>
					<div className="elvpg-filter__dropdowns">
						{ showCategories && (
							<Dropdown
								label={ __(
									'Categories',
									'elvpg-posts-grid-filter'
								) }
								terms={ categories }
								isOpen={ open === 'categories' }
								onToggle={ () => toggle( 'categories' ) }
							/>
						) }
						{ showTags && (
							<Dropdown
								label={ __(
									'Tags',
									'elvpg-posts-grid-filter'
								) }
								terms={ tags }
								isOpen={ open === 'tags' }
								onToggle={ () => toggle( 'tags' ) }
							/>
						) }
					</div>
				</div>
			</div>
		</>
	);
}

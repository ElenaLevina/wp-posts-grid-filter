/**
 * Limits shared by the Inspector controls. The server enforces the same
 * limits in Elvpg_Query::clamp_per_page() and render.php.
 */
export const COLUMNS = { min: 2, max: 4 };
export const POSTS_PER_PAGE = { min: 1, max: 24 };

/**
 * Post type and taxonomy names, printed by PHP (Elvpg_Blocks), because a site
 * can change them with filters (ADR-001).
 */
export const SETTINGS = window.elvpgSettings ?? {
	postType: 'elvpg_post',
	categoryTaxonomy: 'elvpg_category',
	tagTaxonomy: 'elvpg_tag',
	// REST field names of the taxonomies on a post record.
	categoryRestBase: 'elvpg_category',
	tagRestBase: 'elvpg_tag',
};

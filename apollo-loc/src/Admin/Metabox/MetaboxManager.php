<?php
/**
 * MetaboxManager — orquestra todos os metaboxes do CPT "local"
 *
 * Carrega AddressMetabox, ContactMetabox, DetailsMetabox e MetaboxSaver.
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaboxManager {

	public function __construct() {
		new AddressMetabox();
		new ContactMetabox();
		new DetailsMetabox();
		new GalleryMetabox();
		/* Renders local_type / local_area as single-choice <select>s instead of
		   WordPress's default hierarchical checkbox list. Saving is driven by
		   MetaboxSaver, not a second save_post hook — see that class. */
		new TaxonomySelectMetabox();
		new MetaboxSaver();
	}
}

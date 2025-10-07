/**
 * FCRM Tributes Loading Animation
 * Handles loading animation for tribute grid items
 */

(function($) {
	'use strict';

	$(document).on('click', '.firehawk-crm .grid-item', function(event) {
		const contentLink = $(event.currentTarget).data('link');

		if (contentLink) {
			// Add loading animation
			addLoadingAnimation(event.currentTarget);

			// Handle click navigation
			if (event.ctrlKey || event.metaKey) {
				window.open(contentLink, '_blank');
			} else {
				document.location = contentLink;
			}
		}
	});

	/**
	 * Add loading animation to element
	 *
	 * @param {HTMLElement} element The element to add animation to
	 */
	function addLoadingAnimation(element) {
		element.classList.add('loading-animation');
	}

	/**
	 * Remove loading animation from element
	 *
	 * @param {HTMLElement} element The element to remove animation from
	 */
	function removeLoadingAnimation(element) {
		element.classList.remove('loading-animation');
	}
})(jQuery);

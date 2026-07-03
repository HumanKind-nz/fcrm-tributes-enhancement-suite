/**
 * Custom webpack config for non-block scripts.
 *
 * Extends the default @wordpress/scripts config to build:
 * - Settings page: src/js/settings/index.js → build/settings/index.js
 *
 * @package FcrmEnhancementSuite
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'settings/index': path.resolve( process.cwd(), 'src/js/settings/index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'build' ),
	},
};

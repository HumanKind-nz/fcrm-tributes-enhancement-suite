/**
 * Settings page entry point.
 *
 * Mounts the React settings app into the #fcrm-enhancement-settings container.
 *
 * @package FcrmEnhancementSuite
 */
import { createRoot } from '@wordpress/element';
import SettingsApp from './components/SettingsApp';
import './settings.scss';

const container = document.getElementById( 'fcrm-enhancement-settings' );

if ( container ) {
	const root = createRoot( container );
	root.render( <SettingsApp /> );
}

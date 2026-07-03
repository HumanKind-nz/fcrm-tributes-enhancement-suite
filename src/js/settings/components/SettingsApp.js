/**
 * Main settings app with tabbed navigation.
 *
 * Uses @wordpress/components TabPanel for the 6-tab settings UI.
 * All UI is built with WordPress components — no custom CSS needed.
 */
import { TabPanel, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSettings } from '../hooks/useSettings';
import DashboardTab from './DashboardTab';
import LayoutsTab from './LayoutsTab';
import StylingTab from './StylingTab';
import PerformanceTab from './PerformanceTab';
import SeoAnalyticsTab from './SeoAnalyticsTab';
import AboutTab from './AboutTab';

export default function SettingsApp() {
	const {
		settings,
		setSettings,
		saveSettings,
		isSaving,
		notice,
		setNotice,
	} = useSettings();

	if ( settings === null ) {
		return <Spinner />;
	}

	const tabs = [
		{
			name: 'dashboard',
			title: __( 'Dashboard', 'fcrm-enhancement-suite' ),
		},
		{
			name: 'layouts',
			title: __( 'Layouts', 'fcrm-enhancement-suite' ),
		},
		{
			name: 'styling',
			title: __( 'Styling', 'fcrm-enhancement-suite' ),
		},
		{
			name: 'performance',
			title: __( 'Performance', 'fcrm-enhancement-suite' ),
		},
		{
			name: 'seo',
			title: __( 'SEO & Analytics', 'fcrm-enhancement-suite' ),
		},
		// v3.2: an 'Integrations' tab mounts here (FireHawk token/brand/permalinks,
		// then additional data sources per the multi-source adapter strategy).
		// Intentionally not registered in v3.0.
		{
			name: 'about',
			title: __( 'About', 'fcrm-enhancement-suite' ),
		},
	];

	const tabProps = {
		settings,
		setSettings,
		saveSettings,
		isSaving,
	};

	const iconUrl = window.fcrmEnhancementSuite?.iconUrl || '';

	return (
		<div style={ { maxWidth: '960px' } }>
			<h1 style={ { display: 'flex', alignItems: 'center', gap: '10px' } }>
				{ iconUrl && (
					<img
						src={ iconUrl }
						alt=""
						style={ { width: '32px', height: '32px', borderRadius: '6px' } }
					/>
				) }
				{ __( 'FireHawk Tributes Enhancement Suite', 'fcrm-enhancement-suite' ) }
			</h1>

			{ notice && (
				<Notice
					status={ notice.status }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }

			<TabPanel tabs={ tabs }>
				{ ( tab ) => {
					switch ( tab.name ) {
						case 'dashboard':
							return <DashboardTab { ...tabProps } />;
						case 'layouts':
							return <LayoutsTab { ...tabProps } />;
						case 'styling':
							return <StylingTab { ...tabProps } />;
						case 'performance':
							return <PerformanceTab { ...tabProps } />;
						case 'seo':
							return <SeoAnalyticsTab { ...tabProps } />;
						case 'about':
							return <AboutTab />;
						default:
							return null;
					}
				} }
			</TabPanel>
		</div>
	);
}

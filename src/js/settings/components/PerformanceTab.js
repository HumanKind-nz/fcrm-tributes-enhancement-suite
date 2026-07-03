/**
 * Performance tab — caching controls, debug logging.
 */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
	RangeControl,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';

export default function PerformanceTab( { settings, setSettings, saveSettings, isSaving } ) {
	const [ local, setLocal ] = useState( settings );
	const [ cacheClearing, setCacheClearing ] = useState( false );
	const [ cacheNotice, setCacheNotice ] = useState( null );

	const update = ( key, value ) => {
		setLocal( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const save = () => {
		const merged = { ...settings, ...local };
		setSettings( merged );
		saveSettings( merged );
	};

	const clearCache = useCallback( async () => {
		setCacheClearing( true );
		setCacheNotice( null );

		try {
			const formData = new FormData();
			formData.append( 'action', 'fcrm_clear_cache' );
			formData.append( 'nonce', window.fcrmEnhancementSuite?.clearCacheNonce || '' );

			const response = await fetch( window.ajaxurl, {
				method: 'POST',
				body: formData,
			} );

			const data = await response.json();

			if ( data.success ) {
				setCacheNotice( { status: 'success', message: __( 'Cache cleared successfully.', 'fcrm-enhancement-suite' ) } );
			} else {
				setCacheNotice( { status: 'error', message: data.message || __( 'Failed to clear cache.', 'fcrm-enhancement-suite' ) } );
			}
		} catch {
			setCacheNotice( { status: 'error', message: __( 'Network error while clearing cache.', 'fcrm-enhancement-suite' ) } );
		}

		setCacheClearing( false );
	}, [] );

	return (
		<div style={ { paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '12px' } }>
			<Notice status="info" isDismissible={ false } style={ { margin: 0, borderRadius: '2px' } }>
				<p style={ { margin: '0 0 4px' } }>
					{ __( 'Conditional asset loading and script optimisation are always active, saving ~430KB on non-tribute pages.', 'fcrm-enhancement-suite' ) }
				</p>
				<p style={ { margin: 0, fontSize: '12px', color: '#646970' } }>
					{ __( 'Flower delivery is disabled site-wide. Use the ', 'fcrm-enhancement-suite' ) }
					<code>fcrm_disable_flower_delivery</code>
					{ __( ' filter to override.', 'fcrm-enhancement-suite' ) }
				</p>
			</Notice>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'API Caching', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<ToggleControl
						label={ __( 'Enable API Caching', 'fcrm-enhancement-suite' ) }
						help={ __( 'Cache FCRM API responses to improve page load times. Automatically cleared on tribute updates.', 'fcrm-enhancement-suite' ) }
						checked={ !! local.cache_enabled }
						onChange={ ( v ) => update( 'cache_enabled', v ) }
					/>

					{ local.cache_enabled && (
						<>
							<RangeControl
								label={ __( 'Client List Cache (seconds)', 'fcrm-enhancement-suite' ) }
								help={ __( 'Tribute listings cache duration. Default: 1800s (30 min).', 'fcrm-enhancement-suite' ) }
								value={ local.cache_duration_client_list }
								min={ 60 }
								max={ 7200 }
								step={ 60 }
								onChange={ ( v ) => update( 'cache_duration_client_list', v ) }
							/>
							<RangeControl
								label={ __( 'Single Tribute Cache (seconds)', 'fcrm-enhancement-suite' ) }
								help={ __( 'Individual tribute page cache duration. Default: 900s (15 min).', 'fcrm-enhancement-suite' ) }
								value={ local.cache_duration_single_client }
								min={ 60 }
								max={ 3600 }
								step={ 60 }
								onChange={ ( v ) => update( 'cache_duration_single_client', v ) }
							/>
							<RangeControl
								label={ __( 'Messages Cache (seconds)', 'fcrm-enhancement-suite' ) }
								help={ __( 'Tribute messages and dynamic content. Default: 300s (5 min).', 'fcrm-enhancement-suite' ) }
								value={ local.cache_duration_messages }
								min={ 60 }
								max={ 1800 }
								step={ 60 }
								onChange={ ( v ) => update( 'cache_duration_messages', v ) }
							/>

							<div style={ { marginTop: '12px' } }>
								{ cacheNotice && (
									<Notice
										status={ cacheNotice.status }
										isDismissible
										onDismiss={ () => setCacheNotice( null ) }
										style={ { marginBottom: '12px' } }
									>
										{ cacheNotice.message }
									</Notice>
								) }
								<Button
									variant="secondary"
									onClick={ clearCache }
									isBusy={ cacheClearing }
									disabled={ cacheClearing }
								>
									{ __( 'Clear All Cache', 'fcrm-enhancement-suite' ) }
								</Button>
							</div>
						</>
					) }
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Debugging', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<ToggleControl
						label={ __( 'Enable Debug Logging', 'fcrm-enhancement-suite' ) }
						help={ __( 'When enabled and WP_DEBUG is true, writes [FCRM_ES] lines to wp-content/debug.log. Disable on production.', 'fcrm-enhancement-suite' ) }
						checked={ !! local.debug_logging }
						onChange={ ( v ) => update( 'debug_logging', v ) }
					/>
				</CardBody>
			</Card>

			<Button variant="primary" onClick={ save } isBusy={ isSaving } disabled={ isSaving }>
				{ __( 'Save Changes', 'fcrm-enhancement-suite' ) }
			</Button>
		</div>
	);
}

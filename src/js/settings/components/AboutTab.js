/**
 * About tab — version, branding, links, settings export/import.
 */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	ExternalLink,
	Notice,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const OPTION_KEY = 'fcrm_enhancement_suite_settings';

/**
 * Settings export/import card.
 */
function SettingsTransfer() {
	const [ exportData, setExportData ] = useState( '' );
	const [ importData, setImportData ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ isBusy, setIsBusy ] = useState( false );

	const handleExport = useCallback( async () => {
		setNotice( null );
		setIsBusy( true );

		try {
			const response = await apiFetch( { path: '/wp/v2/settings' } );
			const settings = response[ OPTION_KEY ] || {};
			const json = JSON.stringify( settings, null, 2 );
			setExportData( json );

			// Copy to clipboard.
			try {
				await navigator.clipboard.writeText( json );
				setNotice( { status: 'success', message: __( 'Settings exported and copied to clipboard.', 'fcrm-enhancement-suite' ) } );
			} catch {
				// Clipboard API may fail in insecure contexts — the textarea is still there.
				setNotice( { status: 'success', message: __( 'Settings exported. Copy the JSON below.', 'fcrm-enhancement-suite' ) } );
			}
		} catch ( error ) {
			setNotice( { status: 'error', message: error.message || __( 'Failed to export settings.', 'fcrm-enhancement-suite' ) } );
		}

		setIsBusy( false );
	}, [] );

	const handleImport = useCallback( async () => {
		setNotice( null );

		if ( ! importData.trim() ) {
			setNotice( { status: 'error', message: __( 'Paste a settings JSON string first.', 'fcrm-enhancement-suite' ) } );
			return;
		}

		let parsed;
		try {
			parsed = JSON.parse( importData );
		} catch {
			setNotice( { status: 'error', message: __( 'Invalid JSON. Check the format and try again.', 'fcrm-enhancement-suite' ) } );
			return;
		}

		if ( typeof parsed !== 'object' || parsed === null || Array.isArray( parsed ) ) {
			setNotice( { status: 'error', message: __( 'JSON must be an object (not an array or primitive).', 'fcrm-enhancement-suite' ) } );
			return;
		}

		// eslint-disable-next-line no-alert
		if ( ! window.confirm( __( 'This will overwrite all current settings. Continue?', 'fcrm-enhancement-suite' ) ) ) {
			return;
		}

		setIsBusy( true );

		try {
			await apiFetch( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: { [ OPTION_KEY ]: parsed },
			} );

			setNotice( { status: 'success', message: __( 'Settings imported successfully. Reload the page to see changes.', 'fcrm-enhancement-suite' ) } );
			setImportData( '' );
		} catch ( error ) {
			setNotice( { status: 'error', message: error.message || __( 'Failed to import settings.', 'fcrm-enhancement-suite' ) } );
		}

		setIsBusy( false );
	}, [ importData ] );

	return (
		<Card style={ { margin: 0 } }>
			<CardHeader><strong>{ __( 'Settings Export / Import', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
			<CardBody>
				<p>{ __( 'Transfer settings between sites. Export produces a JSON string you can import on another installation.', 'fcrm-enhancement-suite' ) }</p>

				{ notice && (
					<Notice
						status={ notice.status }
						isDismissible
						onDismiss={ () => setNotice( null ) }
						style={ { marginBottom: '12px' } }
					>
						{ notice.message }
					</Notice>
				) }

				<div style={ { margin: 0 } }>
					<Button variant="secondary" onClick={ handleExport } isBusy={ isBusy } disabled={ isBusy }>
						{ __( 'Export Settings', 'fcrm-enhancement-suite' ) }
					</Button>
					{ exportData && (
						<TextareaControl
							value={ exportData }
							readOnly
							rows={ 6 }
							style={ { marginTop: '8px', fontFamily: 'monospace', fontSize: '12px' } }
							onClick={ ( e ) => e.target.select() }
						/>
					) }
				</div>

				<div>
					<TextareaControl
						label={ __( 'Import Settings', 'fcrm-enhancement-suite' ) }
						help={ __( 'Paste a previously exported settings JSON string.', 'fcrm-enhancement-suite' ) }
						value={ importData }
						onChange={ setImportData }
						rows={ 6 }
						style={ { fontFamily: 'monospace', fontSize: '12px' } }
					/>
					<Button
						variant="secondary"
						isDestructive
						onClick={ handleImport }
						isBusy={ isBusy }
						disabled={ isBusy || ! importData.trim() }
					>
						{ __( 'Import Settings', 'fcrm-enhancement-suite' ) }
					</Button>
				</div>
			</CardBody>
		</Card>
	);
}

/**
 * A single copy-to-paste shortcode row.
 */
function ShortcodeRow( { title, code, description } ) {
	const [ copied, setCopied ] = useState( false );

	const copy = useCallback( async () => {
		try {
			await navigator.clipboard.writeText( code );
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} catch {
			// Clipboard API unavailable (insecure context) — the code is still visible to copy manually.
		}
	}, [ code ] );

	return (
		<div style={ { marginBottom: '16px' } }>
			<strong>{ title }</strong>
			<div style={ { display: 'flex', alignItems: 'center', gap: '8px', margin: '4px 0' } }>
				<code style={ { background: '#f0f0f1', padding: '6px 10px', borderRadius: '4px', fontSize: '13px', flex: 1 } }>
					{ code }
				</code>
				<Button variant="secondary" onClick={ copy }>
					{ copied ? __( 'Copied!', 'fcrm-enhancement-suite' ) : __( 'Copy', 'fcrm-enhancement-suite' ) }
				</Button>
			</div>
			<p style={ { margin: '4px 0 0', color: '#757575', fontSize: '13px' } }>{ description }</p>
		</div>
	);
}

/**
 * Shortcodes reference card.
 */
function ShortcodesCard() {
	return (
		<Card style={ { margin: 0 } }>
			<CardHeader><strong>{ __( 'Shortcodes', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
			<CardBody>
				<p>{ __( 'Paste these into any page, page builder, or shortcode block.', 'fcrm-enhancement-suite' ) }</p>
				<ShortcodeRow
					title={ __( 'Tributes Grid', 'fcrm-enhancement-suite' ) }
					code="[show_crm_tributes_grid]"
					description={ __( 'The main listing grid, with search built in. Pick the grid style on the Layouts tab. Optionally override per placement with layout="modern-grid|elegant-grid|gallery-grid|minimal".', 'fcrm-enhancement-suite' ) }
				/>
				<ShortcodeRow
					title={ __( 'Single Tribute', 'fcrm-enhancement-suite' ) }
					code="[show_crm_tribute]"
					description={ __( 'Renders one tribute. Used automatically on the tribute detail page, or drop it on a page to feature a specific tribute as a standalone card.', 'fcrm-enhancement-suite' ) }
				/>
			</CardBody>
		</Card>
	);
}

export default function AboutTab() {
	const version = window.fcrmEnhancementSuite?.version || '';
	const iconUrl = window.fcrmEnhancementSuite?.iconUrl || '';

	return (
		<div style={ { paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '12px' } }>
			<Card style={ { margin: 0 } }>
				<CardHeader>
					<div style={ { display: 'flex', alignItems: 'center', gap: '12px' } }>
						{ iconUrl && (
							<img
								src={ iconUrl }
								alt="HumanKind"
								style={ { width: '48px', height: '48px', borderRadius: '8px' } }
							/>
						) }
						<div>
							<strong>{ __( 'FireHawk Tributes Enhancement Suite', 'fcrm-enhancement-suite' ) }</strong>
							{ version && <span style={ { marginLeft: '8px', color: '#757575' } }>v{ version }</span> }
						</div>
					</div>
				</CardHeader>
				<CardBody>
					<p>
						{ __( 'Developed by ', 'fcrm-enhancement-suite' ) }
						<ExternalLink href="https://humankindwebsites.com">Human Kind Funeral Websites</ExternalLink>
						{ __( ' and ', 'fcrm-enhancement-suite' ) }
						<ExternalLink href="https://weave.co.nz">Weave Digital Studio</ExternalLink>
						{ __( ' to extend the FireHawkCRM Tributes plugin for funeral home websites in NZ/AU.', 'fcrm-enhancement-suite' ) }
					</p>
					<ul style={ { listStyle: 'disc', paddingLeft: '20px' } }>
						<li>{ __( 'Modern responsive grid and single tribute layouts', 'fcrm-enhancement-suite' ) }</li>
						<li>{ __( 'Conditional asset loading (~430KB saved on non-tribute pages, ~330KB more on single tributes)', 'fcrm-enhancement-suite' ) }</li>
						<li>{ __( 'API response caching with Redis support', 'fcrm-enhancement-suite' ) }</li>
						<li>{ __( 'Integrated Plausible Analytics and SEOPress support', 'fcrm-enhancement-suite' ) }</li>
						<li>{ __( 'Instant search engine indexing for new tributes', 'fcrm-enhancement-suite' ) }</li>
						<li>{ __( 'WCAG 2.1 AA compliant, mobile-first design', 'fcrm-enhancement-suite' ) }</li>
					</ul>
				</CardBody>
			</Card>

			<ShortcodesCard />

			<SettingsTransfer />

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Other Funeral Sector Plugins', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'Part of the HumanKind suite for funeral websites:', 'fcrm-enhancement-suite' ) }</p>
					<ul style={ { listStyle: 'disc', paddingLeft: '20px' } }>
						<li>
							<ExternalLink href="https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite">
								{ __( 'FCRM Tributes Enhancement Suite', 'fcrm-enhancement-suite' ) }
							</ExternalLink>
							{ __( ' — This plugin', 'fcrm-enhancement-suite' ) }
						</li>
					</ul>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Support', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>
						{ __( 'Contact our support team at ', 'fcrm-enhancement-suite' ) }
						<ExternalLink href="mailto:support@weave.co.nz?subject=FH Tribute Enhancement Plugin Support">
							support@weave.co.nz
						</ExternalLink>
						{ __( ' or log an issue on ', 'fcrm-enhancement-suite' ) }
						<ExternalLink href="https://github.com/HumanKind-nz/fcrm-tributes-enhancement-suite/issues">
							GitHub
						</ExternalLink>.
					</p>
					<p>
						{ __( 'Need a custom enhancement for your funeral website? ', 'fcrm-enhancement-suite' ) }
						<ExternalLink href="https://humankindwebsites.com">
							{ __( 'Contact Human Kind Funeral Websites', 'fcrm-enhancement-suite' ) }
						</ExternalLink>
					</p>
				</CardBody>
			</Card>
		</div>
	);
}

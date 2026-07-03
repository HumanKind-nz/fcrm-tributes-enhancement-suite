/**
 * SEO & Analytics tab.
 *
 * Plausible Analytics, SEOPress, Sitemap, Instant Indexing controls.
 */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	ToggleControl,
	TextControl,
	TextareaControl,
	Notice,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Social image picker using WordPress media library.
 */
function SocialImagePicker( { value, onChange } ) {
	const defaultImg = window.fcrmEnhancementSuite?.defaultSocialImg || '';
	const displayImg = value || defaultImg;

	const openMediaLibrary = () => {
		const frame = window.wp?.media( {
			title: __( 'Select Social Share Image', 'fcrm-enhancement-suite' ),
			button: { text: __( 'Use Image', 'fcrm-enhancement-suite' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			onChange( attachment.url );
		} );

		frame.open();
	};

	return (
		<div style={ { margin: 0 } }>
			<p><strong>{ __( 'Branded Fallback Image', 'fcrm-enhancement-suite' ) }</strong></p>
			{ displayImg && (
				<img
					src={ displayImg }
					alt={ __( 'Social share preview', 'fcrm-enhancement-suite' ) }
					style={ { maxWidth: '300px', maxHeight: '200px', border: '1px solid #ddd', borderRadius: '4px', display: 'block', marginBottom: '8px' } }
				/>
			) }
			<Button variant="secondary" onClick={ openMediaLibrary } style={ { marginRight: '8px' } }>
				{ value ? __( 'Change Image', 'fcrm-enhancement-suite' ) : __( 'Upload Image', 'fcrm-enhancement-suite' ) }
			</Button>
			{ value && (
				<Button variant="tertiary" onClick={ () => onChange( '' ) }>
					{ __( 'Use Default', 'fcrm-enhancement-suite' ) }
				</Button>
			) }
			<p className="description" style={ { marginTop: '4px', color: '#757575' } }>
				{ __( 'Recommended: 1200x630px for optimal social media display.', 'fcrm-enhancement-suite' ) }
			</p>
		</div>
	);
}

export default function SeoAnalyticsTab( { settings, setSettings, saveSettings, isSaving } ) {
	const [ local, setLocal ] = useState( settings );

	const caps = window.fcrmEnhancementSuite?.capabilities || {};
	const seopressActive = !! caps.seopressActive;
	const plausibleActive = !! caps.plausibleActive;

	const update = ( key, value ) => {
		setLocal( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const save = () => {
		const merged = { ...settings, ...local };
		setSettings( merged );
		saveSettings( merged );
	};

	return (
		<div style={ { paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '12px' } }>
			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Plausible Analytics', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'Privacy-focused, GDPR-compliant analytics for tribute pages. Requires the Plausible Analytics plugin to be installed.', 'fcrm-enhancement-suite' ) }</p>
					<ToggleControl
						label={ __( 'Enable Plausible Analytics', 'fcrm-enhancement-suite' ) }
						help={ __( 'Load Plausible tracking on tribute pages only.', 'fcrm-enhancement-suite' ) }
						checked={ !! local.seo_enable_plausible }
						onChange={ ( v ) => update( 'seo_enable_plausible', v ) }
						disabled={ ! plausibleActive }
					/>
					{ ! plausibleActive && (
						<p style={ { margin: '4px 0 0', fontSize: '12px', color: '#646970' } }>
							{ __( 'Plausible Analytics is not active on this site. Install and activate Plausible Analytics to enable this integration.', 'fcrm-enhancement-suite' ) }
						</p>
					) }
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'SEOPress Integration', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'Enhanced SEO and social media meta tags for tribute pages. Requires SEOPress plugin.', 'fcrm-enhancement-suite' ) }</p>
					<ToggleControl
						label={ __( 'Enable SEOPress Integration', 'fcrm-enhancement-suite' ) }
						help={ __( 'Replace default Yoast integration with SEOPress support.', 'fcrm-enhancement-suite' ) }
						checked={ !! local.seo_enable_seopress }
						onChange={ ( v ) => update( 'seo_enable_seopress', v ) }
						disabled={ ! seopressActive }
					/>
					{ ! seopressActive && (
						<p style={ { margin: '4px 0 0', fontSize: '12px', color: '#646970' } }>
							{ __( 'SEOPress is not active on this site. Install and activate SEOPress to enable this integration.', 'fcrm-enhancement-suite' ) }
						</p>
					) }
					{ local.seo_enable_seopress && (
						<>
							<TextControl
								label={ __( 'Title Suffix', 'fcrm-enhancement-suite' ) }
								help={ __( 'Text appended to tribute names in page titles (e.g. "Tribute").', 'fcrm-enhancement-suite' ) }
								value={ local.seo_seopress_title_suffix || '' }
								onChange={ ( v ) => update( 'seo_seopress_title_suffix', v ) }
							/>
							<ToggleControl
								label={ __( 'Use the tribute photo for social sharing', 'fcrm-enhancement-suite' ) }
								help={ __( 'Use the person’s photo as the social share image, falling back to the image below when a tribute has no photo. Turn off to always use the branded image.', 'fcrm-enhancement-suite' ) }
								checked={ local.seo_seopress_use_tribute_photo !== false }
								onChange={ ( v ) => update( 'seo_seopress_use_tribute_photo', v ) }
							/>
							<SocialImagePicker
								value={ local.seo_seopress_social_image || '' }
								onChange={ ( v ) => update( 'seo_seopress_social_image', v ) }
							/>
						</>
					) }
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Tribute Sitemap', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'Generates XML sitemaps for tribute pages. Works with SEOPress, Yoast, RankMath, and WordPress native sitemaps.', 'fcrm-enhancement-suite' ) }</p>
					<ToggleControl
						label={ __( 'Enable Tribute Sitemap', 'fcrm-enhancement-suite' ) }
						help={ __( 'Generate tribute sitemaps at /fhf_tributes_sitemap_1.xml.', 'fcrm-enhancement-suite' ) }
						checked={ !! local.seo_enable_sitemap }
						onChange={ ( v ) => update( 'seo_enable_sitemap', v ) }
					/>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Instant Indexing', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'Automatically submit new tributes to search engines for faster discovery.', 'fcrm-enhancement-suite' ) }</p>

					<ToggleControl
						label={ __( 'Enable Google Indexing API', 'fcrm-enhancement-suite' ) }
						checked={ !! local.indexing_google_enabled }
						onChange={ ( v ) => update( 'indexing_google_enabled', v ) }
					/>
					{ local.indexing_google_enabled && (
						<>
							<TextareaControl
								label={ __( 'Google Service Account Credentials (JSON)', 'fcrm-enhancement-suite' ) }
								help={ __( 'Paste the full JSON key file content from your Google Cloud service account.', 'fcrm-enhancement-suite' ) }
								value={ local.indexing_google_credentials || '' }
								onChange={ ( v ) => update( 'indexing_google_credentials', v ) }
								rows={ 4 }
							/>
							<NumberControl
								label={ __( 'Daily Quota', 'fcrm-enhancement-suite' ) }
								help={ __( 'Maximum submissions per day. Google default is 200.', 'fcrm-enhancement-suite' ) }
								value={ local.indexing_google_quota }
								min={ 1 }
								max={ 10000 }
								onChange={ ( v ) => update( 'indexing_google_quota', parseInt( v, 10 ) || 200 ) }
							/>
						</>
					) }

					<div style={ { marginTop: '16px' } }>
						<ToggleControl
							label={ __( 'Enable IndexNow (Bing, Yandex, DuckDuckGo)', 'fcrm-enhancement-suite' ) }
							checked={ !! local.indexing_indexnow_enabled }
							onChange={ ( v ) => update( 'indexing_indexnow_enabled', v ) }
						/>
					</div>
				</CardBody>
			</Card>

			<Button variant="primary" onClick={ save } isBusy={ isSaving } disabled={ isSaving }>
				{ __( 'Save Changes', 'fcrm-enhancement-suite' ) }
			</Button>
		</div>
	);
}

/**
 * Styling tab — context-aware based on layouts module state.
 *
 * - Layouts ON  → UI Styling controls (colours, spacing, typography)
 * - Layouts OFF → FireHawk original layout colour overrides
 *
 * Also includes spinner settings (always visible).
 */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	ColorPicker,
	ColorPalette,
	ColorIndicator,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextareaControl,
	Notice,
	__experimentalNumberControl as NumberControl,
	Popover,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Theme/global colour palette, localised once from PHP (wp_get_global_settings).
 * Lets developers match brand colours with a single click. Read at module load
 * so it isn't recomputed on every ColorField render.
 */
const THEME_PALETTE = window.fcrmEnhancementSuite?.themePalette || [];

/**
 * Plugin default settings, localised from PHP get_defaults(). One source of
 * truth for the "Reset colours to defaults" buttons.
 */
const DEFAULTS = window.fcrmEnhancementSuite?.defaults || {};

/**
 * parseInt that preserves a valid 0 (unlike `parseInt( v ) || fallback`, which
 * turns a stored "0" into the fallback). Returns `fallback` only when the value
 * is missing/non-numeric.
 */
const intOrDefault = ( value, fallback ) => {
	const n = parseInt( value, 10 );
	return Number.isNaN( n ) ? fallback : n;
};

/**
 * Resets the given setting keys to their plugin defaults (in local state; the
 * user still presses Save to persist). Reused for the colour and the
 * layout/spacing reset buttons.
 */
function ResetButton( { update, keys, label } ) {
	return (
		<Button
			variant="secondary"
			onClick={ () => keys.forEach( ( key ) => update( key, DEFAULTS[ key ] ?? '' ) ) }
		>
			{ label }
		</Button>
	);
}

/**
 * Reusable colour field with popover picker.
 */
function ColorField( { label, value, onChange } ) {
	const [ isOpen, setIsOpen ] = useState( false );

	return (
		<div style={ { display: 'flex', alignItems: 'center', gap: '8px' } }>
			<span style={ { minWidth: '200px' } }>{ label }</span>
			<button
				type="button"
				onClick={ () => setIsOpen( ! isOpen ) }
				style={ {
					border: '1px solid #ccc',
					borderRadius: '4px',
					padding: '2px',
					cursor: 'pointer',
					background: 'none',
					position: 'relative',
				} }
			>
				<ColorIndicator colorValue={ value } />
				{ isOpen && (
					<Popover onClose={ () => setIsOpen( false ) }>
						<div style={ { padding: '12px', width: '240px' } }>
							{ THEME_PALETTE.length > 0 && (
								<div style={ { marginBottom: '12px' } }>
									<p style={ { margin: '0 0 8px', fontSize: '11px', fontWeight: 600, textTransform: 'uppercase', color: '#757575' } }>
										{ __( 'Theme colours', 'fcrm-enhancement-suite' ) }
									</p>
									<ColorPalette
										colors={ THEME_PALETTE }
										value={ value }
										onChange={ ( color ) => onChange( color || '' ) }
										disableCustomColors
										clearable={ false }
									/>
								</div>
							) }
							<ColorPicker
								color={ value }
								onChange={ onChange }
								enableAlpha
							/>
						</div>
					</Popover>
				) }
			</button>
			<code style={ { fontSize: '12px', color: value ? undefined : '#757575' } }>
				{ value || __( 'Default (inherits theme)', 'fcrm-enhancement-suite' ) }
			</code>
		</div>
	);
}

/**
 * UI Styling controls (modern layouts).
 */
function UIStyleControls( { local, update } ) {
	const colorFields = [
		[ 'ui_primary_color', __( 'Primary Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_primary_text_color', __( 'Primary Text Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_primary_button_text_color', __( 'Button Text Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_secondary_color', __( 'Secondary Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_accent_color', __( 'Accent Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_background_color', __( 'Background Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_card_background', __( 'Card Background', 'fcrm-enhancement-suite' ) ],
		[ 'ui_text_color', __( 'Text Colour', 'fcrm-enhancement-suite' ) ],
		[ 'ui_border_color', __( 'Border Colour', 'fcrm-enhancement-suite' ) ],
	];

	const shadowOptions = [
		{ label: 'None', value: 'none' },
		{ label: 'Subtle', value: 'subtle' },
		{ label: 'Medium', value: 'medium' },
		{ label: 'Elevated', value: 'elevated' },
	];

	const fontOptions = [
		{ label: 'System Default', value: 'system' },
		{ label: 'Serif', value: 'serif' },
		{ label: 'Modern (Inter)', value: 'modern' },
		{ label: 'Traditional (Crimson Text)', value: 'traditional' },
	];

	const photoSizeOptions = [
		{ label: 'Small', value: 'small' },
		{ label: 'Medium', value: 'medium' },
		{ label: 'Large', value: 'large' },
	];

	return (
		<>
			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Colours', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					{ colorFields.map( ( [ key, label ] ) => (
						<ColorField
							key={ key }
							label={ label }
							value={ local[ key ] || '' }
							onChange={ ( v ) => update( key, v ) }
						/>
					) ) }
					<ResetButton
						update={ update }
						keys={ colorFields.map( ( [ k ] ) => k ) }
						label={ __( 'Reset colours to defaults', 'fcrm-enhancement-suite' ) }
					/>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Layout & Spacing', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<RangeControl
						label={ __( 'Card Radius (px)', 'fcrm-enhancement-suite' ) }
						value={ intOrDefault( local.ui_border_radius, 8 ) }
						min={ 0 }
						max={ 30 }
						onChange={ ( v ) => update( 'ui_border_radius', String( v ) ) }
					/>
					<RangeControl
						label={ __( 'Button & Field Radius (px)', 'fcrm-enhancement-suite' ) }
						value={ intOrDefault( local.ui_control_radius, 8 ) }
						min={ 0 }
						max={ 30 }
						onChange={ ( v ) => update( 'ui_control_radius', String( v ) ) }
					/>
					<RangeControl
						label={ __( 'Border Width (px)', 'fcrm-enhancement-suite' ) }
						value={ parseInt( local.ui_border_width, 10 ) || 1 }
						min={ 0 }
						max={ 5 }
						onChange={ ( v ) => update( 'ui_border_width', String( v ) ) }
					/>
					<SelectControl
						label={ __( 'Card Shadow', 'fcrm-enhancement-suite' ) }
						value={ local.ui_card_shadow }
						options={ shadowOptions }
						onChange={ ( v ) => update( 'ui_card_shadow', v ) }
					/>
					<RangeControl
						label={ __( 'Grid Gap (rem)', 'fcrm-enhancement-suite' ) }
						value={ parseFloat( local.ui_grid_gap ) || 1.5 }
						min={ 0.5 }
						max={ 4 }
						step={ 0.25 }
						onChange={ ( v ) => update( 'ui_grid_gap', String( v ) ) }
					/>
					<RangeControl
						label={ __( 'Card Padding (rem)', 'fcrm-enhancement-suite' ) }
						value={ parseFloat( local.ui_card_padding ) || 1.5 }
						min={ 0.5 }
						max={ 4 }
						step={ 0.25 }
						onChange={ ( v ) => update( 'ui_card_padding', String( v ) ) }
					/>
					<NumberControl
						label={ __( 'Grid Max Width (px)', 'fcrm-enhancement-suite' ) }
						value={ local.ui_grid_max_width }
						min={ 800 }
						max={ 1600 }
						onChange={ ( v ) => update( 'ui_grid_max_width', String( parseInt( v, 10 ) || 1200 ) ) }
					/>
					<ResetButton
						update={ update }
						keys={ [
							'ui_border_radius',
							'ui_control_radius',
							'ui_border_width',
							'ui_card_shadow',
							'ui_grid_gap',
							'ui_card_padding',
							'ui_grid_max_width',
						] }
						label={ __( 'Reset layout & spacing to defaults', 'fcrm-enhancement-suite' ) }
					/>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Typography', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<ToggleControl
						label={ __( 'Inherit Font from Theme', 'fcrm-enhancement-suite' ) }
						checked={ !! local.ui_font_inherit }
						onChange={ ( v ) => update( 'ui_font_inherit', v ) }
					/>
					{ ! local.ui_font_inherit && (
						<SelectControl
							label={ __( 'Font Family', 'fcrm-enhancement-suite' ) }
							value={ local.ui_font_family }
							options={ fontOptions }
							onChange={ ( v ) => update( 'ui_font_family', v ) }
						/>
					) }
					<RangeControl
						label={ __( 'Font Size Scale (%)', 'fcrm-enhancement-suite' ) }
						value={ parseInt( local.ui_font_size_scale, 10 ) || 100 }
						min={ 80 }
						max={ 130 }
						onChange={ ( v ) => update( 'ui_font_size_scale', String( v ) ) }
					/>
					{ local.active_layout === 'elegant-grid' && (
						<ToggleControl
							label={ __( 'Use Serif for Elegant Layout', 'fcrm-enhancement-suite' ) }
							checked={ !! local.ui_elegant_use_serif }
							onChange={ ( v ) => update( 'ui_elegant_use_serif', v ) }
						/>
					) }
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Layout-Specific', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					{ local.active_layout === 'elegant-grid' && (
						<ColorField
							label={ __( 'Elegant Gold Colour', 'fcrm-enhancement-suite' ) }
							value={ local.ui_elegant_gold_color || '#d4af37' }
							onChange={ ( v ) => update( 'ui_elegant_gold_color', v ) }
						/>
					) }
					{ local.active_layout === 'gallery-grid' && (
						<RangeControl
							label={ __( 'Gallery Overlay Opacity (%)', 'fcrm-enhancement-suite' ) }
							value={ parseInt( local.ui_gallery_overlay_opacity, 10 ) || 85 }
							min={ 0 }
							max={ 100 }
							onChange={ ( v ) => update( 'ui_gallery_overlay_opacity', String( v ) ) }
						/>
					) }
					{ local.active_layout === 'minimal' && (
						<SelectControl
							label={ __( 'List Photo Size', 'fcrm-enhancement-suite' ) }
							value={ local.ui_list_photo_size }
							options={ photoSizeOptions }
							onChange={ ( v ) => update( 'ui_list_photo_size', v ) }
						/>
					) }
					{ ( local.active_layout === 'modern-grid' || local.active_layout === 'gallery-grid' ) && (
						<ToggleControl
							label={ __( 'Modern Grid Hover Lift', 'fcrm-enhancement-suite' ) }
							checked={ !! local.ui_modern_hover_lift }
							onChange={ ( v ) => update( 'ui_modern_hover_lift', v ) }
						/>
					) }
				</CardBody>
			</Card>
		</>
	);
}

/**
 * FireHawk Styling controls (original layout).
 */
function FireHawkStyleControls( { local, update } ) {
	const colorFields = [
		[ 'styling_primary_color', __( 'Primary Colour', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_color', __( 'Secondary Colour', 'fcrm-enhancement-suite' ) ],
		[ 'styling_primary_button', __( 'Primary Button Colour', 'fcrm-enhancement-suite' ) ],
		[ 'styling_primary_button_text', __( 'Primary Button Text', 'fcrm-enhancement-suite' ) ],
		[ 'styling_primary_button_hover', __( 'Primary Button Hover', 'fcrm-enhancement-suite' ) ],
		[ 'styling_primary_button_hover_text', __( 'Primary Button Hover Text', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button', __( 'Secondary Button Colour', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button_text', __( 'Secondary Button Text', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button_border', __( 'Secondary Button Border', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button_hover', __( 'Secondary Button Hover', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button_hover_text', __( 'Secondary Button Hover Text', 'fcrm-enhancement-suite' ) ],
		[ 'styling_secondary_button_hover_border', __( 'Secondary Button Hover Border', 'fcrm-enhancement-suite' ) ],
		[ 'styling_focus_border_color', __( 'Grid Card Border', 'fcrm-enhancement-suite' ) ],
		[ 'styling_card_background', __( 'Grid Card Background', 'fcrm-enhancement-suite' ) ],
		[ 'styling_primary_shadow', __( 'Grid Card Box Shadow', 'fcrm-enhancement-suite' ) ],
		[ 'styling_focus_shadow_color', __( 'Focus Shadow Colour', 'fcrm-enhancement-suite' ) ],
		[ 'styling_link_color', __( 'Link Colour', 'fcrm-enhancement-suite' ) ],
	];

	return (
		<Card style={ { margin: 0 } }>
			<CardHeader><strong>{ __( 'FireHawk Layout Colours', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
			<CardBody>
				<p>{ __( 'Customise the original FireHawk Tributes colour scheme. Switch to Modern layouts on the Layouts tab for modern styling controls instead.', 'fcrm-enhancement-suite' ) }</p>
				{ colorFields.map( ( [ key, label ] ) => (
					<ColorField
						key={ key }
						label={ label }
						value={ local[ key ] || '' }
						onChange={ ( v ) => update( key, v ) }
					/>
				) ) }

				<ResetButton
					update={ update }
					keys={ colorFields.map( ( [ k ] ) => k ) }
					label={ __( 'Reset colours to defaults', 'fcrm-enhancement-suite' ) }
				/>

				<NumberControl
					label={ __( 'Button Border Radius', 'fcrm-enhancement-suite' ) }
					value={ local.styling_border_radius }
					onChange={ ( v ) => update( 'styling_border_radius', v ) }
				/>
				<NumberControl
					label={ __( 'Grid Card Border Radius', 'fcrm-enhancement-suite' ) }
					value={ local.styling_grid_border_radius }
					onChange={ ( v ) => update( 'styling_grid_border_radius', v ) }
				/>
			</CardBody>
		</Card>
	);
}

export default function StylingTab( { settings, setSettings, saveSettings, isSaving } ) {
	const [ local, setLocal ] = useState( settings );
	const isModern = ( local.layout_mode || 'modern' ) === 'modern';

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
			{ isModern ? (
				<>
					<Notice status="info" isDismissible={ false } style={ { margin: 0, borderRadius: '2px' } }>
						<p style={ { margin: 0 } }>
							{ __( 'Showing UI Styling controls for modern layouts.', 'fcrm-enhancement-suite' ) }
							<br />
							<span style={ { fontSize: '12px', color: '#646970' } }>
								{ __( 'Switch to the FireHawk original layout on the Layouts tab to access colour overrides instead.', 'fcrm-enhancement-suite' ) }
							</span>
						</p>
					</Notice>
					<UIStyleControls local={ local } update={ update } />
				</>
			) : (
				<>
					<Notice status="info" isDismissible={ false } style={ { margin: 0, borderRadius: '2px' } }>
						<p style={ { margin: 0 } }>
							{ __( 'Showing FireHawk layout colour overrides.', 'fcrm-enhancement-suite' ) }
							<br />
							<span style={ { fontSize: '12px', color: '#646970' } }>
								{ __( 'Switch to Modern layouts on the Layouts tab for modern styling controls.', 'fcrm-enhancement-suite' ) }
							</span>
						</p>
					</Notice>
					<FireHawkStyleControls local={ local } update={ update } />
				</>
			) }

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Loading Spinner', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<ColorField
						label={ __( 'Spinner Colour', 'fcrm-enhancement-suite' ) }
						value={ local.spinner_color || '#667eea' }
						onChange={ ( v ) => update( 'spinner_color', v ) }
					/>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader><strong>{ __( 'Custom CSS (Advanced)', 'fcrm-enhancement-suite' ) }</strong></CardHeader>
				<CardBody>
					<p>{ __( 'For developers. CSS entered here is loaded on tribute pages only, after the layout styles, so it can override them. It travels with the settings export. Use the layout class names (e.g. .fcrm-tribute-card, .fcrm-enhanced-classic).', 'fcrm-enhancement-suite' ) }</p>
					<TextareaControl
						value={ local.styling_custom_css || '' }
						onChange={ ( v ) => update( 'styling_custom_css', v ) }
						rows={ 8 }
						style={ { fontFamily: 'monospace', fontSize: '13px' } }
						placeholder={ '.fcrm-tribute-card {\n    min-height: 420px;\n}' }
					/>
				</CardBody>
			</Card>

			<Button variant="primary" onClick={ save } isBusy={ isSaving } disabled={ isSaving }>
				{ __( 'Save Changes', 'fcrm-enhancement-suite' ) }
			</Button>
		</div>
	);
}

/**
 * Layouts tab — grid + single layout selection, grid options.
 */
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	RadioControl,
	SelectControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const gridLayouts = [
	{ label: 'Modern Grid Layout', value: 'modern-grid' },
	{ label: 'Elegant Grid Layout', value: 'elegant-grid' },
	{ label: 'Single Column List', value: 'minimal' },
	{ label: 'Gallery Grid Layout', value: 'gallery-grid' },
];

const singleLayouts = [
	{ label: 'Default FCRM Single Layout', value: 'default' },
	{ label: 'Enhanced Classic (Subtle Modern Touches)', value: 'enhanced-classic' },
	// 'Modern Hero' is incomplete (hero banner only) — hidden until finished.
];

const cardStyles = [
	{ label: 'Standard Cards', value: 'standard' },
	{ label: 'Elevated Cards', value: 'elevated' },
	{ label: 'Outlined Cards', value: 'outlined' },
	{ label: 'Minimal Cards', value: 'minimal' },
];

const gridColumnOptions = [
	{ label: '3 Columns', value: '3' },
	{ label: '4 Columns', value: '4' },
];

export default function LayoutsTab( { settings, setSettings, saveSettings, isSaving } ) {
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
			<Card style={ { margin: 0 } }>
				<CardHeader>
					<strong>{ __( 'Layout system', 'fcrm-enhancement-suite' ) }</strong>
				</CardHeader>
				<CardBody>
					<RadioControl
						selected={ local.layout_mode || 'modern' }
						options={ [
							{ label: __( 'Modern layouts (recommended)', 'fcrm-enhancement-suite' ), value: 'modern' },
							{ label: __( 'FireHawk original layout (legacy)', 'fcrm-enhancement-suite' ), value: 'firehawk' },
						] }
						onChange={ ( value ) => {
							const merged = { ...settings, ...local, layout_mode: value };
							setLocal( merged );
							setSettings( merged );
							saveSettings( merged );
						} }
					/>
					<p style={ { margin: '8px 0 0', fontSize: '12px', color: '#646970' } }>
						{ __(
							'Modern uses our card-based grid and single tribute layouts. FireHawk original passes through to the unmodified FireHawk layout with optional colour overrides on the Styling tab.',
							'fcrm-enhancement-suite'
						) }
					</p>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader>
					<strong>{ __( 'Grid Layout', 'fcrm-enhancement-suite' ) }</strong>
				</CardHeader>
				<CardBody>
					{ ! isModern && (
						<p style={ { margin: '0 0 8px', fontSize: '12px', color: '#646970' } }>
							{ __( 'Switch to Modern layouts above to edit these options.', 'fcrm-enhancement-suite' ) }
						</p>
					) }
					<SelectControl
						label={ __( 'Grid Layout (Tribute Lists)', 'fcrm-enhancement-suite' ) }
						value={ local.active_layout }
						options={ gridLayouts }
						onChange={ ( v ) => update( 'active_layout', v ) }
						disabled={ ! isModern }
					/>
					{ local.active_layout !== 'minimal' && (
						<SelectControl
							label={ __( 'Card Style', 'fcrm-enhancement-suite' ) }
							value={ local.layout_card_style }
							options={ cardStyles }
							onChange={ ( v ) => update( 'layout_card_style', v ) }
							disabled={ ! isModern }
						/>
					) }
					{ local.active_layout !== 'minimal' && (
						<SelectControl
							label={ __( 'Grid Columns', 'fcrm-enhancement-suite' ) }
							value={ local.layout_grid_columns }
							options={ gridColumnOptions }
							onChange={ ( v ) => update( 'layout_grid_columns', v ) }
							disabled={ ! isModern }
						/>
					) }
					<NumberControl
						label={ __( 'Default Items Per Page', 'fcrm-enhancement-suite' ) }
						help={ __( 'Number of tributes shown before "Load More" button. Overridable per shortcode with size="X".', 'fcrm-enhancement-suite' ) }
						value={ local.layout_default_page_size }
						min={ 1 }
						max={ 50 }
						onChange={ ( v ) => update( 'layout_default_page_size', parseInt( v, 10 ) || 12 ) }
						disabled={ ! isModern }
					/>
					<NumberControl
						label={ __( '"Load More" Page Size', 'fcrm-enhancement-suite' ) }
						help={ __( 'Additional tributes loaded per click.', 'fcrm-enhancement-suite' ) }
						value={ local.layout_load_more_size }
						min={ 1 }
						max={ 50 }
						onChange={ ( v ) => update( 'layout_load_more_size', parseInt( v, 10 ) || 8 ) }
						disabled={ ! isModern }
					/>
				</CardBody>
			</Card>

			<Card style={ { margin: 0 } }>
				<CardHeader>
					<strong>{ __( 'Single Tribute Layout', 'fcrm-enhancement-suite' ) }</strong>
				</CardHeader>
				<CardBody>
					<SelectControl
						label={ __( 'Single Tribute Layout', 'fcrm-enhancement-suite' ) }
						value={ local.active_single_layout }
						options={ singleLayouts }
						onChange={ ( v ) => update( 'active_single_layout', v ) }
						disabled={ ! isModern }
					/>
				</CardBody>
			</Card>

			<Button variant="primary" onClick={ save } isBusy={ isSaving } disabled={ isSaving }>
				{ __( 'Save Changes', 'fcrm-enhancement-suite' ) }
			</Button>
		</div>
	);
}

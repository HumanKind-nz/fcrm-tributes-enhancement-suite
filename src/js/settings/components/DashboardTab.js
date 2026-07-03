/**
 * Dashboard tab — read-only status overview.
 *
 * Shows the self-detected state of each capability. No toggles: features
 * are always-on and self-gate, or are chosen via layout mode on the
 * Layouts tab. Status rows are computed server-side (status-checks.php)
 * and passed through window.fcrmEnhancementSuite.status.
 */
import { Card, CardBody, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const DOT_COLOURS = {
	ok: '#00a32a',
	info: '#646970',
	warning: '#dba617',
	inactive: '#c3c4c7',
};

function StatusRow( { row } ) {
	return (
		<li
			style={ {
				display: 'flex',
				alignItems: 'baseline',
				gap: '10px',
				padding: '8px 0',
				borderBottom: '1px solid #f0f0f1',
			} }
		>
			<span
				aria-hidden="true"
				style={ {
					flex: '0 0 auto',
					width: '8px',
					height: '8px',
					borderRadius: '50%',
					transform: 'translateY(1px)',
					background: DOT_COLOURS[ row.state ] || DOT_COLOURS.info,
				} }
			/>
			<span style={ { flex: '0 0 160px', fontWeight: 600 } }>{ row.label }</span>
			<span style={ { color: '#50575e' } }>{ row.detail }</span>
		</li>
	);
}

export default function DashboardTab( { settings } ) {
	const serverStatus = window.fcrmEnhancementSuite?.status || [];
	const mode = settings?.layout_mode || 'modern';

	// The layout row reflects the live in-app setting so it updates immediately
	// when the user changes mode on the Layouts tab. The localized snapshot is
	// computed at page load; the other rows depend on other plugins / server
	// state that cannot change without a reload, so the snapshot is fine there.
	const status = serverStatus.map( ( row ) =>
		row.key === 'layout'
			? {
					...row,
					detail:
						mode === 'modern'
							? __( 'Modern layouts active', 'fcrm-enhancement-suite' )
							: __( 'FireHawk original layout (legacy)', 'fcrm-enhancement-suite' ),
			  }
			: row
	);

	return (
		<div style={ { paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '12px' } }>
			<Notice status="info" isDismissible={ false }>
				{ __(
					'These features run automatically and switch themselves on only when their requirements are met. Choose your layout on the Layouts tab.',
					'fcrm-enhancement-suite'
				) }
			</Notice>

			<Card>
				<CardBody>
					<h2 style={ { marginTop: 0 } }>{ __( 'Plugin status', 'fcrm-enhancement-suite' ) }</h2>
					<ul style={ { margin: 0, padding: 0, listStyle: 'none' } }>
						{ status.map( ( row ) => (
							<StatusRow key={ row.key } row={ row } />
						) ) }
					</ul>
				</CardBody>
			</Card>

			<Card>
				<CardBody>
					<p style={ { margin: 0, color: '#646970', fontSize: '13px' } }>
						{ __(
							'Always on: conditional asset loading (~430KB saved on non-tribute pages), script optimisation, a loading spinner on tribute grids while data fetches from FireHawk, and flower delivery disabled site-wide. To re-enable flower delivery, use the fcrm_disable_flower_delivery filter.',
							'fcrm-enhancement-suite'
						) }
					</p>
				</CardBody>
			</Card>
		</div>
	);
}

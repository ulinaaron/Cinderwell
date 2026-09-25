import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/editor';
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { dispatch, useSelect } from '@wordpress/data';
import {
	registerBlockVariation,
	store as blocksStore,
} from '@wordpress/blocks';
import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
} from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Notice,
	TextControl,
	ToolbarButton,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	DynamicDataPicker,
	getPreviewValue,
} from '../shared/dynamic-data-picker';
import { DynamicDataIcon } from '../shared/dynamic-data-icon';
import {
	ResponsiveVisibilityPanel,
	resolveResponsiveVisibility,
	responsiveVisibilityLabels,
} from '../shared/responsive-visibility';
import {
	BackgroundControls,
	BlockIdentity,
	getSpacingClassName,
	getTypographyClassName,
	LayoutControls,
	SegmentedControl,
	TypographyControls,
} from '../shared/inspector-controls';
import { ConditionsPanel } from '../shared/conditions-panel';
import {
	applyEditorAccessClasses,
	canEditControl,
	editorAccessPolicy,
	filterEditorAccessChanges,
} from '../shared/editor-access';
import { applyColorRegistryPreview, TOKEN_CHANGE_EVENT } from '../shared/color-registry';
import { BlockSettingsClipboardMenu } from '../shared/block-settings-clipboard';
import './style.css';
import '../modules/animations/editor';
import '../shared/page-header-panel';
import './content-fields';

applyEditorAccessClasses();

// Stable editor-control contract for Cinderwell add-ons.
window.cinderwellEditorComponents = {
	...( window.cinderwellEditorComponents || {} ),
	BackgroundControls,
	BlockIdentity,
	ConditionsPanel,
	getSpacingClassName,
	getTypographyClassName,
	LayoutControls,
	ResponsiveVisibilityPanel,
	SegmentedControl,
	TypographyControls,
};

const teamsSettings = window.cinderwellEditorSettings?.addons?.teams;
if ( teamsSettings?.enabled ) {
	registerBlockVariation( 'cinderwell/loop', {
		name: 'people',
		title: teamsSettings.pluralLabel,
		description: __(
			'Display people, optionally filtered by category.',
			'cinderwell'
		),
		icon: 'groups',
		scope: [ 'inserter' ],
		attributes: {
			variation: 'people',
			postType: teamsSettings.postType,
			taxonomy: teamsSettings.taxonomy,
			heading: teamsSettings.pluralLabel,
			order: 'asc',
			orderBy: 'menu_order',
			showDate: false,
			showTerms: false,
			showExcerpt: false,
			showReadMore: false,
			showTeamPosition: true,
			imageAspect: 'portrait',
			linkBehavior: 'modal',
		},
		isActive: ( blockAttributes ) => blockAttributes.variation === 'people',
	} );
}

const portfolioSettings = window.cinderwellEditorSettings?.addons?.portfolio;
if ( portfolioSettings?.enabled ) {
	registerBlockVariation( 'cinderwell/loop', {
		name: 'portfolio',
		title: __( 'Portfolio', 'cinderwell' ),
		description: __(
			'Display portfolio items, optionally filtered by category.',
			'cinderwell'
		),
		icon: 'portfolio',
		scope: [ 'inserter' ],
		attributes: {
			variation: 'portfolio',
			postType: portfolioSettings.postType,
			taxonomy: portfolioSettings.taxonomy,
			heading: __( 'Portfolio', 'cinderwell' ),
			order: 'asc',
			orderBy: 'menu_order',
			showDate: false,
			showTerms: true,
			showExcerpt: true,
			showReadMore: true,
			readMoreLabel: __( 'View project', 'cinderwell' ),
			showPortfolioDetails: true,
			imageAspect: 'landscape',
			linkBehavior: portfolioSettings.itemsPublic ? 'page' : 'none',
		},
		isActive: ( blockAttributes ) =>
			blockAttributes.variation === 'portfolio',
	} );
}

const locationsSettings = window.cinderwellEditorSettings?.addons?.locations;
if ( locationsSettings?.enabled ) {
	registerBlockVariation( 'cinderwell/loop', {
		name: 'locations',
		title: __( 'Locations', 'cinderwell' ),
		description: __( 'Display company locations.', 'cinderwell' ),
		icon: 'location-alt',
		scope: [ 'inserter' ],
		attributes: {
			variation: 'locations',
			postType: locationsSettings.postType,
			heading: __( 'Locations', 'cinderwell' ),
			order: 'asc',
			orderBy: 'menu_order',
			showDate: false,
			showTerms: false,
			showExcerpt: true,
			showReadMore: locationsSettings.locationsPublic,
			readMoreLabel: __( 'View location', 'cinderwell' ),
			imageAspect: 'landscape',
			linkBehavior: locationsSettings.locationsPublic ? 'page' : 'none',
		},
		isActive: ( blockAttributes ) =>
			blockAttributes.variation === 'locations',
	} );
}

const addResponsiveVisibilityAttribute = ( settings, name ) => {
	if ( ! name?.startsWith( 'cinderwell/' ) ) return settings;
	return {
		...settings,
		attributes: {
			...settings.attributes,
			responsiveVisibility: { type: 'object', default: {} },
		},
	};
};

addFilter(
	'blocks.registerBlockType',
	'cinderwell/responsive-visibility-attribute',
	addResponsiveVisibilityAttribute
);
dispatch( blocksStore ).reapplyBlockTypeFilters();

const dynamicSlots = {
	'cinderwell/hero': [
		'eyebrow',
		'heading',
		'subheading',
		'caption',
		'footnote',
	],
	'cinderwell/body': [
		'eyebrow',
		'heading',
		'byline',
		'bodyContent',
		'pullquote',
		'footnote',
	],
	'cinderwell/cta': [ 'eyebrow', 'heading', 'bodyContent', 'footnote' ],
	'cinderwell/image-text': [
		'eyebrow',
		'heading',
		'caption',
		'bodyContent',
		'footnote',
	],
	'cinderwell/quote': [ 'quote', 'attribution', 'byline', 'context' ],
	'cinderwell/gallery': [ 'eyebrow', 'heading', 'caption', 'footnote' ],
	'cinderwell/card-grid': [ 'eyebrow', 'heading', 'footnote' ],
	'cinderwell/loop': [ 'eyebrow', 'heading' ],
	'cinderwell/faq': [ 'eyebrow', 'heading', 'footnote' ],
	'cinderwell/accordion': [ 'eyebrow', 'heading', 'footnote' ],
	'cinderwell/tabs': [ 'eyebrow', 'heading', 'footnote' ],
	'cinderwell/image': [ 'caption' ],
	'cinderwell/heading': [ 'content' ],
	'cinderwell/note': [ 'content' ],
	'cinderwell/button': [ 'text' ],
	'cinderwell/link': [ 'text' ],
};

const labelForSlot = ( slot ) =>
	slot
		.replace( /([A-Z])/g, ' $1' )
		.replace( /^./, ( character ) => character.toUpperCase() );

const CinderwellBlockEnhancements = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { name, attributes, setAttributes } = props;
		const slots = ( dynamicSlots[ name ] || [] )
			.filter( ( slot ) =>
				Object.prototype.hasOwnProperty.call( attributes, slot )
			)
			.map( ( slot ) => ( {
				value: slot,
				label: labelForSlot( slot ),
			} ) );
		const [ pickerAnchor, setPickerAnchor ] = useState( null );
		const [ pickerSlot, setPickerSlot ] = useState( '' );
		const liveValues = useSelect(
			( registrySelect ) => ( {
				post_title:
					registrySelect( 'core/editor' )?.getEditedPostAttribute(
						'title'
					) || '',
				post_excerpt:
					registrySelect( 'core/editor' )?.getEditedPostAttribute(
						'excerpt'
					) || '',
			} ),
			[]
		);
		const selectedTextSlot = useSelect(
			( registrySelect ) => {
				const selection =
					registrySelect(
						'core/block-editor'
					)?.getSelectionStart?.();
				return selection?.clientId === props.clientId
					? selection.attributeKey
					: '';
			},
			[ props.clientId ]
		);
		const visibility = attributes.visibility || 'always';
		const conditionLabel = ( window.cinderwellEditorSettings?.conditions ||
			{} )[ visibility ]?.label;
		const editorDevice = useSelect(
			( registrySelect ) =>
				registrySelect( 'core/editor' )?.getDeviceType?.() || 'Desktop',
			[]
		).toLowerCase();
		const resolvedBlockVisibility = resolveResponsiveVisibility(
			attributes.responsiveVisibility?.block
		);
		const hiddenOnEditorDevice =
			resolvedBlockVisibility[ editorDevice ] === 'hide';

		if ( ! name?.startsWith( 'cinderwell/' ) ) {
			return <BlockEdit { ...props } />;
		}

		const guardedSetAttributes = ( changes ) => {
			const allowedChanges = filterEditorAccessChanges(
				changes,
				attributes
			);
			if ( Object.keys( allowedChanges ).length > 0 )
				setAttributes( allowedChanges );
		};

		const previewAttributes = { ...attributes };
		Object.entries( attributes.dynamicData || {} ).forEach(
			( [ slot, binding ] ) => {
				if (
					binding?.source &&
					binding.source !== 'static' &&
					Object.prototype.hasOwnProperty.call(
						previewAttributes,
						slot
					)
				) {
					const sourceLabel = Object.values(
						window.cinderwellEditorSettings?.dataSources || {}
					)
						.flat()
						.find(
							( source ) => source.key === binding.source
						)?.label;
					previewAttributes[ slot ] = getPreviewValue(
						binding.source,
						binding.field,
						binding.fallback,
						sourceLabel,
						liveValues
					);
				}
			}
		);

		return (
			<>
				<BlockControls group="block">
					{ canEditControl( 'layout' ) && (
						<AlignmentToolbar
							value={ attributes.align }
							onChange={ ( align ) =>
								guardedSetAttributes( { align } )
							}
						/>
					) }
					{ canEditControl( 'advanced' ) && slots.length > 0 && (
						<ToolbarButton
							icon={ DynamicDataIcon }
							label={ __( 'Dynamic data', 'cinderwell' ) }
							isActive={
								Object.keys( attributes.dynamicData || {} )
									.length > 0
							}
							onClick={ ( event ) => {
								setPickerSlot(
									slots.some(
										( item ) =>
											item.value === selectedTextSlot
									)
										? selectedTextSlot
										: ''
								);
								setPickerAnchor( event.currentTarget );
							} }
						/>
					) }
				</BlockControls>
				{ visibility !== 'always' && (
					<div className="cw-condition-preview">
						<strong>
							{ __( 'Conditions applied:', 'cinderwell' ) }
						</strong>
						{ conditionLabel }
					</div>
				) }
				<BlockEdit
					{ ...props }
					attributes={ previewAttributes }
					setAttributes={ guardedSetAttributes }
				/>
				<InspectorControls>
					{ editorAccessPolicy.isRestricted && (
						<Notice
							status="info"
							isDismissible={ false }
							className="cw-editor-access-notice"
						>
							{ __(
								'Some editing controls are managed by Editor Access.',
								'cinderwell'
							) }
						</Notice>
					) }
					{ canEditControl( 'advanced' ) && (
						<ResponsiveVisibilityPanel
							attributes={ attributes }
							setAttributes={ guardedSetAttributes }
						/>
					) }
				</InspectorControls>
				{ hiddenOnEditorDevice && (
					<div className="cw-responsive-visibility-preview">
						<strong>
							{ __( 'Hidden on this device', 'cinderwell' ) }
						</strong>
						<span>
							{ __(
								'The block remains visible in the editor so it can still be selected.',
								'cinderwell'
							) }
						</span>
					</div>
				) }
				{ canEditControl( 'advanced' ) && pickerAnchor && (
					<DynamicDataPicker
						anchor={ pickerAnchor }
						slots={ slots }
						initialSlot={ pickerSlot }
						value={ attributes.dynamicData || {} }
						slotValues={ attributes }
						onChange={ ( dynamicData ) =>
							guardedSetAttributes( { dynamicData } )
						}
						onPreviewChange={ ( previewSlot, previewValue ) =>
							guardedSetAttributes( {
								[ previewSlot ]: previewValue,
							} )
						}
						onClose={ () => setPickerAnchor( null ) }
					/>
				) }
			</>
		);
	},
	'CinderwellBlockEnhancements'
);

addFilter(
	'editor.BlockEdit',
	'cinderwell/dynamic-data-and-conditions',
	CinderwellBlockEnhancements
);

const CinderwellResponsiveBlockListBlock = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const editorDevice = useSelect(
			( select ) =>
				select( 'core/editor' )?.getDeviceType?.() || 'Desktop',
			[]
		).toLowerCase();
		if ( ! props.name?.startsWith( 'cinderwell/' ) )
			return <BlockListBlock { ...props } />;

		const responsiveVisibility =
			props.attributes.responsiveVisibility || {};
		const classes = [];
		if (
			resolveResponsiveVisibility( responsiveVisibility.block )[
				editorDevice
			] === 'hide'
		) {
			classes.push( 'cw-responsive-preview-hidden' );
		}
		Object.entries( responsiveVisibility.sections || {} ).forEach(
			( [ section, values ] ) => {
				if (
					responsiveVisibilityLabels[ section ] &&
					resolveResponsiveVisibility( values )[ editorDevice ] ===
						'hide'
				) {
					classes.push( `cw-responsive-preview-hide-${ section }` );
				}
			}
		);

		return (
			<BlockListBlock
				{ ...props }
				className={ `${ props.className || '' } ${ classes.join(
					' '
				) }`.trim() }
			/>
		);
	},
	'CinderwellResponsiveBlockListBlock'
);

addFilter(
	'editor.BlockListBlock',
	'cinderwell/responsive-visibility-preview',
	CinderwellResponsiveBlockListBlock
);

const tokenIcon = (
	<svg
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<circle cx="7" cy="7" r="3" fill="currentColor" />
		<circle
			cx="17"
			cy="7"
			r="3"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.8"
		/>
		<circle
			cx="7"
			cy="17"
			r="3"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.8"
		/>
		<circle cx="17" cy="17" r="3" fill="currentColor" />
	</svg>
);

const groupDefinitions = [
	{ key: 'colors', label: __( 'Colors', 'cinderwell' ) },
	{ key: 'buttons', label: __( 'Buttons', 'cinderwell' ) },
	{ key: 'fonts', label: __( 'Font Families', 'cinderwell' ) },
	{ key: 'type-scale', label: __( 'Typography', 'cinderwell' ) },
	{ key: 'spacing', label: __( 'Spacing', 'cinderwell' ) },
	{ key: 'layout', label: __( 'Layout', 'cinderwell' ) },
	{ key: 'widths', label: __( 'Widths', 'cinderwell' ) },
	{ key: 'shape', label: __( 'Shape', 'cinderwell' ) },
	{ key: 'elevation', label: __( 'Elevation', 'cinderwell' ) },
	{ key: 'motion', label: __( 'Motion', 'cinderwell' ) },
	{ key: 'images', label: __( 'Images', 'cinderwell' ) },
];

const TokenRow = ( { token, canManage, onChange, onReset } ) => {
	const isModified = token.value !== token.default;
	const canEdit = canManage && token.editable !== false;
	const colorPickerValue = /^#[0-9a-f]{6}$/i.test( token.value )
		? token.value
		: token.default;

	return (
		<div className="cw-token-view__row">
			<div className="cw-token-view__identity">
				{ token.type === 'color' && (
					<input
						type="color"
						className="cw-token-view__swatch"
						value={ colorPickerValue }
						onChange={ ( event ) => onChange( event.target.value ) }
						aria-label={ `${ token.label } ${ __(
							'color',
							'cinderwell'
						) }` }
						disabled={ ! canEdit }
					/>
				) }
				{ token.type === 'derived-color' && (
					<span
						className="cw-token-view__swatch cw-token-view__swatch--derived"
						style={ { background: token.value } }
						aria-hidden="true"
					/>
				) }
				<div>
					<strong>{ token.label }</strong>
					<code>{ token.cssVariable }</code>
				</div>
				{ isModified && (
					<span className="cw-token-view__badge">
						{ __( 'Custom', 'cinderwell' ) }
					</span>
				) }
			</div>
			{ canEdit && token.type === 'choice' ? (
				<div className="cw-token-view__controls">
					<div className="cw-token-view__choices">
						{ token.options.map( ( option ) => (
							<Button
								key={ option.value }
								variant={
									token.value === option.value
										? 'primary'
										: 'secondary'
								}
								aria-pressed={ token.value === option.value }
								onClick={ () => onChange( option.value ) }
							>
								{ option.label }
							</Button>
						) ) }
					</div>
					<Button
						icon="undo"
						label={ __( 'Reset to default', 'cinderwell' ) }
						size="compact"
						variant="tertiary"
						onClick={ onReset }
						disabled={ ! isModified }
					/>
				</div>
			) : canEdit ? (
				<div className="cw-token-view__controls">
					<TextControl
						label={ `${ token.label } ${ __(
							'value',
							'cinderwell'
						) }` }
						hideLabelFromVision
						value={ token.value }
						placeholder={ token.default }
						onChange={ onChange }
					/>
					<Button
						icon="undo"
						label={ __( 'Reset to default', 'cinderwell' ) }
						size="compact"
						variant="tertiary"
						onClick={ onReset }
						disabled={ ! isModified }
					/>
				</div>
			) : (
				<code className="cw-token-view__value">{ token.value }</code>
			) }
			<p>{ token.description }</p>
		</div>
	);
};

const getValueMap = ( tokens ) =>
	Object.fromEntries( tokens.map( ( token ) => [ token.key, token.value ] ) );

const applyTokenPreview = ( tokens ) => {
	const editorDocument = document.querySelector(
		'iframe[name="editor-canvas"]'
	)?.contentDocument;
	const roots = [
		document.documentElement,
		editorDocument?.documentElement,
	].filter( Boolean );

	roots.forEach( ( root ) => {
		tokens.forEach( ( token ) =>
			root.style.setProperty( token.cssVariable, token.value )
		);
	} );
};

const TokenGroup = ( { group, children } ) => {
	const [ isOpen, setIsOpen ] = useState( group.key === 'colors' );

	return (
		<details
			className="cw-token-view__group"
			open={ isOpen }
			onToggle={ ( event ) => setIsOpen( event.currentTarget.open ) }
		>
			<summary>
				<span>{ group.label }</span>
				<span className="cw-token-view__count">
					{ group.items.length }
				</span>
			</summary>
			<div className="cw-token-view__group-body">{ children }</div>
		</details>
	);
};

const TokenSidebar = () => {
	const settings = window.cinderwellEditorSettings || {};
	const [ tokens, setTokens ] = useState( settings.tokens || [] );
	const [ savedValues, setSavedValues ] = useState( () =>
		getValueMap( settings.tokens || [] )
	);
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );
	const canManage = Boolean( settings.canManage );

	useEffect( () => {
		settings.tokens = tokens;
		applyTokenPreview( tokens );
		applyColorRegistryPreview( tokens );
		window.dispatchEvent( new CustomEvent( TOKEN_CHANGE_EVENT, {
			detail: { tokens },
		} ) );
	}, [ settings, tokens ] );
	const hasChanges = tokens.some(
		( token ) => token.value !== savedValues[ token.key ]
	);
	const claimedKeys = new Set();
	const groups = groupDefinitions.map( ( group ) => {
		const items = tokens.filter(
			( token ) => token.category === group.key
		);
		items.forEach( ( token ) => claimedKeys.add( token.key ) );
		return { ...group, items };
	} );
	const otherTokens = tokens.filter(
		( token ) => ! claimedKeys.has( token.key )
	);

	if ( otherTokens.length ) {
		groups.push( {
			key: 'other',
			label: __( 'Other', 'cinderwell' ),
			items: otherTokens,
		} );
	}

	const updateToken = ( key, value ) => {
		setTokens( ( current ) =>
			current.map( ( token ) =>
				token.key === key ? { ...token, value } : token
			)
		);
		setNotice( null );
	};

	const saveTokens = async () => {
		setIsSaving( true );
		setNotice( null );

		try {
			const overrides = Object.fromEntries(
				tokens.map( ( token ) => [
					token.key,
					token.value.trim() === token.default
						? ''
						: token.value.trim(),
				] )
			);
			const response = await apiFetch( {
				path: '/cinderwell/v1/tokens',
				method: 'POST',
				data: { tokens: overrides },
			} );
			const savedTokens = response.tokens || tokens;
			setTokens( savedTokens );
			setSavedValues( getValueMap( savedTokens ) );
			applyTokenPreview( savedTokens );
			setNotice( {
				status: 'success',
				message: __( 'Design tokens saved.', 'cinderwell' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error?.message ||
					__( 'Design tokens could not be saved.', 'cinderwell' ),
			} );
		} finally {
			setIsSaving( false );
		}
	};

	return (
		<PluginSidebar
			name="design-tokens"
			title={ __( 'Design Tokens', 'cinderwell' ) }
			icon={ tokenIcon }
			className="cw-token-view"
		>
			<div className="cw-token-view__intro">
				<p>
					{ canManage
						? __(
								'Edit the design values used by every Cinderwell block.',
								'cinderwell'
						  )
						: __(
								'The resolved design values currently used by Cinderwell blocks.',
								'cinderwell'
						  ) }
				</p>
				{ notice && (
					<Notice status={ notice.status } isDismissible={ false }>
						{ notice.message }
					</Notice>
				) }
				{ canManage && (
					<div className="cw-token-view__actions">
						<Button
							variant="primary"
							onClick={ saveTokens }
							isBusy={ isSaving }
							disabled={ isSaving || ! hasChanges }
						>
							{ isSaving
								? __( 'Saving…', 'cinderwell' )
								: __( 'Save tokens', 'cinderwell' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => {
								setTokens( ( current ) =>
									current.map( ( token ) => ( {
										...token,
										value: token.default,
									} ) )
								);
								setNotice( null );
							} }
							disabled={
								isSaving ||
								! tokens.some(
									( token ) => token.value !== token.default
								)
							}
						>
							{ __( 'Reset all', 'cinderwell' ) }
						</Button>
					</div>
				) }
			</div>
			{ groups
				.filter( ( group ) => group.items.length )
				.map( ( group ) => (
					<TokenGroup group={ group } key={ group.key }>
						{ group.items.map( ( token ) => (
							<TokenRow
								token={ token }
								canManage={ canManage }
								onChange={ ( value ) =>
									updateToken( token.key, value )
								}
								onReset={ () =>
									updateToken( token.key, token.default )
								}
								key={ token.key }
							/>
						) ) }
					</TokenGroup>
				) ) }
		</PluginSidebar>
	);
};

registerPlugin( 'cinderwell-token-view', {
	icon: tokenIcon,
	render: TokenSidebar,
} );

if (
	window.cinderwellEditorSettings?.features?.blockSettingsClipboard !== false
) {
	registerPlugin( 'cinderwell-block-settings-clipboard', {
		render: BlockSettingsClipboardMenu,
	} );
}

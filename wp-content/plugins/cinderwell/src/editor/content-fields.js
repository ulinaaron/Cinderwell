import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	CheckboxControl,
	SelectControl,
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const optionList = ( options = {} ) =>
	Object.entries( options ).map( ( [ value, label ] ) => ( {
		value,
		label: typeof label === 'object' ? label.label || value : label,
	} ) );

const FieldControl = ( { field, value, onChange } ) => {
	const label = field.required ? `${ field.label } *` : field.label;
	const help = [ field.required ? __( 'Required.', 'cinderwell' ) : '', field.description || '' ].filter( Boolean ).join( ' ' ) || undefined;

	if ( field.type === 'checkbox' ) {
		return (
			<CheckboxControl
				label={ label }
				help={ help }
				checked={ Boolean( value ) }
				required={ Boolean( field.required ) }
				onChange={ onChange }
			/>
		);
	}

	if ( [ 'select', 'segmented', 'color_chips', 'post', 'posts', 'term', 'terms', 'user', 'users' ].includes( field.type ) ) {
		const isReference = [ 'post', 'posts', 'term', 'terms', 'user', 'users' ].includes( field.type );
		const isMultiple = Boolean( field.multiple );
		const currentValue = isMultiple
			? ( Array.isArray( value ) ? value.map( String ) : [] )
			: String( value ?? field.default ?? '' );
		return (
			<SelectControl
				label={ label }
				help={ help }
				value={ currentValue }
				multiple={ isMultiple }
				required={ Boolean( field.required ) }
				options={ [ ...( isMultiple ? [] : [ { value: '', label: __( 'Select…', 'cinderwell' ) } ] ), ...optionList( field.options ) ] }
				onChange={ ( next ) => onChange( isReference ? ( isMultiple ? next.map( Number ) : ( next ? Number( next ) : 0 ) ) : next ) }
			/>
		);
	}

	if ( field.type === 'checkboxes' ) {
		const selected = Array.isArray( value ) ? value : [];
		return (
			<fieldset className="cw-content-field__choices">
				<legend>{ label }</legend>
				{ help && <p>{ help }</p> }
				{ optionList( field.options ).map( ( option ) => (
					<CheckboxControl
						key={ option.value }
						label={ option.label }
						checked={ selected.includes( option.value ) }
						onChange={ ( checked ) =>
							onChange(
								checked
									? [ ...selected, option.value ]
									: selected.filter( ( item ) => item !== option.value )
							)
						}
					/>
				) ) }
			</fieldset>
		);
	}

	if ( field.type === 'textarea' ) {
		return (
			<TextareaControl
				label={ label }
				help={ help }
				value={ value ?? '' }
				required={ Boolean( field.required ) }
				onChange={ onChange }
			/>
		);
	}

	if ( field.type === 'media' ) {
		return (
			<BaseControl label={ label } help={ help }>
				<MediaUploadCheck>
					<MediaUpload
						onSelect={ ( media ) => onChange( media.id ) }
						allowedTypes={ [ 'image' ] }
						value={ Number( value ) || 0 }
						render={ ( { open } ) => (
							<div className="cw-content-field__media-actions">
								<Button variant="secondary" onClick={ open }>
									{ value
										? __( 'Replace image', 'cinderwell' )
										: __( 'Choose image', 'cinderwell' ) }
								</Button>
								{ Boolean( value ) && (
									<Button variant="tertiary" isDestructive onClick={ () => onChange( 0 ) }>
										{ __( 'Remove', 'cinderwell' ) }
									</Button>
								) }
							</div>
						) }
					/>
				</MediaUploadCheck>
			</BaseControl>
		);
	}

	const inputType = [ 'email', 'url', 'tel', 'date', 'datetime', 'datetime-local', 'number' ].includes( field.type )
		? ( field.type === 'datetime' ? 'datetime-local' : field.type )
		: 'text';
	return (
		<TextControl
			label={ label }
			help={ help }
			type={ inputType }
			required={ Boolean( field.required ) }
			value={ value ?? '' }
			onChange={ ( next ) => onChange( inputType === 'number' && next !== '' ? Number( next ) : next ) }
		/>
	);
};

const ContentFieldPanels = () => {
	const groups = window.cinderwellEditorSettings?.contentFields?.groups || [];
	const postType = useSelect(
		( select ) => select( 'core/editor' )?.getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( ! groups.length || ! postType ) return null;

	return groups.map( ( group ) => (
		<PluginDocumentSettingPanel
			key={ group.id }
			name={ `cinderwell-fields-${ group.id.replace( /[^a-z0-9-]/gi, '-' ) }` }
			title={ group.label }
			className="cw-content-fields-panel"
		>
			{ group.description && <p>{ group.description }</p> }
			{ group.fields.map( ( field ) => (
				<FieldControl
					key={ field.key }
					field={ field }
					value={ meta?.[ field.key ] ?? field.default }
					onChange={ ( next ) => setMeta( { ...meta, [ field.key ]: next } ) }
				/>
			) ) }
		</PluginDocumentSettingPanel>
	) );
};

if ( window.cinderwellEditorSettings?.contentFields?.groups?.length ) {
	registerPlugin( 'cinderwell-content-fields', {
		render: ContentFieldPanels,
	} );
}

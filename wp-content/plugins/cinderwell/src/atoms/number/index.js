import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ConditionsPanel } from '../../shared/conditions-panel';
import { BlockIdentity, SegmentedControl, ToggleRow } from '../../shared/inspector-controls';
import metadata from './block.json';

const sizeOptions = [
    { value: 'sm', label: __( 'Small', 'cinderwell' ) },
    { value: 'md', label: __( 'Medium', 'cinderwell' ) },
    { value: 'lg', label: __( 'Large', 'cinderwell' ) },
    { value: 'xl', label: __( 'XL', 'cinderwell' ) },
];

const alignmentOptions = [
    { value: 'left', label: __( 'Left', 'cinderwell' ) },
    { value: 'center', label: __( 'Center', 'cinderwell' ) },
    { value: 'right', label: __( 'Right', 'cinderwell' ) },
];

const durationOptions = [
    { value: 'fast', label: __( 'Fast', 'cinderwell' ) },
    { value: 'standard', label: __( 'Standard', 'cinderwell' ) },
    { value: 'slow', label: __( 'Slow', 'cinderwell' ) },
];

const durationValues = {
    fast: 800,
    standard: 1200,
    slow: 1800,
};

const getNumericValue = ( value ) => {
    const normalized = String( value || '' ).replace( /[\s,]/g, '' );
    const parsed = Number( normalized );
    return Number.isFinite( parsed ) ? parsed : null;
};

const getDecimalPlaces = ( value ) => {
    const normalized = String( value || '' ).replace( /[\s,]/g, '' );
    const decimal = normalized.split( '.' )[ 1 ];
    return decimal ? Math.min( decimal.length, 4 ) : 0;
};

const getClassName = ( attributes ) => `cinderwell-number cinderwell-number--size-${ attributes.size || 'lg' } cinderwell-number--align-${ attributes.alignment || 'left' }`;

const NumberValue = ( { attributes, editable = false, onChange } ) => {
    const value = attributes.value || '';
    const visibleValue = editable ? (
        <RichText
            tagName="span"
            className="cinderwell-number__output"
            identifier="value"
            value={ value }
            onChange={ onChange }
            placeholder={ __( '100', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    ) : (
        <RichText.Content
            tagName="span"
            className="cinderwell-number__output"
            value={ value }
            data-cw-number-output
        />
    );

    return (
        <span className="cinderwell-number__value" aria-hidden={ editable ? undefined : 'true' }>
            { attributes.prefix && <span className="cinderwell-number__prefix">{ attributes.prefix }</span> }
            { visibleValue }
            { attributes.suffix && <span className="cinderwell-number__suffix">{ attributes.suffix }</span> }
        </span>
    );
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const blockProps = useBlockProps( { className: getClassName( attributes ) } );

        return (
            <>
                <InspectorControls>
                    <BlockIdentity
                        icon="#"
                        title={ __( 'Number', 'cinderwell' ) }
                        description={ __( 'Statistic or measurable result', 'cinderwell' ) }
                    />
                    <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                        <TextControl
                            label={ __( 'Prefix', 'cinderwell' ) }
                            value={ attributes.prefix || '' }
                            onChange={ ( prefix ) => setAttributes( { prefix } ) }
                            help={ __( 'Optional, such as $ or +.', 'cinderwell' ) }
                        />
                        <TextControl
                            label={ __( 'Suffix', 'cinderwell' ) }
                            value={ attributes.suffix || '' }
                            onChange={ ( suffix ) => setAttributes( { suffix } ) }
                            help={ __( 'Optional, such as %, ft, or +.', 'cinderwell' ) }
                        />
                    </PanelBody>
                    <PanelBody title={ __( 'Appearance', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-appearance">
                        <SegmentedControl
                            label={ __( 'Number size', 'cinderwell' ) }
                            value={ attributes.size || 'lg' }
                            options={ sizeOptions }
                            onChange={ ( size ) => setAttributes( { size } ) }
                        />
                        <SegmentedControl
                            label={ __( 'Alignment', 'cinderwell' ) }
                            value={ attributes.alignment || 'left' }
                            options={ alignmentOptions }
                            onChange={ ( alignment ) => setAttributes( { alignment } ) }
                        />
                    </PanelBody>
                    <PanelBody title={ __( 'Animation', 'cinderwell' ) } initialOpen={ false } className="cw-panel cw-access-animation">
                        <ToggleRow
                            label={ __( 'Count up when visible', 'cinderwell' ) }
                            checked={ attributes.animate !== false }
                            onChange={ ( animate ) => setAttributes( { animate } ) }
                        />
                        { attributes.animate !== false && (
                            <SegmentedControl
                                label={ __( 'Animation speed', 'cinderwell' ) }
                                value={ attributes.animationDuration || 'standard' }
                                options={ durationOptions }
                                onChange={ ( animationDuration ) => setAttributes( { animationDuration } ) }
                            />
                        ) }
                    </PanelBody>
                    <ConditionsPanel attributes={ attributes } setAttributes={ setAttributes } />
                </InspectorControls>
                <div { ...blockProps }>
                    <NumberValue
                        attributes={ attributes }
                        editable
                        onChange={ ( value ) => setAttributes( { value } ) }
                    />
                    <RichText
                        tagName="span"
                        className="cinderwell-number__label"
                        identifier="label"
                        value={ attributes.label }
                        onChange={ ( label ) => setAttributes( { label } ) }
                        placeholder={ __( 'Describe this number…', 'cinderwell' ) }
                        allowedFormats={ [] }
                    />
                </div>
            </>
        );
    },
    save: ( { attributes } ) => {
        const numericValue = getNumericValue( attributes.value );
        const duration = durationValues[ attributes.animationDuration ] || durationValues.standard;
        const accessibleValue = `${ attributes.prefix || '' }${ attributes.value || '' }${ attributes.suffix || '' }`;
        const blockProps = useBlockProps.save( {
            className: getClassName( attributes ),
            'data-cw-number': attributes.animate !== false && numericValue !== null ? 'true' : undefined,
            'data-cw-number-target': attributes.animate !== false && numericValue !== null ? numericValue : undefined,
            'data-cw-number-decimals': attributes.animate !== false && numericValue !== null ? getDecimalPlaces( attributes.value ) : undefined,
            'data-cw-number-duration': attributes.animate !== false && numericValue !== null ? duration : undefined,
        } );

        return (
            <div { ...blockProps }>
                <NumberValue attributes={ attributes } />
                <span className="screen-reader-text">{ accessibleValue }</span>
                <RichText.Content tagName="span" className="cinderwell-number__label" value={ attributes.label } />
            </div>
        );
    },
} );

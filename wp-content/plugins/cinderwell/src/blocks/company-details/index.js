import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
    BackgroundControls,
    BlockIdentity,
    LayoutControls,
    SectionToggles,
    SegmentedControl,
    TypographyControls,
    getHeadingTagName,
    getSpacingClassName,
    getTextStyleClassName,
    getTypographyClassName,
} from '../../shared/inspector-controls';
import metadata from './block.json';

const modes = {
    hours: {
        label: __( 'Hours', 'cinderwell' ),
        heading: __( 'Business hours', 'cinderwell' ),
        sections: [ 'hours', 'status', 'phone', 'contact' ],
    },
    contact: {
        label: __( 'Contact', 'cinderwell' ),
        heading: __( 'Contact us', 'cinderwell' ),
        sections: [ 'description', 'address', 'phone', 'email', 'contact', 'directions' ],
    },
    address: {
        label: __( 'Address', 'cinderwell' ),
        heading: __( 'Visit us', 'cinderwell' ),
        sections: [ 'address', 'phone', 'contact', 'directions' ],
    },
    all: {
        label: __( 'All details', 'cinderwell' ),
        heading: __( 'Company details', 'cinderwell' ),
        sections: [ 'description', 'address', 'phone', 'email', 'hours', 'status', 'contact', 'directions' ],
    },
};

const sectionAttributes = {
    description: 'showDescription',
    address: 'showAddress',
    phone: 'showPhone',
    email: 'showEmail',
    hours: 'showHours',
    status: 'showStatus',
    contact: 'showContact',
    directions: 'showDirections',
};

const sectionLabels = {
    description: __( 'Description', 'cinderwell' ),
    address: __( 'Address', 'cinderwell' ),
    phone: __( 'Phone', 'cinderwell' ),
    email: __( 'Email', 'cinderwell' ),
    hours: __( 'Weekly hours', 'cinderwell' ),
    status: __( 'Open/closed status', 'cinderwell' ),
    contact: __( 'Contact button', 'cinderwell' ),
    directions: __( 'Directions button', 'cinderwell' ),
};

const dayLabels = {
    monday: __( 'Monday', 'cinderwell' ),
    tuesday: __( 'Tuesday', 'cinderwell' ),
    wednesday: __( 'Wednesday', 'cinderwell' ),
    thursday: __( 'Thursday', 'cinderwell' ),
    friday: __( 'Friday', 'cinderwell' ),
    saturday: __( 'Saturday', 'cinderwell' ),
    sunday: __( 'Sunday', 'cinderwell' ),
};

const formatTime = ( value ) => {
    if ( ! /^\d{2}:\d{2}$/.test( value || '' ) ) return value || '';
    const [ hours, minutes ] = value.split( ':' ).map( Number );
    const date = new Date( 2000, 0, 1, hours, minutes );
    return new Intl.DateTimeFormat( undefined, { hour: 'numeric', minute: '2-digit' } ).format( date );
};

const HoursPreview = ( { schedule, note } ) => {
    const entries = Object.entries( dayLabels ).filter( ( [ day ] ) => schedule?.[ day ] );
    if ( ! entries.length ) {
        return <p className="cinderwell-company-details__empty">{ note || __( 'Add weekly hours in Company Details.', 'cinderwell' ) }</p>;
    }

    return <dl className="cinderwell-company-details__hours">{ entries.map( ( [ day, label ] ) => {
        const details = schedule[ day ];
        let value = __( 'Closed', 'cinderwell' );
        if ( details.status === 'all_day' ) value = __( 'Open 24 hours', 'cinderwell' );
        if ( details.status === 'open' ) value = ( details.periods || [] ).map( ( period ) => `${ formatTime( period.opens ) } – ${ formatTime( period.closes ) }` ).join( ', ' );
        return <div key={ day } className="cinderwell-company-details__hours-row"><dt>{ label }</dt><dd>{ value }{ details.note ? <small>{ details.note }</small> : null }</dd></div>;
    } ) }</dl>;
};

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const preview = window.cinderwellEditorSettings?.previewValues || {};
        const mode = modes[ attributes.contentMode ] || modes.hours;
        const sections = mode.sections.map( ( key ) => ( { key, label: sectionLabels[ key ] } ) );
        const values = Object.fromEntries( mode.sections.map( ( key ) => [ key, Boolean( attributes[ sectionAttributes[ key ] ] ) ] ) );
        const setSections = ( next ) => setAttributes( Object.fromEntries( Object.entries( next ).map( ( [ key, value ] ) => [ sectionAttributes[ key ], value ] ) ) );
        const isShown = ( key ) => mode.sections.includes( key ) && attributes[ sectionAttributes[ key ] ];
        const heading = attributes.heading || mode.heading;
        const address = preview.company_address || __( 'Company address', 'cinderwell' );
        const blockProps = useBlockProps( {
            className: `cinderwell-company-details cinderwell-company-details--mode-${ attributes.contentMode } cinderwell-company-details--layout-${ attributes.layout } cinderwell-company-details--bg-${ attributes.background } cinderwell-company-details--align-${ attributes.alignment }${ getSpacingClassName( attributes ) }${ getTypographyClassName( attributes ) }`,
        } );

        return <>
            <InspectorControls>
                <BlockIdentity icon="&#8962;" title={ __( 'Company Details', 'cinderwell' ) } description={ __( 'Shared company information', 'cinderwell' ) } />
                <PanelBody title={ __( 'Content', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-content">
                    <SegmentedControl label={ __( 'Details to show', 'cinderwell' ) } value={ attributes.contentMode } options={ Object.entries( modes ).map( ( [ value, item ] ) => ( { value, label: item.label } ) ) } onChange={ ( contentMode ) => setAttributes( { contentMode } ) } />
                    <SectionToggles sections={ [ { key: 'heading', label: __( 'Heading', 'cinderwell' ) }, ...sections ] } values={ { heading: attributes.showHeading, ...values } } onChange={ ( next ) => {
                        setAttributes( { showHeading: next.heading } );
                        const sectionValues = { ...next };
                        delete sectionValues.heading;
                        setSections( sectionValues );
                    } } />
                    { isShown( 'contact' ) && <TextControl label={ __( 'Contact button label', 'cinderwell' ) } value={ attributes.contactLabel } onChange={ ( contactLabel ) => setAttributes( { contactLabel } ) } /> }
                    { isShown( 'directions' ) && <TextControl label={ __( 'Directions button label', 'cinderwell' ) } value={ attributes.directionsLabel } onChange={ ( directionsLabel ) => setAttributes( { directionsLabel } ) } /> }
                </PanelBody>
                <PanelBody title={ __( 'Presentation', 'cinderwell' ) } initialOpen={ true } className="cw-panel cw-access-layout">
                    <SegmentedControl label={ __( 'Layout', 'cinderwell' ) } value={ attributes.layout } options={ [
                        { value: 'stacked', label: __( 'Stacked', 'cinderwell' ) },
                        { value: 'card', label: __( 'Card', 'cinderwell' ) },
                        { value: 'split', label: __( 'Split', 'cinderwell' ) },
                    ] } onChange={ ( layout ) => setAttributes( { layout } ) } />
                    <SegmentedControl label={ __( 'Alignment', 'cinderwell' ) } value={ attributes.alignment } options={ [
                        { value: 'left', label: __( 'Left', 'cinderwell' ) },
                        { value: 'center', label: __( 'Center', 'cinderwell' ) },
                        { value: 'right', label: __( 'Right', 'cinderwell' ) },
                    ] } onChange={ ( alignment ) => setAttributes( { alignment } ) } />
                </PanelBody>
                <LayoutControls attributes={ attributes } setAttributes={ setAttributes } showAlignment={ false } />
                <BackgroundControls value={ attributes.background } onChange={ ( background ) => setAttributes( { background } ) } attributes={ attributes } setAttributes={ setAttributes } />
                <TypographyControls attributes={ attributes } setAttributes={ setAttributes } sections={ [
                    { key: 'heading', label: __( 'Heading', 'cinderwell' ), enabled: attributes.showHeading },
                ] } />
            </InspectorControls>
            <section { ...blockProps }>
                <div className="cinderwell-company-details__inner" style={ { maxWidth: `var(--cw-width-${ attributes.width })` } }>
                    { attributes.showHeading && <RichText tagName={ getHeadingTagName( attributes.headingLevel ) } className={ `cinderwell-company-details__heading${ getTextStyleClassName( attributes, 'heading' ) }` } value={ attributes.heading } onChange={ ( value ) => setAttributes( { heading: value } ) } placeholder={ heading } allowedFormats={ [] } /> }
                    { isShown( 'description' ) && <p className="cinderwell-company-details__description">{ preview.company_description || __( 'Company description', 'cinderwell' ) }</p> }
                    <div className="cinderwell-company-details__content">
                        { ( isShown( 'address' ) || isShown( 'phone' ) || isShown( 'email' ) ) && <div className="cinderwell-company-details__contact">
                            { isShown( 'address' ) && <address>{ address }</address> }
                            { isShown( 'phone' ) && <span>{ preview.company_phone || __( 'Company phone', 'cinderwell' ) }</span> }
                            { isShown( 'email' ) && <span>{ preview.company_email || __( 'Company email', 'cinderwell' ) }</span> }
                        </div> }
                        { isShown( 'hours' ) && <div className="cinderwell-company-details__schedule">
                            { isShown( 'status' ) && <p className="cinderwell-company-details__status">{ __( 'Current open status appears on the published page.', 'cinderwell' ) }</p> }
                            <HoursPreview schedule={ preview.company_hours_schedule || {} } note={ preview.company_hours_note || '' } />
                        </div> }
                    </div>
                    { ( isShown( 'contact' ) || isShown( 'directions' ) ) && <div className="cinderwell-company-details__actions cinderwell-buttons">
                        { isShown( 'contact' ) && <span className="btn btn--primary">{ attributes.contactLabel || __( 'Contact us', 'cinderwell' ) }</span> }
                        { isShown( 'directions' ) && <span className="btn btn--secondary">{ attributes.directionsLabel || __( 'Get directions', 'cinderwell' ) }</span> }
                    </div> }
                </div>
            </section>
        </>;
    },
    save: () => null,
} );

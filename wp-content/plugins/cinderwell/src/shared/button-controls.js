import { Button, Popover, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { LinkSettingsControl } from './link-control';
import { IconPicker } from './icon-controls';
import { IconGlyph, iconOptions } from './icon-library';
import { normalizeEmailHref, normalizePhoneHref } from './button-utils';

const choiceOptions = [
    { value: 'link', label: __( 'Link', 'cinderwell' ) },
    { value: 'phone', label: __( 'Phone', 'cinderwell' ) },
    { value: 'email', label: __( 'Email', 'cinderwell' ) },
];

const quickIcons = [ 'phone', 'mail', 'arrow-right', 'external-link', 'download', 'check' ];

const PillControl = ( { label, value, options, onChange } ) => <div className="cw-field">
    { label && <div className="cw-field__label">{ label }</div> }
    <div className="cw-segmented">
        { options.map( ( option ) => <button
            type="button"
            key={ option.value }
            className={ value === option.value ? 'is-active' : '' }
            aria-pressed={ value === option.value }
            onClick={ () => onChange( option.value ) }
        >{ option.label }</button> ) }
    </div>
</div>;

export const ButtonDestinationControl = ( {
    destinationType = 'link',
    url = '',
    phoneNumber = '',
    emailAddress = '',
    opensInNewTab = false,
    dynamicData = {},
    onChange,
} ) => {
    const phoneHref = normalizePhoneHref( phoneNumber );
    const emailHref = normalizeEmailHref( emailAddress );

    return <div className="cw-button-destination">
        <PillControl
            label={ __( 'Destination', 'cinderwell' ) }
            value={ destinationType }
            options={ choiceOptions }
            onChange={ ( value ) => onChange( { destinationType: value } ) }
        />
        { destinationType === 'link' && <LinkSettingsControl
            url={ url }
            opensInNewTab={ opensInNewTab }
            dynamicData={ dynamicData }
            onChange={ onChange }
        /> }
        { destinationType === 'phone' && <TextControl
            label={ __( 'Phone number', 'cinderwell' ) }
            value={ phoneNumber }
            onChange={ ( value ) => onChange( { phoneNumber: value } ) }
            placeholder={ __( '(231) 555-5555', 'cinderwell' ) }
            help={ phoneNumber ? ( phoneHref || __( 'Enter a phone number with at least one digit.', 'cinderwell' ) ) : __( 'Use any familiar formatting. It will be normalized automatically.', 'cinderwell' ) }
            className={ phoneNumber && ! phoneHref ? 'has-error' : '' }
        /> }
        { destinationType === 'email' && <TextControl
            type="email"
            label={ __( 'Email address', 'cinderwell' ) }
            value={ emailAddress }
            onChange={ ( value ) => onChange( { emailAddress: value } ) }
            placeholder={ __( 'hello@example.com', 'cinderwell' ) }
            help={ emailAddress ? ( emailHref || __( 'Enter a complete email address.', 'cinderwell' ) ) : __( 'The mailto: prefix is added automatically.', 'cinderwell' ) }
            className={ emailAddress && ! emailHref ? 'has-error' : '' }
        /> }
    </div>;
};

export const ButtonIconControl = ( { icon = '', iconPosition = 'before', onChange } ) => {
    const [ anchor, setAnchor ] = useState( null );
    const selectedLabel = iconOptions.find( ( option ) => option.value === icon )?.label || __( 'Icon', 'cinderwell' );

    return <div className="cw-button-icon-control">
        <div className="cw-field__label">{ __( 'Icon', 'cinderwell' ) }</div>
        <div className="cw-button-icon-control__choices">
            <button type="button" className={ ! icon ? 'is-selected' : '' } onClick={ () => onChange( { icon: '' } ) }>{ __( 'None', 'cinderwell' ) }</button>
            { quickIcons.map( ( option ) => <button
                type="button"
                key={ option }
                className={ icon === option ? 'is-selected' : '' }
                onClick={ () => onChange( { icon: option } ) }
                aria-label={ iconOptions.find( ( item ) => item.value === option )?.label }
                title={ iconOptions.find( ( item ) => item.value === option )?.label }
            ><IconGlyph icon={ option } size={ 18 } /></button> ) }
        </div>
        <Button
            variant="secondary"
            className="cw-button-icon-control__browse"
            onClick={ ( event ) => setAnchor( anchor ? null : event.currentTarget ) }
            aria-expanded={ Boolean( anchor ) }
        >{ icon && ! quickIcons.includes( icon ) ? selectedLabel : __( 'Browse all icons', 'cinderwell' ) }</Button>
        { anchor && <Popover anchor={ anchor } onClose={ () => setAnchor( null ) } placement="left-start" className="cw-popover cw-button-icon-popover">
            <div className="cw-popover__inner">
                <div className="cw-popover__head">
                    <span className="cw-popover__title">{ __( 'Choose an icon', 'cinderwell' ) }</span>
                    <button type="button" className="cw-popover__close" onClick={ () => setAnchor( null ) } aria-label={ __( 'Close', 'cinderwell' ) }>&times;</button>
                </div>
                <IconPicker value={ icon || 'arrow-right' } onChange={ ( value ) => { onChange( { icon: value } ); setAnchor( null ); } } />
            </div>
        </Popover> }
        { icon && <PillControl
            label={ __( 'Placement', 'cinderwell' ) }
            value={ iconPosition }
            options={ [
                { value: 'before', label: __( 'Before', 'cinderwell' ) },
                { value: 'after', label: __( 'After', 'cinderwell' ) },
            ] }
            onChange={ ( value ) => onChange( { iconPosition: value } ) }
        /> }
    </div>;
};

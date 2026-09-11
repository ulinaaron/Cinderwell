import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const SubheadingEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="p"
            className="cinderwell-subheading"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Subheading text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const SubheadingSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="p" className="cinderwell-subheading" value={ value } />;
};

import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const LegalEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="p"
            className="cinderwell-legal"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Legal text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const LegalSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="p" className="cinderwell-legal" value={ value } />;
};

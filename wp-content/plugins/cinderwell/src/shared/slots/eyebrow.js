import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const EyebrowEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="span"
            className="cinderwell-eyebrow"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Eyebrow text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const EyebrowSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="span" className="cinderwell-eyebrow" value={ value } />;
};

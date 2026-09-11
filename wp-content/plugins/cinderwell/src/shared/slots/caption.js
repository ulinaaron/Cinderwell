import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const CaptionEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="figcaption"
            className="cinderwell-caption"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Caption text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const CaptionSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="figcaption" className="cinderwell-caption" value={ value } />;
};

import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const BylineEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="p"
            className="cinderwell-byline"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Author, Date…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const BylineSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="p" className="cinderwell-byline" value={ value } />;
};

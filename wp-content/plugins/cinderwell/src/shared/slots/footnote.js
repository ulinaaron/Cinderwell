import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const FootnoteEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="p"
            className="cinderwell-footnote"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Footnote text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const FootnoteSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="p" className="cinderwell-footnote" value={ value } />;
};

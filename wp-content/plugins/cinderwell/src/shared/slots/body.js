import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const BodyEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="div"
            className="cinderwell-body"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Body text…', 'cinderwell' ) }
        />
    );
};

export const BodySave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="div" className="cinderwell-body" value={ value } />;
};

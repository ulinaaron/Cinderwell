import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const PullquoteEdit = ( { value, onChange } ) => {
    return (
        <RichText
            tagName="blockquote"
            className="cinderwell-pullquote"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Pullquote text…', 'cinderwell' ) }
        />
    );
};

export const PullquoteSave = ( { value } ) => {
    if ( ! value ) return null;
    return <RichText.Content tagName="blockquote" className="cinderwell-pullquote" value={ value } />;
};

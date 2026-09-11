import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const HeadingEdit = ( { value, onChange, level = 2 } ) => {
    const tag = `h${ level }`;
    return (
        <RichText
            tagName={ tag }
            className="cinderwell-heading"
            value={ value }
            onChange={ onChange }
            placeholder={ __( 'Heading text…', 'cinderwell' ) }
            allowedFormats={ [] }
        />
    );
};

export const HeadingSave = ( { value, level = 2 } ) => {
    if ( ! value ) return null;
    const tag = `h${ level }`;
    return <RichText.Content tagName={ tag } className="cinderwell-heading" value={ value } />;
};

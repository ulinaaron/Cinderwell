import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockType('cinderwell-portal/register', {
    edit: () => {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <div className="cinderwell-portal-block-preview">
                    <span className="dashicons dashicons-groups"></span>
                    <p>{__('Registration Form', 'cinderwell-portal')}</p>
                    <small>{__('Renders the member registration form. Only visible when registration mode allows it.', 'cinderwell-portal')}</small>
                </div>
            </div>
        );
    },
    save: () => null,
});

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockType('cinderwell-portal/member-profile', {
    edit: () => {
        const blockProps = useBlockProps();
        return (
            <div {...blockProps}>
                <div className="cinderwell-portal-block-preview">
                    <span className="dashicons dashicons-admin-users"></span>
                    <p>{__('Member Profile', 'cinderwell-portal')}</p>
                    <small>{__('Shows login, pending notice, or profile form depending on state.', 'cinderwell-portal')}</small>
                </div>
            </div>
        );
    },
    save: () => null,
});

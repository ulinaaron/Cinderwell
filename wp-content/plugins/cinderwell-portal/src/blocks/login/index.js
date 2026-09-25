import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('cinderwell-portal/login', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Settings', 'cinderwell-portal')}>
                        <TextControl
                            label={__('Redirect after login', 'cinderwell-portal')}
                            value={attributes.redirectAfterLogin}
                            onChange={(v) => setAttributes({ redirectAfterLogin: v })}
                            help={__('Leave empty to use the global setting.', 'cinderwell-portal')}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <div className="cinderwell-portal-block-preview">
                        <span className="dashicons dashicons-lock"></span>
                        <p>{__('Login Form', 'cinderwell-portal')}</p>
                        <small>{__('Renders the member login form on the frontend.', 'cinderwell-portal')}</small>
                    </div>
                </div>
            </>
        );
    },
    save: () => null,
});

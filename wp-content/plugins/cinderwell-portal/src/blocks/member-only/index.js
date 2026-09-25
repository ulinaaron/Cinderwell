import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

registerBlockType('cinderwell-portal/member-only', {
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Fallback (Non-Members)', 'cinderwell-portal')}>
                        <TextControl
                            label={__('Message', 'cinderwell-portal')}
                            value={attributes.fallbackMessage}
                            onChange={(v) => setAttributes({ fallbackMessage: v })}
                        />
                        <ToggleControl
                            label={__('Show login link', 'cinderwell-portal')}
                            checked={attributes.fallbackShowLoginLink}
                            onChange={(v) => setAttributes({ fallbackShowLoginLink: v })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div {...blockProps}>
                    <div className="cinderwell-portal-block-preview cinderwell-portal-block-preview--member-only">
                        <span className="dashicons dashicons-visibility"></span>
                        <p>{__('Member Only Content', 'cinderwell-portal')}</p>
                        <small>{__('Wrap any content here. Only logged-in members will see it.', 'cinderwell-portal')}</small>
                    </div>
                    <div style={{ marginTop: '1rem' }}>
                        <InnerBlocks />
                    </div>
                </div>
            </>
        );
    },
    save: () => {
        return (
            <div className="wp-block-cinderwell-portal-member-only">
                <InnerBlocks.Content />
            </div>
        );
    },
});

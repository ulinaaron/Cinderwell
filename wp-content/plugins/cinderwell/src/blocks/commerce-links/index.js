import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

const AccountIcon = () => (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <circle cx="12" cy="8" r="3" />
        <path d="M5.5 20c.35-4 2.5-6 6.5-6s6.15 2 6.5 6" />
    </svg>
);

const CartIcon = () => (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6" />
        <circle cx="10" cy="20" r="1" />
        <circle cx="18" cy="20" r="1" />
    </svg>
);

registerBlockType( metadata.name, {
    edit: ( { attributes, setAttributes } ) => {
        const { showAccount, showCart, showCount } = attributes;
        const blockProps = useBlockProps( { className: 'cinderwell-commerce-links' } );

        return (
            <>
                <InspectorControls>
                    <PanelBody title={ __( 'Commerce links', 'cinderwell' ) }>
                        <ToggleControl
                            label={ __( 'Show account link', 'cinderwell' ) }
                            checked={ showAccount }
                            onChange={ ( value ) => setAttributes( { showAccount: value } ) }
                        />
                        <ToggleControl
                            label={ __( 'Show cart link', 'cinderwell' ) }
                            checked={ showCart }
                            onChange={ ( value ) => setAttributes( { showCart: value } ) }
                        />
                        { showCart && (
                            <ToggleControl
                                label={ __( 'Show cart count', 'cinderwell' ) }
                                checked={ showCount }
                                onChange={ ( value ) => setAttributes( { showCount: value } ) }
                            />
                        ) }
                    </PanelBody>
                </InspectorControls>
                <nav { ...blockProps } aria-label={ __( 'Commerce', 'cinderwell' ) }>
                    { showAccount && <span className="cinderwell-commerce-links__link"><AccountIcon /></span> }
                    { showCart && (
                        <span className="cinderwell-commerce-links__link cinderwell-commerce-links__cart">
                            <CartIcon />
                            { showCount && <span className="cinderwell-commerce-links__count">0</span> }
                        </span>
                    ) }
                </nav>
            </>
        );
    },
    save: () => null,
} );

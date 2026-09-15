import { InspectorControls } from "@wordpress/block-editor";
import { dispatch } from "@wordpress/data";
import { store as blocksStore } from "@wordpress/blocks";
import { createHigherOrderComponent } from "@wordpress/compose";
import { PanelBody, SelectControl, ToggleControl } from "@wordpress/components";
import { addFilter } from "@wordpress/hooks";
import { __ } from "@wordpress/i18n";
import { canEditControl } from "../../shared/editor-access";

const settings = window.cinderwellEditorSettings?.addons?.animations;

if ( settings?.enabled ) {
	const supported = new Set( settings.supportedBlocks || [] );
	const addAttributes = ( blockSettings, name ) => {
		if ( ! supported.has( name ) ) return blockSettings;
		return {
			...blockSettings,
			attributes: {
				...blockSettings.attributes,
				cwAnimation: { type: "string", default: "" },
				cwAnimationTarget: { type: "string", default: "block" },
				cwAnimationDuration: { type: "string", default: "" },
				cwAnimationDelay: { type: "string", default: "" },
			},
		};
	};

	addFilter(
		"blocks.registerBlockType",
		"cinderwell/animation-attributes",
		addAttributes,
	);
	dispatch( blocksStore ).reapplyBlockTypeFilters();

	const withAnimationControls = createHigherOrderComponent(
		( BlockEdit ) => ( props ) => {
			if ( ! supported.has( props.name ) || ! canEditControl( "appearance" ) ) {
				return <BlockEdit { ...props } />;
			}

			const { attributes, setAttributes } = props;
			const enabled = Boolean( attributes.cwAnimation );
			const defaultAnimation = settings.defaults?.animation || "fade-up";
			const withSiteDefault = ( options, label ) => [
				{ value: "", label },
				...( options || [] ),
			];

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody
							title={ __( "Animation", "cinderwell" ) }
							initialOpen={ false }
							className="cw-panel cw-access-appearance"
						>
							<ToggleControl
								label={ __( "Animate on entry", "cinderwell" ) }
								checked={ enabled }
								onChange={ ( value ) => setAttributes( {
									cwAnimation: value ? defaultAnimation : "",
								} ) }
							/>
							{ enabled && (
								<>
									<SelectControl
										label={ __( "Preset", "cinderwell" ) }
										value={ attributes.cwAnimation }
										options={ settings.presets || [] }
										onChange={ ( cwAnimation ) => setAttributes( { cwAnimation } ) }
									/>
									<SelectControl
										label={ __( "Animate", "cinderwell" ) }
										value={ attributes.cwAnimationTarget || "block" }
										options={ [
											{ value: "block", label: __( "Whole block", "cinderwell" ) },
											{ value: "sections", label: __( "Content sections", "cinderwell" ) },
										] }
										onChange={ ( cwAnimationTarget ) => setAttributes( { cwAnimationTarget } ) }
									/>
									<SelectControl
										label={ __( "Duration", "cinderwell" ) }
										value={ attributes.cwAnimationDuration || "" }
										options={ withSiteDefault( settings.durations, __( "Site default", "cinderwell" ) ) }
										onChange={ ( cwAnimationDuration ) => setAttributes( { cwAnimationDuration } ) }
									/>
									<SelectControl
										label={ __( "Delay", "cinderwell" ) }
										value={ attributes.cwAnimationDelay || "" }
										options={ withSiteDefault( settings.delays, __( "Site default", "cinderwell" ) ) }
										onChange={ ( cwAnimationDelay ) => setAttributes( { cwAnimationDelay } ) }
									/>
									<p className="components-base-control__help">
										{ __( "Motion uses Cinderwell timing, easing, and distance tokens. Reduced-motion preferences are always respected.", "cinderwell" ) }
									</p>
								</>
							) }
						</PanelBody>
					</InspectorControls>
				</>
			);
		},
		"withCinderwellAnimationControls",
	);

	addFilter(
		"editor.BlockEdit",
		"cinderwell/animation-controls",
		withAnimationControls,
	);
}

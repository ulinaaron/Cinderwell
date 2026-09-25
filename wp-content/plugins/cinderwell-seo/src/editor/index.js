import apiFetch from '@wordpress/api-fetch';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	Button,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/editor';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useMemo, useState } from '@wordpress/element';
import './style.css';

const prefix = window.cinderwellSeoEditor?.metaPrefix || '_cw_seo_';
const key = ( name ) => `${ prefix }${ name }`;
const searchPolicy = window.cinderwellSeoEditor?.searchPolicy || {};

const seoIcon = (
	<svg
		viewBox="0 0 24 24"
		width="24"
		height="24"
		aria-hidden="true"
		focusable="false"
	>
		<circle
			cx="10.5"
			cy="10.5"
			r="5.75"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.75"
		/>
		<path
			d="m15 15 4.25 4.25"
			fill="none"
			stroke="currentColor"
			strokeLinecap="round"
			strokeWidth="1.75"
		/>
	</svg>
);

const SeoPanel = () => {
	const indexingLocked = Boolean(
		searchPolicy.noindex && searchPolicy.locked
	);
	const editor = useSelect( ( select ) => {
		const store = select( 'core/editor' );
		return {
			postId: store.getCurrentPostId(),
			postType: store.getCurrentPostType(),
			title: store.getEditedPostAttribute( 'title' ) || '',
			excerpt: store.getEditedPostAttribute( 'excerpt' ) || '',
			content: store.getEditedPostContent() || '',
			permalink: store.getPermalink?.() || '',
		};
	}, [] );
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		editor.postType,
		'meta'
	);
	const socialImageId = Number( meta?.[ key( 'social_image_id' ) ] || 0 );
	const socialImage = useSelect(
		( select ) =>
			socialImageId ? select( 'core' ).getMedia( socialImageId ) : null,
		[ socialImageId ]
	);
	const [ analysis, setAnalysis ] = useState( null );
	const [ isAnalyzing, setIsAnalyzing ] = useState( false );
	const [ error, setError ] = useState( '' );

	const analysisMeta = useMemo(
		() => ( {
			title: meta?.[ key( 'title' ) ] || '',
			description: meta?.[ key( 'description' ) ] || '',
			focus_phrase: meta?.[ key( 'focus_phrase' ) ] || '',
			canonical: meta?.[ key( 'canonical' ) ] || '',
			robots_index: indexingLocked
				? 'noindex'
				: meta?.[ key( 'robots_index' ) ] || '',
			robots_follow: meta?.[ key( 'robots_follow' ) ] || '',
			social_title: meta?.[ key( 'social_title' ) ] || '',
			social_description: meta?.[ key( 'social_description' ) ] || '',
			social_image_id: socialImageId,
		} ),
		[ indexingLocked, meta, socialImageId ]
	);

	useEffect( () => {
		if ( ! editor.postId || ! editor.postType || indexingLocked ) {
			return undefined;
		}
		const controller = new AbortController();
		const timer = window.setTimeout( async () => {
			setIsAnalyzing( true );
			setError( '' );
			try {
				const response = await apiFetch( {
					path: '/cinderwell-seo/v1/analyze',
					method: 'POST',
					signal: controller.signal,
					data: {
						post_id: editor.postId,
						post_type: editor.postType,
						title: editor.title,
						excerpt: editor.excerpt,
						content: editor.content,
						url:
							editor.permalink ||
							window.cinderwellSeoEditor?.homeUrl ||
							'',
						meta: analysisMeta,
					},
				} );
				setAnalysis( response );
			} catch ( requestError ) {
				if ( requestError?.name !== 'AbortError' ) {
					setError(
						requestError?.message ||
							__(
								'SEO analysis could not be completed.',
								'cinderwell-seo'
							)
					);
				}
			} finally {
				if ( ! controller.signal.aborted ) {
					setIsAnalyzing( false );
				}
			}
		}, 800 );
		return () => {
			window.clearTimeout( timer );
			controller.abort();
		};
	}, [ editor, analysisMeta, indexingLocked ] );

	if ( ! meta ) {
		return null;
	}

	const update = ( name, value ) =>
		setMeta( { ...meta, [ key( name ) ]: value } );
	const searchTitle =
		analysisMeta.title ||
		editor.title ||
		__( 'Untitled', 'cinderwell-seo' );
	const searchDescription = searchPolicy.suppress_description
		? ''
		: analysisMeta.description ||
		  editor.excerpt ||
		  __(
				'Add a meta description to control this preview.',
				'cinderwell-seo'
		  );
	const displayUrl =
		editor.permalink || window.cinderwellSeoEditor?.homeUrl || '';

	return (
		<PluginSidebar
			name="cinderwell-seo"
			title={ __( 'Search appearance', 'cinderwell-seo' ) }
			icon={ seoIcon }
			className="cw-seo-sidebar"
		>
			<div className="cw-seo-editor">
				{ ! indexingLocked && (
					<section
						className="cw-seo-score"
						aria-labelledby="cw-seo-score-title"
					>
						<div>
							<strong id="cw-seo-score-title">
								{ __( 'SEO readiness', 'cinderwell-seo' ) }
							</strong>
							<span aria-live="polite">
								{ isAnalyzing && (
									<>
										<Spinner />{ ' ' }
										{ __( 'Analyzing…', 'cinderwell-seo' ) }
									</>
								) }
								{ ! isAnalyzing &&
									analysis &&
									sprintf(
										/* translators: 1: score, 2: readiness label. */
										__(
											'%1$d out of 100 — %2$s',
											'cinderwell-seo'
										),
										analysis.score,
										analysis.band_label
									) }
							</span>
						</div>
						{ analysis && (
							<output
								className={ `cw-seo-score__value is-${ analysis.band }` }
								aria-label={ sprintf(
									/* translators: %d: SEO readiness score. */
									__(
										'SEO readiness score: %d out of 100',
										'cinderwell-seo'
									),
									analysis.score
								) }
							>
								{ analysis.score }
							</output>
						) }
					</section>
				) }

				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				{ indexingLocked && (
					<Notice status="info" isDismissible={ false }>
						<strong>
							{ searchPolicy.label ||
								__( 'Members only', 'cinderwell-seo' ) }
						</strong>
						<br />
						{ searchPolicy.description ||
							__(
								'Members Portal enforces noindex for this page.',
								'cinderwell-seo'
							) }
					</Notice>
				) }

				<div
					className="cw-seo-preview"
					aria-label={ __(
						'Search result preview',
						'cinderwell-seo'
					) }
				>
					<span>{ displayUrl }</span>
					<strong>{ searchTitle }</strong>
					{ searchDescription && <p>{ searchDescription }</p> }
				</div>

				<TextControl
					label={ __( 'Search title', 'cinderwell-seo' ) }
					help={ sprintf(
						/* translators: %d: title character count. */
						__(
							'%d characters. Leave blank to use the WordPress title.',
							'cinderwell-seo'
						),
						searchTitle.length
					) }
					value={ analysisMeta.title }
					onChange={ ( value ) => update( 'title', value ) }
				/>
				<TextareaControl
					label={ __( 'Meta description', 'cinderwell-seo' ) }
					help={
						searchPolicy.suppress_description
							? __(
									'Members Portal prevents descriptions derived from protected content from being output.',
									'cinderwell-seo'
							  )
							: sprintf(
									/* translators: %d: description character count. */
									__(
										'%d characters. Leave blank to inherit an excerpt or content summary.',
										'cinderwell-seo'
									),
									searchDescription.length
							  )
					}
					value={ analysisMeta.description }
					disabled={ Boolean( searchPolicy.suppress_description ) }
					onChange={ ( value ) => update( 'description', value ) }
				/>
				<TextControl
					label={ __( 'Focus phrase', 'cinderwell-seo' ) }
					help={ __(
						'Used for analysis only. It is not output as a meta keyword.',
						'cinderwell-seo'
					) }
					value={ analysisMeta.focus_phrase }
					onChange={ ( value ) => update( 'focus_phrase', value ) }
				/>

				{ analysis && ! indexingLocked && (
					<details className="cw-seo-analysis">
						<summary>
							{ sprintf(
								/* translators: %d: number of analysis checks. */
								__( 'Review %d checks', 'cinderwell-seo' ),
								analysis.checks.length
							) }
						</summary>
						<ul>
							{ analysis.checks.map( ( check ) => (
								<li
									key={ check.id }
									className={ `is-${ check.status }` }
								>
									<strong>{ check.label }</strong>
									<span>{ check.message }</span>
								</li>
							) ) }
						</ul>
					</details>
				) }

				<details className="cw-seo-advanced">
					<summary>
						{ __( 'Indexing and canonical', 'cinderwell-seo' ) }
					</summary>
					<div className="cw-seo-advanced__content">
						<TextControl
							label={ __( 'Canonical URL', 'cinderwell-seo' ) }
							type="url"
							help={ __(
								'Leave blank to use the WordPress permalink.',
								'cinderwell-seo'
							) }
							value={ analysisMeta.canonical }
							onChange={ ( value ) =>
								update( 'canonical', value )
							}
						/>
						<SelectControl
							label={ __( 'Indexing', 'cinderwell-seo' ) }
							value={ analysisMeta.robots_index }
							disabled={ indexingLocked }
							help={
								indexingLocked
									? __(
											'Members Portal owns this setting while the page is protected.',
											'cinderwell-seo'
									  )
									: undefined
							}
							options={ [
								{
									label: __(
										'Inherit site default',
										'cinderwell-seo'
									),
									value: '',
								},
								{
									label: __( 'Index', 'cinderwell-seo' ),
									value: 'index',
								},
								{
									label: __( 'Noindex', 'cinderwell-seo' ),
									value: 'noindex',
								},
							] }
							onChange={ ( value ) =>
								update( 'robots_index', value )
							}
						/>
						<SelectControl
							label={ __( 'Link following', 'cinderwell-seo' ) }
							value={ analysisMeta.robots_follow }
							options={ [
								{
									label: __(
										'Inherit site default',
										'cinderwell-seo'
									),
									value: '',
								},
								{
									label: __( 'Follow', 'cinderwell-seo' ),
									value: 'follow',
								},
								{
									label: __( 'Nofollow', 'cinderwell-seo' ),
									value: 'nofollow',
								},
							] }
							onChange={ ( value ) =>
								update( 'robots_follow', value )
							}
						/>
					</div>
				</details>

				<details className="cw-seo-advanced">
					<summary>
						{ __( 'Social sharing', 'cinderwell-seo' ) }
					</summary>
					<div className="cw-seo-advanced__content">
						<TextControl
							label={ __( 'Social title', 'cinderwell-seo' ) }
							value={ analysisMeta.social_title }
							disabled={ Boolean( searchPolicy.suppress_social ) }
							help={
								searchPolicy.suppress_social
									? __(
											'Members Portal suppresses social metadata for this page.',
											'cinderwell-seo'
									  )
									: undefined
							}
							onChange={ ( value ) =>
								update( 'social_title', value )
							}
						/>
						<TextareaControl
							label={ __(
								'Social description',
								'cinderwell-seo'
							) }
							value={ analysisMeta.social_description }
							disabled={ Boolean( searchPolicy.suppress_social ) }
							onChange={ ( value ) =>
								update( 'social_description', value )
							}
						/>
						{ socialImage?.source_url && (
							<img
								className="cw-seo-social-image"
								src={ socialImage.source_url }
								alt=""
							/>
						) }
						<MediaUploadCheck>
							<MediaUpload
								allowedTypes={ [ 'image' ] }
								value={ socialImageId }
								onSelect={ ( media ) =>
									update( 'social_image_id', media.id )
								}
								render={ ( { open } ) => (
									<div className="cw-seo-media-actions">
										<Button
											variant="secondary"
											onClick={ open }
											disabled={ Boolean(
												searchPolicy.suppress_social
											) }
										>
											{ socialImageId
												? __(
														'Replace social image',
														'cinderwell-seo'
												  )
												: __(
														'Choose social image',
														'cinderwell-seo'
												  ) }
										</Button>
										{ Boolean( socialImageId ) && (
											<Button
												variant="tertiary"
												isDestructive
												disabled={ Boolean(
													searchPolicy.suppress_social
												) }
												onClick={ () =>
													update(
														'social_image_id',
														0
													)
												}
											>
												{ __(
													'Remove',
													'cinderwell-seo'
												) }
											</Button>
										) }
									</div>
								) }
							/>
						</MediaUploadCheck>
					</div>
				</details>
			</div>
		</PluginSidebar>
	);
};

registerPlugin( 'cinderwell-seo', { icon: seoIcon, render: SeoPanel } );

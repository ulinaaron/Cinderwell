import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Modal,
	Notice,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import './sessions-panel.css';

const emptyForm = {
	id: 0,
	allDay: false,
	startDate: '',
	startTime: '09:00',
	endDate: '',
	endTime: '10:00',
};

const fromSession = ( session ) => ( {
	id: session.id,
	allDay: session.all_day,
	startDate: session.start.slice( 0, 10 ),
	startTime: session.all_day ? '09:00' : session.start.slice( 11, 16 ),
	endDate: session.end.slice( 0, 10 ),
	endTime: session.all_day ? '10:00' : session.end.slice( 11, 16 ),
} );

const SessionsPanel = () => {
	const modeMeta =
		window.cinderwellEventsEditor?.modeMeta ||
		'_cinderwell_event_schedule_mode';
	const { postId, postType, postMeta } = useSelect(
		( select ) => ( {
			postId: select( 'core/editor' )?.getCurrentPostId(),
			postType: select( 'core/editor' )?.getCurrentPostType(),
			postMeta: select( 'core/editor' )?.getEditedPostAttribute( 'meta' ),
		} ),
		[]
	);
	const { editPost } = useDispatch( 'core/editor' );
	const [ sessions, setSessions ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ managerOpen, setManagerOpen ] = useState( false );
	const [ editing, setEditing ] = useState( false );
	const [ form, setForm ] = useState( emptyForm );
	const [ notice, setNotice ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ view, setView ] = useState( 'upcoming' );
	const [ managerKind, setManagerKind ] = useState( 'sessions' );

	const load = useCallback( async () => {
		if ( ! postId ) {
			return;
		}
		setLoading( true );
		try {
			const base = `/cinderwell-events/v1/events/${ postId }/sessions?context=edit&include_past=true&per_page=100&order=asc&_envelope=1`;
			const first = await apiFetch( { path: base } );
			const totalPages = Number(
				first.headers?.[ 'X-WP-TotalPages' ] ||
					first.headers?.[ 'x-wp-totalpages' ] ||
					1
			);
			const remaining =
				totalPages > 1
					? await Promise.all(
							Array.from(
								{ length: totalPages - 1 },
								( unused, index ) =>
									apiFetch( {
										path: `${ base }&page=${ index + 2 }`,
									} )
							)
					  )
					: [];
			setSessions( [
				...( first.body || [] ),
				...remaining.flatMap( ( response ) => response.body || [] ),
			] );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error.message ||
					__( 'Sessions could not be loaded.', 'cinderwell-events' ),
			} );
		} finally {
			setLoading( false );
		}
	}, [ postId ] );

	useEffect( () => {
		if ( postType === 'cw_event' ) {
			load();
		}
	}, [ load, postType ] );

	const upcoming = useMemo(
		() => sessions.filter( ( session ) => session.state !== 'past' ),
		[ sessions ]
	);
	const past = useMemo(
		() =>
			sessions
				.filter( ( session ) => session.state === 'past' )
				.reverse(),
		[ sessions ]
	);
	const next = upcoming[ 0 ];
	const singleDate = sessions[ 0 ];
	const storedMode =
		postMeta?.[ modeMeta ] === 'sessions' ? 'sessions' : 'single';
	const scheduleMode =
		storedMode === 'sessions' || sessions.length > 1
			? 'sessions'
			: 'single';

	if ( postType !== 'cw_event' ) {
		return null;
	}

	const openNew = () => {
		setForm( emptyForm );
		setManagerKind( 'sessions' );
		setEditing( true );
		setNotice( null );
	};

	const openOneTime = () => {
		setForm( sessions[ 0 ] ? fromSession( sessions[ 0 ] ) : emptyForm );
		setManagerKind( 'single' );
		setEditing( true );
		setManagerOpen( true );
		setNotice( null );
	};

	const changeMode = ( mode ) => {
		if ( mode === 'single' && sessions.length > 1 ) {
			return;
		}
		editPost( {
			meta: {
				...( postMeta || {} ),
				[ modeMeta ]: mode,
			},
		} );
		setNotice( null );
	};

	const save = async () => {
		if (
			! form.startDate ||
			! form.endDate ||
			( ! form.allDay && ( ! form.startTime || ! form.endTime ) )
		) {
			setNotice( {
				status: 'error',
				message: __( 'Choose a start and end.', 'cinderwell-events' ),
			} );
			return;
		}
		setSaving( true );
		setNotice( null );
		const data = {
			start: form.allDay
				? form.startDate
				: `${ form.startDate }T${ form.startTime }:00`,
			end: form.allDay
				? form.endDate
				: `${ form.endDate }T${ form.endTime }:00`,
			all_day: form.allDay,
		};
		try {
			await apiFetch( {
				path: form.id
					? `/cinderwell-events/v1/sessions/${ form.id }`
					: `/cinderwell-events/v1/events/${ postId }/sessions`,
				method: form.id ? 'PUT' : 'POST',
				data,
			} );
			await load();
			setEditing( false );
			setForm( emptyForm );
			let successMessage = __( 'Session added.', 'cinderwell-events' );
			if ( managerKind === 'single' ) {
				successMessage = __(
					'Event date and time saved.',
					'cinderwell-events'
				);
			} else if ( form.id ) {
				successMessage = __( 'Session updated.', 'cinderwell-events' );
			}
			setNotice( {
				status: 'success',
				message: successMessage,
			} );
			if ( managerKind === 'single' ) {
				setManagerOpen( false );
			}
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error.message ||
					__(
						'The Session could not be saved.',
						'cinderwell-events'
					),
			} );
		} finally {
			setSaving( false );
		}
	};

	const remove = async ( session ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				sprintf(
					// translators: %s is the formatted date and time of the Session.
					__( 'Delete the Session on %s?', 'cinderwell-events' ),
					session.display.date_time
				)
			)
		) {
			return;
		}
		setNotice( null );
		try {
			await apiFetch( {
				path: `/cinderwell-events/v1/sessions/${ session.id }`,
				method: 'DELETE',
			} );
			await load();
			setNotice( {
				status: 'success',
				message: __( 'Session deleted.', 'cinderwell-events' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error.message ||
					__(
						'The Session could not be deleted.',
						'cinderwell-events'
					),
			} );
		}
	};

	const renderList = ( items ) =>
		items.length ? (
			<div className="cw-events-session-list">
				{ items.map( ( session ) => (
					<article
						className="cw-events-session-row"
						key={ session.id }
					>
						<div>
							<strong>{ session.display.date }</strong>
							<span>{ session.display.time }</span>
							{ session.state === 'ongoing' && (
								<span className="cw-events-session-state">
									{ __( 'Ongoing', 'cinderwell-events' ) }
								</span>
							) }
						</div>
						<div className="cw-events-session-row__actions">
							<Button
								variant="secondary"
								size="compact"
								onClick={ () => {
									setForm( fromSession( session ) );
									setEditing( true );
									setNotice( null );
								} }
							>
								{ __( 'Edit', 'cinderwell-events' ) }
							</Button>
							<Button
								variant="tertiary"
								isDestructive
								size="compact"
								onClick={ () => remove( session ) }
							>
								{ __( 'Delete', 'cinderwell-events' ) }
							</Button>
						</div>
					</article>
				) ) }
			</div>
		) : (
			<p className="cw-events-session-empty">
				{ view === 'past'
					? __( 'No past Sessions.', 'cinderwell-events' )
					: __( 'No upcoming Sessions.', 'cinderwell-events' ) }
			</p>
		);
	let formTitle = __( 'Add Session', 'cinderwell-events' );
	if ( managerKind === 'single' ) {
		formTitle = __( 'When is this Event?', 'cinderwell-events' );
	} else if ( form.id ) {
		formTitle = __( 'Edit Session', 'cinderwell-events' );
	}
	let saveLabel = __( 'Save Session', 'cinderwell-events' );
	if ( saving ) {
		saveLabel = __( 'Saving…', 'cinderwell-events' );
	} else if ( managerKind === 'single' ) {
		saveLabel = __( 'Save date and time', 'cinderwell-events' );
	}

	return (
		<>
			<PluginDocumentSettingPanel
				name="cinderwell-event-sessions"
				title={ __( 'Event Schedule', 'cinderwell-events' ) }
				initialOpen
			>
				{ loading ? (
					<Spinner />
				) : (
					<>
						<div
							className="cw-segmented cw-events-mode"
							role="group"
							aria-label={ __(
								'Event schedule type',
								'cinderwell-events'
							) }
						>
							<button
								type="button"
								className={
									scheduleMode === 'single' ? 'is-active' : ''
								}
								aria-pressed={ scheduleMode === 'single' }
								disabled={ sessions.length > 1 }
								onClick={ () => changeMode( 'single' ) }
							>
								{ __( 'One-time', 'cinderwell-events' ) }
							</button>
							<button
								type="button"
								className={
									scheduleMode === 'sessions'
										? 'is-active'
										: ''
								}
								aria-pressed={ scheduleMode === 'sessions' }
								onClick={ () => changeMode( 'sessions' ) }
							>
								{ __(
									'Multiple sessions',
									'cinderwell-events'
								) }
							</button>
						</div>
						<p className="cw-events-mode__help">
							{ scheduleMode === 'single'
								? __(
										'A single Event with one date and time.',
										'cinderwell-events'
								  )
								: __(
										'The same Event happens on multiple independently editable dates.',
										'cinderwell-events'
								  ) }
						</p>

						{ scheduleMode === 'single' ? (
							<>
								{ singleDate ? (
									<div className="cw-events-next-session">
										<span>
											{ __(
												'Event date',
												'cinderwell-events'
											) }
										</span>
										<strong>
											{ singleDate.display.date }
										</strong>
										<span>{ singleDate.display.time }</span>
									</div>
								) : (
									<p className="cw-events-date-empty">
										{ __(
											'Date and time not set.',
											'cinderwell-events'
										) }
									</p>
								) }
								<Button
									variant="primary"
									className="cw-events-date-action"
									onClick={ openOneTime }
								>
									{ singleDate
										? __(
												'Edit date and time',
												'cinderwell-events'
										  )
										: __(
												'Set date and time',
												'cinderwell-events'
										  ) }
								</Button>
							</>
						) : (
							<>
								{ next ? (
									<div className="cw-events-next-session">
										<span>
											{ __(
												'Next Session',
												'cinderwell-events'
											) }
										</span>
										<strong>{ next.display.date }</strong>
										<span>{ next.display.time }</span>
									</div>
								) : (
									<p>
										{ __(
											'No upcoming Sessions.',
											'cinderwell-events'
										) }
									</p>
								) }
								<p className="cw-events-session-count">
									{ sprintf(
										// translators: 1: upcoming Session count, 2: past Session count.
										__(
											'%1$d upcoming · %2$d past',
											'cinderwell-events'
										),
										upcoming.length,
										past.length
									) }
								</p>
								<div className="cw-events-panel-actions">
									<Button
										variant="primary"
										onClick={ () => {
											setManagerOpen( true );
											openNew();
										} }
									>
										{ __(
											'Add Session',
											'cinderwell-events'
										) }
									</Button>
									<Button
										variant="secondary"
										onClick={ () => {
											setManagerKind( 'sessions' );
											setManagerOpen( true );
											setEditing( false );
											setNotice( null );
										} }
									>
										{ __( 'Manage', 'cinderwell-events' ) }
									</Button>
								</div>
								{ sessions.length > 1 && (
									<p className="components-base-control__help">
										{ __(
											'Remove extra Sessions before switching back to a one-time Event.',
											'cinderwell-events'
										) }
									</p>
								) }
							</>
						) }
						<p className="components-base-control__help">
							{ sprintf(
								// translators: %s is the WordPress site timezone.
								__(
									'Times use %s and save immediately.',
									'cinderwell-events'
								),
								window.cinderwellEventsEditor?.timezone ||
									__(
										'the site timezone',
										'cinderwell-events'
									)
							) }
						</p>
					</>
				) }
			</PluginDocumentSettingPanel>

			{ managerOpen && (
				<Modal
					title={
						managerKind === 'single'
							? __( 'Event date and time', 'cinderwell-events' )
							: __( 'Manage Sessions', 'cinderwell-events' )
					}
					onRequestClose={ () => setManagerOpen( false ) }
					className="cw-events-session-modal"
					size="large"
				>
					{ notice && (
						<Notice
							status={ notice.status }
							isDismissible
							onRemove={ () => setNotice( null ) }
						>
							{ notice.message }
						</Notice>
					) }
					{ managerKind === 'sessions' && (
						<div className="cw-events-session-modal__toolbar">
							<div
								className="cw-segmented"
								role="group"
								aria-label={ __(
									'Session view',
									'cinderwell-events'
								) }
							>
								<button
									type="button"
									className={
										view === 'upcoming' ? 'is-active' : ''
									}
									aria-pressed={ view === 'upcoming' }
									onClick={ () => {
										setView( 'upcoming' );
										setEditing( false );
									} }
								>
									{ __( 'Upcoming', 'cinderwell-events' ) }{ ' ' }
									<span>{ upcoming.length }</span>
								</button>
								<button
									type="button"
									className={
										view === 'past' ? 'is-active' : ''
									}
									aria-pressed={ view === 'past' }
									onClick={ () => {
										setView( 'past' );
										setEditing( false );
									} }
								>
									{ __( 'Past', 'cinderwell-events' ) }{ ' ' }
									<span>{ past.length }</span>
								</button>
							</div>
							<Button variant="primary" onClick={ openNew }>
								{ __( 'Add Session', 'cinderwell-events' ) }
							</Button>
						</div>
					) }

					{ editing && (
						<div className="cw-events-session-form">
							<div className="cw-events-session-form__heading">
								<div>
									<h2>{ formTitle }</h2>
									<p>
										{ sprintf(
											// translators: %s is the WordPress site timezone.
											__(
												'Times use %s.',
												'cinderwell-events'
											),
											window.cinderwellEventsEditor
												?.timezone ||
												__(
													'the site timezone',
													'cinderwell-events'
												)
										) }
									</p>
								</div>
								<ToggleControl
									label={ __(
										'All day',
										'cinderwell-events'
									) }
									checked={ form.allDay }
									onChange={ ( allDay ) =>
										setForm( { ...form, allDay } )
									}
								/>
							</div>
							<div className="cw-events-session-form__fields">
								<label htmlFor="cw-events-start-date">
									<span>
										{ __(
											'Start date',
											'cinderwell-events'
										) }
									</span>
									<input
										id="cw-events-start-date"
										type="date"
										value={ form.startDate }
										onChange={ ( event ) =>
											setForm( {
												...form,
												startDate: event.target.value,
												endDate:
													form.endDate ||
													event.target.value,
											} )
										}
									/>
								</label>
								{ ! form.allDay && (
									<label htmlFor="cw-events-start-time">
										<span>
											{ __(
												'Start time',
												'cinderwell-events'
											) }
										</span>
										<input
											id="cw-events-start-time"
											type="time"
											value={ form.startTime }
											onChange={ ( event ) =>
												setForm( {
													...form,
													startTime:
														event.target.value,
												} )
											}
										/>
									</label>
								) }
								<label htmlFor="cw-events-end-date">
									<span>
										{ __(
											'End date',
											'cinderwell-events'
										) }
									</span>
									<input
										id="cw-events-end-date"
										type="date"
										value={ form.endDate }
										min={ form.startDate }
										onChange={ ( event ) =>
											setForm( {
												...form,
												endDate: event.target.value,
											} )
										}
									/>
								</label>
								{ ! form.allDay && (
									<label htmlFor="cw-events-end-time">
										<span>
											{ __(
												'End time',
												'cinderwell-events'
											) }
										</span>
										<input
											id="cw-events-end-time"
											type="time"
											value={ form.endTime }
											onChange={ ( event ) =>
												setForm( {
													...form,
													endTime: event.target.value,
												} )
											}
										/>
									</label>
								) }
							</div>
							<div className="cw-events-session-form__actions">
								<Button
									variant="primary"
									isBusy={ saving }
									disabled={ saving }
									onClick={ save }
								>
									{ saveLabel }
								</Button>
								<Button
									variant="tertiary"
									disabled={ saving }
									onClick={ () => setEditing( false ) }
								>
									{ __( 'Cancel', 'cinderwell-events' ) }
								</Button>
							</div>
						</div>
					) }
					{ managerKind === 'sessions' && ! editing && loading && (
						<Spinner />
					) }
					{ managerKind === 'sessions' &&
						! editing &&
						! loading &&
						renderList( view === 'past' ? past : upcoming ) }
				</Modal>
			) }
		</>
	);
};

registerPlugin( 'cinderwell-event-sessions', {
	render: SessionsPanel,
	icon: 'calendar-alt',
} );

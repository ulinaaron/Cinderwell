( () => {
	'use strict';
	const root = document.querySelector( '[data-cw-form-builder]' );
	const esc = ( value ) => String( value ?? '' ).replace( /[&<>"]/g, ( char ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' } )[ char ] );
	const uuid = () => window.crypto && window.crypto.randomUUID ? window.crypto.randomUUID() : `cw-${ Date.now() }-${ Math.random().toString( 16 ).slice( 2 ) }`;
	const clone = ( value ) => JSON.parse( JSON.stringify( value ) );

	if ( root ) {
		const input = root.querySelector( '[data-cw-definition]' );
		let definition;
		try { definition = JSON.parse( input.value ); } catch ( error ) { definition = {}; }
		definition.fields = Array.isArray( definition.fields ) ? definition.fields : [];
		definition.notifications = Array.isArray( definition.notifications ) ? definition.notifications : [];
		const fieldsRoot = root.querySelector( '[data-cw-fields]' );
		const canvas = root.querySelector( '.cw-form-canvas' );
		const fieldInspector = root.querySelector( '[data-cw-field-inspector]' );
		const notificationsRoot = root.querySelector( '[data-cw-notifications]' );
		const builderStatus = root.querySelector( '[data-cw-builder-status]' );
		const sidebarTabs = [ ...root.querySelectorAll( '[data-cw-sidebar-tab]' ) ];
		const sidebarPanels = [ ...root.querySelectorAll( '[data-cw-sidebar-panel]' ) ];
		let selectedFieldIndex = definition.fields.length ? 0 : -1;
		const fieldTypes = window.cinderwellFormsAdmin.fieldTypes;
		const choicePresets = window.cinderwellFormsAdmin.choicePresets || {};
		const choiceTypes = [ 'select', 'radio', 'checkboxes' ];
		const uniqueFieldKey = ( base = 'field' ) => {
			const keys = new Set( definition.fields.map( ( field ) => field.key ) );
			let key = base; let suffix = 2;
			while ( keys.has( key ) ) { key = `${ base }_${ suffix }`; suffix += 1; }
			return key;
		};
		const nextFieldKey = () => {
			const keys = new Set( definition.fields.map( ( field ) => field.key ) ); let suffix = 1;
			while ( keys.has( `field_${ suffix }` ) ) suffix += 1;
			return `field_${ suffix }`;
		};
		const emailFields = () => definition.fields.filter( ( field ) => field.type === 'email' && ! field.query_param );
		const conditionFields = ( currentIndex = definition.fields.length ) => definition.fields.slice( 0, currentIndex ).filter( ( field ) => ! [ 'content', 'divider' ].includes( field.type ) );
		const options = ( list, selected, empty = '' ) => `${ empty ? `<option value="">${ esc( empty ) }</option>` : '' }${ list.map( ( item ) => `<option value="${ esc( item.value ) }"${ String( item.value ) === String( selected ) ? ' selected' : '' }>${ esc( item.label ) }</option>` ).join( '' ) }`;
		const fieldOptions = ( list, selected, empty ) => options( list.map( ( field ) => ( { value: field.key, label: field.label || field.key } ) ), selected, empty );
		const save = () => { input.value = JSON.stringify( definition ); };
		const showSidebarPanel = ( name ) => {
			sidebarTabs.forEach( ( tab ) => { const selected = tab.dataset.cwSidebarTab === name; tab.setAttribute( 'aria-selected', String( selected ) ); tab.tabIndex = selected ? 0 : -1; } );
			sidebarPanels.forEach( ( panel ) => { panel.hidden = panel.dataset.cwSidebarPanel !== name; } );
		};
		const positionOptions = ( selected ) => definition.fields.map( ( field, index ) => `<option value="${ index }"${ index === selected ? ' selected' : '' }>${ index + 1 }. ${ esc( field.label || fieldTypes[ field.type ] ) }</option>` ).join( '' );

		const renderConditions = ( conditions, available, prefix ) => {
			const fieldContext = 'field' === prefix;
			if ( ! available.length ) return `<p class="cw-condition-empty">${ fieldContext ? 'Add or move an input field above this field to use conditional visibility.' : 'Add an input field before creating notification conditions.' }</p>`;
			const ruleList = conditions.length ? `<div class="cw-rule-list">${ conditions.map( ( rule, index ) => {
				const valueDisabled = [ 'empty', 'not_empty' ].includes( rule.operator );
				return `<div class="cw-rule" data-rule-index="${ index }"><div class="cw-rule__header"><strong>Condition ${ index + 1 }</strong><button type="button" class="cw-rule__remove" data-remove-rule aria-label="Remove condition ${ index + 1 }"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span>${ esc( window.cinderwellFormsAdmin.strings.remove ) }</button></div><div class="cw-rule__controls"><label class="cw-rule__field"><span>Field</span><select data-rule-field>${ fieldOptions( available, rule.field, 'Choose field' ) }</select></label><label><span>Comparison</span><select data-rule-operator>${ options( [ { value: 'is', label: 'Is' }, { value: 'is_not', label: 'Is not' }, { value: 'contains', label: 'Contains' }, { value: 'empty', label: 'Is empty' }, { value: 'not_empty', label: 'Is not empty' } ], rule.operator ) }</select></label><label class="cw-rule__value${ valueDisabled ? ' is-disabled' : '' }"><span>Value</span><input type="text" data-rule-value value="${ esc( rule.value ) }"${ valueDisabled ? ' disabled placeholder="Not needed"' : '' }></label></div></div>`;
			} ).join( '' ) }</div>` : '';
			return `<p class="cw-condition-help">${ fieldContext ? 'Conditions can use input fields placed above this field.' : 'Conditions can use submitted form fields.' }</p>${ ruleList }<button type="button" class="button cw-add-condition" data-add-rule="${ prefix }">Add condition</button>`;
		};
		const choicePresetControl = ( field ) => {
			if ( 'select' !== field.type || ! Object.keys( choicePresets ).length ) return '';
			const presetOptions = Object.entries( choicePresets ).map( ( [ key, preset ] ) => `<option value="${ esc( key ) }">${ esc( preset.label ) }</option>` ).join( '' );
			return `<fieldset class="cw-choice-preset"><legend>Common choices</legend><label><span>Preset</span><select data-choice-preset><option value="">${ esc( window.cinderwellFormsAdmin.strings.choosePreset ) }</option>${ presetOptions }</select></label><button type="button" class="button" data-apply-choice-preset disabled>${ esc( window.cinderwellFormsAdmin.strings.applyPreset ) }</button><small data-choice-preset-help>Choose a preset to replace the current choices. Display labels remain readable while submitted values use standard two-letter codes.</small></fieldset>`;
		};

		const fieldPreview = ( field ) => {
			const instructions = field.instructions && ! [ 'content', 'divider', 'hidden' ].includes( field.type )
				? `<small class="cw-preview-instructions">${ esc( field.instructions ) }</small>`
				: '';
			if ( field.type === 'divider' ) return '<hr>';
			if ( field.type === 'content' ) return `<p>${ esc( field.instructions || 'Informational content' ) }</p>`;
			if ( [ 'radio', 'checkboxes' ].includes( field.type ) ) return `<div class="cw-preview-choices">${ ( field.choices || [] ).slice( 0, 3 ).map( ( choice ) => `<span><i class="${ field.type === 'radio' ? 'is-radio' : '' }"></i>${ esc( choice.label ) }</span>` ).join( '' ) }</div>${ instructions }`;
			if ( field.type === 'consent' ) return `<div class="cw-preview-choices"><span><i></i>Agreement</span></div>${ instructions }`;
			if ( field.type === 'textarea' ) return `<span class="cw-preview-control is-textarea"></span>${ instructions }`;
			if ( field.type === 'select' ) return `<span class="cw-preview-control">Select an option⌄</span>${ instructions }`;
			if ( field.type === 'hidden' ) return '<span class="cw-preview-hidden">Hidden value</span>';
			return `<span class="cw-preview-control">${ esc( field.placeholder || '' ) }</span>${ instructions }`;
		};

		const renderFieldInspector = () => {
			if ( selectedFieldIndex < 0 || ! definition.fields[ selectedFieldIndex ] ) {
				fieldInspector.innerHTML = '<div class="cw-inspector-empty"><strong>No field selected</strong><p>Add a field or select one in the preview to edit its settings.</p></div>'; return;
			}
			const field = definition.fields[ selectedFieldIndex ];
			const choices = ( field.choices || [] ).map( ( choice ) => choice.label === choice.value ? choice.label : `${ choice.label }|${ choice.value }` ).join( '\n' );
			const nonInput = [ 'content', 'divider' ].includes( field.type );
			fieldInspector.innerHTML = `<div class="cw-inspector-heading" data-field-index="${ selectedFieldIndex }"><div><span>${ esc( fieldTypes[ field.type ] || field.type ) }</span><h2 tabindex="-1">${ esc( field.label || fieldTypes[ field.type ] ) }</h2></div><div class="cw-card-actions"><button type="button" class="cw-field-action" data-duplicate><span class="dashicons dashicons-admin-page" aria-hidden="true"></span>${ esc( window.cinderwellFormsAdmin.strings.duplicate ) }</button><button type="button" class="cw-field-action cw-field-action--danger" data-remove aria-label="Hold for 3 seconds to remove ${ esc( field.label || fieldTypes[ field.type ] ) }" title="Hold for 3 seconds to remove"><span class="dashicons dashicons-trash" aria-hidden="true"></span>${ esc( window.cinderwellFormsAdmin.strings.remove ) }</button></div></div><div class="cw-field-settings" data-field-index="${ selectedFieldIndex }"><details open><summary>Content</summary><div class="cw-inspector-panel"><label><span>Label</span><input type="text" data-field-prop="label" value="${ esc( field.label ) }"></label><label><span>Field key</span><input type="text" data-field-prop="key" value="${ esc( field.key ) }" pattern="[a-z0-9_]+"></label>${ field.type === 'content' ? '' : `<label><span>Instructions</span><input type="text" data-field-prop="instructions" value="${ esc( field.instructions ) }"></label>` }${ ! nonInput && field.type !== 'consent' ? `<label><span>Placeholder</span><input type="text" data-field-prop="placeholder" value="${ esc( field.placeholder ) }"></label>` : '' }${ choicePresetControl( field ) }${ choiceTypes.includes( field.type ) ? `<label><span>Choices</span><textarea rows="5" data-field-choices>${ esc( choices ) }</textarea><small>One per line. Use Label|value when they differ.</small></label>` : '' }</div></details><details open><summary>Layout and behavior</summary><div class="cw-inspector-panel">${ nonInput ? '' : `<label class="cw-toggle-row"><span class="cw-toggle-copy"><strong>Required</strong><small>Visitors must complete this field before submitting.</small></span><span class="cw-toggle"><input type="checkbox" role="switch" data-field-prop="required"${ field.required ? ' checked' : '' }><span aria-hidden="true"></span></span></label>` }<label><span>Position</span><select data-field-position>${ positionOptions( selectedFieldIndex ) }</select><small>Provides a keyboard-accessible alternative to dragging.</small></label>${ nonInput ? '' : `<label><span>Width</span><select data-field-prop="width"><option value="full"${ field.width !== 'half' ? ' selected' : '' }>Full</option><option value="half"${ field.width === 'half' ? ' selected' : '' }>Half</option></select></label><label><span>URL parameter</span><input type="text" data-field-prop="query_param" value="${ esc( field.query_param ) }" pattern="[a-z0-9_-]+"><small>Only this named parameter may prefill the field.</small></label>` }</div></details>${ nonInput ? '' : `<details><summary>Conditional visibility</summary><div class="cw-inspector-panel cw-conditions-panel"><label><span>Match</span><select data-field-prop="condition_relation"><option value="all"${ field.condition_relation !== 'any' ? ' selected' : '' }>All conditions</option><option value="any"${ field.condition_relation === 'any' ? ' selected' : '' }>Any condition</option></select></label>${ renderConditions( field.conditions || [], conditionFields( selectedFieldIndex ), 'field' ) }</div></details>` }`;
			const conditionPanel = fieldInspector.querySelector( 'details:last-of-type' );
			if ( conditionPanel && ! nonInput ) { conditionPanel.dataset.fieldConditions = ''; conditionPanel.open = Boolean( field.conditions.length ); }
		};

		const updateFieldPreview = ( index ) => {
			const field = definition.fields[ index ];
			const card = fieldsRoot.querySelector( `[data-field-index="${ index }"]` );
			if ( ! field || ! card ) return;
			card.classList.toggle( 'cw-field-preview--half', field.width === 'half' );
			card.classList.toggle( 'cw-field-preview--full', field.width !== 'half' );
			const button = card.querySelector( '[data-select-field]' );
			button.innerHTML = `<span class="cw-preview-label">${ esc( field.label || fieldTypes[ field.type ] ) }${ field.required ? ' <em>(required)</em>' : '' }</span>${ fieldPreview( field ) }`;
			button.setAttribute( 'aria-label', `Edit ${ field.label || fieldTypes[ field.type ] }` );
			card.querySelector( '[data-duplicate]' )?.setAttribute( 'aria-label', `Duplicate ${ field.label || fieldTypes[ field.type ] }` );
			card.querySelector( '[data-remove]' )?.setAttribute( 'aria-label', `Hold for 3 seconds to remove ${ field.label || fieldTypes[ field.type ] }` );
			fieldInspector.querySelector( '[data-remove]' )?.setAttribute( 'aria-label', `Hold for 3 seconds to remove ${ field.label || fieldTypes[ field.type ] }` );
			const heading = fieldInspector.querySelector( '.cw-inspector-heading h2' );
			if ( index === selectedFieldIndex && heading ) heading.textContent = field.label || fieldTypes[ field.type ];
		};

		const renderFields = () => {
			if ( selectedFieldIndex >= definition.fields.length ) selectedFieldIndex = definition.fields.length - 1;
			fieldsRoot.innerHTML = definition.fields.length ? definition.fields.map( ( field, index ) => `<article class="cw-field-preview${ index === selectedFieldIndex ? ' is-selected' : '' } cw-field-preview--${ esc( field.width ) }" data-field-index="${ index }"><div class="cw-field-preview__controls"><span class="cw-drag" draggable="true" data-drag-handle title="Drag to reorder" aria-hidden="true"><span class="dashicons dashicons-move"></span></span><button type="button" class="cw-field-preview__duplicate" data-duplicate aria-label="Duplicate ${ esc( field.label || fieldTypes[ field.type ] ) }" title="Duplicate"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button><button type="button" class="cw-field-preview__remove" data-remove aria-label="Hold for 3 seconds to remove ${ esc( field.label || fieldTypes[ field.type ] ) }" title="Hold for 3 seconds to remove"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button></div><button type="button" class="cw-field-select" data-select-field aria-label="Edit ${ esc( field.label || fieldTypes[ field.type ] ) }" aria-pressed="${ index === selectedFieldIndex ? 'true' : 'false' }"><span class="cw-preview-label">${ esc( field.label || fieldTypes[ field.type ] ) }${ field.required ? ' <em>(required)</em>' : '' }</span>${ fieldPreview( field ) }</button><span class="cw-field-preview__remove-progress" aria-hidden="true"></span></article>` ).join( '' ) : '<div class="cw-canvas-empty"><strong>Start with a field</strong><p>Click a field or drag one here to begin building your form.</p></div>';
			renderFieldInspector(); save();
		};

		const renderNotifications = () => {
			const emails = emailFields();
			const firstRender = ! notificationsRoot.children.length;
			const openIds = new Set( [ ...notificationsRoot.querySelectorAll( '.cw-notification-card[open]' ) ].map( ( card ) => definition.notifications[ Number( card.dataset.notificationIndex ) ]?.id ).filter( Boolean ) );
			notificationsRoot.innerHTML = definition.notifications.map( ( rule, index ) => {
				const customRecipient = rule.recipient_mode !== 'field';
				const conditionCount = ( rule.conditions || [] ).length;
				const isOpen = openIds.has( rule.id ) || ( firstRender && 0 === index );
				return `<details class="cw-field-card cw-notification-card" data-notification-index="${ index }"${ isOpen ? ' open' : '' }><summary class="cw-notification-card__summary"><span><strong data-notification-title>${ esc( rule.name || 'Notification' ) }</strong><span class="cw-notification-status${ rule.enabled ? ' is-enabled' : '' }" data-notification-status>${ rule.enabled ? 'Enabled' : 'Disabled' }</span></span><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></summary><div class="cw-notification-card__body"><div class="cw-notification-toolbar"><label class="cw-notification-enabled"><span class="cw-toggle-copy"><strong>Enabled</strong><small>Send after matching submissions.</small></span><span class="cw-toggle"><input type="checkbox" role="switch" data-notification-prop="enabled"${ rule.enabled ? ' checked' : '' }><span aria-hidden="true"></span></span></label><div class="cw-card-actions"><button type="button" class="cw-field-action" data-duplicate-notification><span class="dashicons dashicons-admin-page" aria-hidden="true"></span>${ esc( window.cinderwellFormsAdmin.strings.duplicate ) }</button><button type="button" class="cw-field-action cw-field-action--danger" data-remove-notification><span class="dashicons dashicons-trash" aria-hidden="true"></span>${ esc( window.cinderwellFormsAdmin.strings.remove ) }</button></div></div><div class="cw-notification-meta"><label><span>Notification name</span><input type="text" data-notification-prop="name" value="${ esc( rule.name ) }"></label></div><section class="cw-notification-section"><h3>Delivery</h3><div class="cw-notification-grid"><label><span>Recipient type</span><select data-notification-prop="recipient_mode"><option value="custom"${ customRecipient ? ' selected' : '' }>Custom email addresses</option><option value="field"${ customRecipient ? '' : ' selected' }>Submitted email field</option></select></label><label data-recipient-setting="custom"${ customRecipient ? '' : ' hidden' }><span>Custom recipients</span><input type="text" data-notification-prop="recipients" value="${ esc( rule.recipients ) }" placeholder="${ esc( window.cinderwellFormsAdmin.adminEmail ) }"></label><label data-recipient-setting="field"${ customRecipient ? ' hidden' : '' }><span>Recipient email field</span><select data-notification-prop="recipient_field">${ fieldOptions( emails, rule.recipient_field, 'Choose email field' ) }</select></label><label><span>Reply-to email field</span><select data-notification-prop="reply_to_field">${ fieldOptions( emails, rule.reply_to_field, 'No reply-to field' ) }</select></label></div></section><section class="cw-notification-section"><h3>Message</h3><div class="cw-notification-message"><label><span>Subject</span><input type="text" data-notification-prop="subject" value="${ esc( rule.subject ) }"></label><label><span>Message</span><textarea rows="7" data-notification-prop="body">${ esc( rule.body ) }</textarea><small>Merge tags: {form_title}, {submission_id}, {submission_date}, {source_url}, {admin_entry_url}, {all_fields}, or {field:field_key}.</small></label></div></section><details class="cw-notification-conditions"><summary><span>Send conditions</span><span>${ conditionCount ? `${ conditionCount } condition${ 1 === conditionCount ? '' : 's' }` : 'No conditions' }</span></summary><div class="cw-notification-conditions__body"><label><span>Match</span><select data-notification-prop="condition_relation"><option value="all"${ rule.condition_relation !== 'any' ? ' selected' : '' }>All conditions</option><option value="any"${ rule.condition_relation === 'any' ? ' selected' : '' }>Any condition</option></select></label>${ renderConditions( rule.conditions || [], definition.fields.filter( ( field ) => ! [ 'content', 'divider' ].includes( field.type ) ), 'notification' ) }</div></details></div></details>`;
			} ).join( '' );
			save();
		};

		const newField = ( type ) => ( { id: uuid(), key: nextFieldKey(), type, label: fieldTypes[ type ], instructions: '', placeholder: '', default: '', required: false, width: 'full', choices: choiceTypes.includes( type ) ? [ { label: 'Option 1', value: 'option-1' } ] : [], query_param: '', condition_relation: 'all', conditions: [] } );
		const newNotification = () => ( { id: uuid(), name: 'Administrator notification', enabled: true, recipient_mode: 'custom', recipients: window.cinderwellFormsAdmin.adminEmail, recipient_field: '', reply_to_field: '', subject: '{form_title}: new submission', body: '{all_fields}', condition_relation: 'all', conditions: [] } );
		const removeHoldDuration = 3000;
		let pendingRemoval = null;

		const cancelFieldRemoval = ( announce = false ) => {
			if ( ! pendingRemoval ) return;
			window.cancelAnimationFrame( pendingRemoval.frame );
			pendingRemoval.card.classList.remove( 'is-remove-armed' );
			pendingRemoval.card.style.removeProperty( '--cw-remove-progress' );
			pendingRemoval.button.classList.remove( 'is-holding' );
			if ( announce ) builderStatus.textContent = `${ pendingRemoval.label } removal canceled.`;
			pendingRemoval = null;
		};

		const completeFieldRemoval = () => {
			if ( ! pendingRemoval ) return;
			const removal = pendingRemoval;
			pendingRemoval = null;
			removal.card.classList.remove( 'is-remove-armed' );
			removal.card.classList.add( 'is-remove-complete' );
			removal.card.style.setProperty( '--cw-remove-progress', '100%' );
			window.setTimeout( () => {
				const index = definition.fields.findIndex( ( field ) => field.id === removal.id );
				if ( index < 0 ) return;
				definition.fields.splice( index, 1 );
				selectedFieldIndex = Math.min( index, definition.fields.length - 1 );
				renderFields(); renderNotifications();
				if ( selectedFieldIndex < 0 ) {
					showSidebarPanel( 'add' );
					root.querySelector( '[data-cw-sidebar-tab="add"]' )?.focus();
				} else {
					fieldsRoot.querySelector( `[data-field-index="${ selectedFieldIndex }"] [data-select-field]` )?.focus();
				}
				builderStatus.textContent = `${ removal.label } removed.`;
			}, 160 );
		};

		const startFieldRemoval = ( button ) => {
			const owner = button.closest( '[data-field-index]' );
			const index = owner ? Number( owner.dataset.fieldIndex ) : -1;
			const card = index >= 0 ? fieldsRoot.querySelector( `[data-field-index="${ index }"]` ) : null;
			const field = index >= 0 ? definition.fields[ index ] : null;
			if ( ! card || ! field || pendingRemoval ) return;
			const removal = { button, card, id: field.id, label: field.label || fieldTypes[ field.type ], started: window.performance.now(), frame: 0 };
			pendingRemoval = removal;
			card.classList.add( 'is-remove-armed' );
			button.classList.add( 'is-holding' );
			builderStatus.textContent = `Keep holding to remove ${ removal.label }.`;
			const updateProgress = ( now ) => {
				if ( pendingRemoval !== removal ) return;
				const progress = Math.min( 1, ( now - removal.started ) / removeHoldDuration );
				card.style.setProperty( '--cw-remove-progress', `${ progress * 100 }%` );
				if ( progress >= 1 ) completeFieldRemoval();
				else removal.frame = window.requestAnimationFrame( updateProgress );
			};
			removal.frame = window.requestAnimationFrame( updateProgress );
		};

		root.addEventListener( 'pointerdown', ( event ) => {
			const button = event.target.closest( '[data-remove]' );
			if ( ! button || event.button !== 0 ) return;
			event.preventDefault();
			button.setPointerCapture?.( event.pointerId );
			startFieldRemoval( button );
		} );
		root.addEventListener( 'pointerup', () => cancelFieldRemoval( true ) );
		root.addEventListener( 'pointercancel', () => cancelFieldRemoval( true ) );
		root.addEventListener( 'keydown', ( event ) => {
			const button = event.target.closest( '[data-remove]' );
			if ( ! button || ! [ ' ', 'Enter' ].includes( event.key ) ) return;
			event.preventDefault();
			if ( ! event.repeat ) startFieldRemoval( button );
		} );
		root.addEventListener( 'keyup', ( event ) => {
			if ( event.target.closest( '[data-remove]' ) && [ ' ', 'Enter' ].includes( event.key ) ) {
				event.preventDefault(); cancelFieldRemoval( true );
			}
		} );
		root.addEventListener( 'focusout', ( event ) => {
			if ( event.target.closest( '[data-remove]' ) ) cancelFieldRemoval( true );
		} );

		root.addEventListener( 'click', ( event ) => {
			const sidebarTab = event.target.closest( '[data-cw-sidebar-tab]' );
			if ( sidebarTab ) { showSidebarPanel( sidebarTab.dataset.cwSidebarTab ); return; }
			const tab = event.target.closest( '[data-tab]' );
			if ( tab ) { root.querySelectorAll( '[data-tab]' ).forEach( ( item ) => { item.setAttribute( 'aria-selected', String( item === tab ) ); item.tabIndex = item === tab ? 0 : -1; } ); root.querySelectorAll( '[data-panel]' ).forEach( ( panel ) => { panel.hidden = panel.dataset.panel !== tab.dataset.tab; } ); return; }
			const addField = event.target.closest( '[data-cw-add-field]' );
			if ( addField ) { const insertAt = selectedFieldIndex < 0 ? definition.fields.length : selectedFieldIndex + 1; definition.fields.splice( insertAt, 0, newField( addField.dataset.cwAddField ) ); selectedFieldIndex = insertAt; renderFields(); renderNotifications(); showSidebarPanel( 'settings' ); fieldInspector.querySelector( 'h2' )?.focus(); builderStatus.textContent = `${ definition.fields[ insertAt ].label } added.`; return; }
			if ( event.target.closest( '[data-cw-add-notification]' ) ) { definition.notifications.push( newNotification() ); renderNotifications(); notificationsRoot.lastElementChild.open = true; notificationsRoot.lastElementChild?.querySelector( 'input' )?.focus(); return; }
			const card = event.target.closest( '[data-field-index]' );
			if ( card ) {
				const index = Number( card.dataset.fieldIndex ); let restoreCanvas = false; let message = '';
				if ( event.target.closest( '[data-select-field]' ) ) { selectedFieldIndex = index; renderFields(); showSidebarPanel( 'settings' ); fieldInspector.querySelector( 'h2' )?.focus(); return; }
				const applyPreset = event.target.closest( '[data-apply-choice-preset]' );
				if ( applyPreset ) {
					const presetKey = card.querySelector( '[data-choice-preset]' )?.value;
					const preset = presetKey ? choicePresets[ presetKey ] : null;
					if ( ! preset || ! Array.isArray( preset.choices ) ) return;
					const warning = window.cinderwellFormsAdmin.strings.replaceChoices.replace( '%s', preset.label );
					if ( definition.fields[ index ].choices.length && ! window.confirm( warning ) ) return;
					definition.fields[ index ].choices = clone( preset.choices );
					renderFieldInspector(); updateFieldPreview( index ); save();
					fieldInspector.querySelector( '[data-field-choices]' )?.focus();
					builderStatus.textContent = window.cinderwellFormsAdmin.strings.presetApplied.replace( '%s', preset.label );
					return;
				}
				if ( event.target.closest( '[data-remove]' ) ) { event.preventDefault(); return; }
				if ( event.target.closest( '[data-duplicate]' ) ) { const copy = clone( definition.fields[ index ] ); copy.id = uuid(); copy.key = uniqueFieldKey( `${ copy.key }_copy` ); definition.fields.splice( index + 1, 0, copy ); selectedFieldIndex = index + 1; message = `${ copy.label } duplicated.`; restoreCanvas = true; }
				else if ( event.target.closest( '[data-add-rule="field"]' ) ) {
					const source = conditionFields( index )[ 0 ];
					if ( ! source ) return;
					definition.fields[ index ].conditions.push( { field: source.key, operator: 'is', value: '' } );
					renderFieldInspector(); save();
					const rules = fieldInspector.querySelectorAll( '[data-rule-index]' );
					fieldInspector.querySelector( '[data-field-conditions]' ).open = true;
					rules[ rules.length - 1 ]?.querySelector( '[data-rule-field]' )?.focus();
					builderStatus.textContent = `Condition ${ definition.fields[ index ].conditions.length } added to ${ definition.fields[ index ].label }.`;
					return;
				} else if ( event.target.closest( '[data-remove-rule]' ) ) {
					const ruleIndex = Number( event.target.closest( '[data-rule-index]' ).dataset.ruleIndex );
					definition.fields[ index ].conditions.splice( ruleIndex, 1 );
					renderFieldInspector(); save();
					fieldInspector.querySelector( '[data-field-conditions]' ).open = true;
					fieldInspector.querySelector( '[data-add-rule="field"]' )?.focus();
					builderStatus.textContent = `Condition ${ ruleIndex + 1 } removed from ${ definition.fields[ index ].label }.`;
					return;
				}
				else return;
				renderFields(); renderNotifications();
				if ( selectedFieldIndex < 0 ) showSidebarPanel( 'add' );
				if ( restoreCanvas ) fieldsRoot.querySelector( `[data-field-index="${ selectedFieldIndex }"] [data-select-field]` )?.focus();
				if ( message ) builderStatus.textContent = message;
				return;
			}
			const notification = event.target.closest( '[data-notification-index]' );
			if ( notification ) { const index = Number( notification.dataset.notificationIndex ); if ( event.target.closest( '[data-remove-notification]' ) && window.confirm( window.cinderwellFormsAdmin.strings.confirmRemove ) ) definition.notifications.splice( index, 1 ); else if ( event.target.closest( '[data-duplicate-notification]' ) ) { const copy = clone( definition.notifications[ index ] ); copy.id = uuid(); copy.name += ' copy'; definition.notifications.splice( index + 1, 0, copy ); } else if ( event.target.closest( '[data-add-rule="notification"]' ) ) definition.notifications[ index ].conditions.push( { field: '', operator: 'is', value: '' } ); else if ( event.target.closest( '[data-remove-rule]' ) ) definition.notifications[ index ].conditions.splice( Number( event.target.closest( '[data-rule-index]' ).dataset.ruleIndex ), 1 ); else return; renderNotifications(); }
		} );
		root.querySelector( '[data-cw-tabs]' ).addEventListener( 'keydown', ( event ) => {
			if ( ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) return;
			const tabs = [ ...root.querySelectorAll( '[data-tab]' ) ]; const current = tabs.indexOf( document.activeElement ); if ( current < 0 ) return;
			event.preventDefault(); let next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : ( current + ( event.key === 'ArrowRight' ? 1 : -1 ) + tabs.length ) % tabs.length;
			tabs[ next ].focus(); tabs[ next ].click();
		} );
		root.querySelector( '.cw-builder-sidebar__tabs' ).addEventListener( 'keydown', ( event ) => {
			if ( ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) return;
			const current = sidebarTabs.indexOf( document.activeElement ); if ( current < 0 ) return;
			event.preventDefault(); const next = event.key === 'Home' ? 0 : event.key === 'End' ? sidebarTabs.length - 1 : ( current + ( event.key === 'ArrowRight' ? 1 : -1 ) + sidebarTabs.length ) % sidebarTabs.length;
			sidebarTabs[ next ].focus(); sidebarTabs[ next ].click();
		} );

		root.addEventListener( 'change', ( event ) => {
			if ( ! event.target.matches( '[data-choice-preset]' ) ) return;
			const panel = event.target.closest( '.cw-choice-preset' );
			const preset = choicePresets[ event.target.value ];
			const apply = panel?.querySelector( '[data-apply-choice-preset]' );
			const help = panel?.querySelector( '[data-choice-preset-help]' );
			if ( apply ) apply.disabled = ! preset;
			if ( help ) help.textContent = preset?.description || 'Choose a preset to replace the current choices. Display labels remain readable while submitted values use standard two-letter codes.';
		} );

		root.addEventListener( 'input', ( event ) => {
			if ( event.target.matches( '[name="confirmation_type"]' ) ) root.querySelectorAll( '[data-confirmation-setting]' ).forEach( ( setting ) => { setting.hidden = setting.dataset.confirmationSetting !== event.target.value; } );
			const card = event.target.closest( '[data-field-index]' );
			if ( card ) {
				const index = Number( card.dataset.fieldIndex ); const field = definition.fields[ index ];
				if ( event.target.matches( '[data-field-position]' ) ) {
					const to = Number( event.target.value );
					if ( to !== index && definition.fields[ to ] ) { const [ item ] = definition.fields.splice( index, 1 ); definition.fields.splice( to, 0, item ); selectedFieldIndex = to; renderFields(); renderNotifications(); fieldInspector.querySelector( '[data-field-position]' )?.focus(); builderStatus.textContent = `${ item.label } moved to position ${ to + 1 }.`; }
					return;
				}
				const prop = event.target.dataset.fieldProp;
				if ( prop ) { const oldKey = field.key; field[ prop ] = event.target.type === 'checkbox' ? event.target.checked : event.target.value; if ( prop === 'key' && oldKey !== field.key ) { definition.fields.forEach( ( item ) => ( item.conditions || [] ).forEach( ( condition ) => { if ( condition.field === oldKey ) condition.field = field.key; } ) ); definition.notifications.forEach( ( item ) => { if ( item.recipient_field === oldKey ) item.recipient_field = field.key; if ( item.reply_to_field === oldKey ) item.reply_to_field = field.key; ( item.conditions || [] ).forEach( ( condition ) => { if ( condition.field === oldKey ) condition.field = field.key; } ); } ); } }
				if ( event.target.matches( '[data-field-choices]' ) ) field.choices = event.target.value.split( '\n' ).map( ( line ) => line.trim() ).filter( Boolean ).map( ( line ) => { const parts = line.split( '|' ); return { label: parts[ 0 ].trim(), value: ( parts[ 1 ] || parts[ 0 ] ).trim() }; } );
				const rule = event.target.closest( '[data-rule-index]' ); if ( rule ) { const item = field.conditions[ Number( rule.dataset.ruleIndex ) ]; if ( event.target.matches( '[data-rule-field]' ) ) item.field = event.target.value; if ( event.target.matches( '[data-rule-operator]' ) ) { item.operator = event.target.value; const valueInput = rule.querySelector( '[data-rule-value]' ); const valueDisabled = [ 'empty', 'not_empty' ].includes( item.operator ); valueInput.disabled = valueDisabled; valueInput.placeholder = valueDisabled ? 'Not needed' : ''; valueInput.closest( '.cw-rule__value' )?.classList.toggle( 'is-disabled', valueDisabled ); } if ( event.target.matches( '[data-rule-value]' ) ) item.value = event.target.value; }
				if ( prop || event.target.matches( '[data-field-choices]' ) ) updateFieldPreview( index );
				save();
			}
			const notification = event.target.closest( '[data-notification-index]' ); if ( notification ) { const item = definition.notifications[ Number( notification.dataset.notificationIndex ) ]; const prop = event.target.dataset.notificationProp; if ( prop ) { item[ prop ] = event.target.type === 'checkbox' ? event.target.checked : event.target.value; if ( 'recipient_mode' === prop ) notification.querySelectorAll( '[data-recipient-setting]' ).forEach( ( setting ) => { setting.hidden = setting.dataset.recipientSetting !== event.target.value; } ); if ( 'name' === prop ) notification.querySelector( '[data-notification-title]' ).textContent = event.target.value || 'Notification'; if ( 'enabled' === prop ) { const status = notification.querySelector( '[data-notification-status]' ); status.textContent = event.target.checked ? 'Enabled' : 'Disabled'; status.classList.toggle( 'is-enabled', event.target.checked ); } } const rule = event.target.closest( '[data-rule-index]' ); if ( rule ) { const condition = item.conditions[ Number( rule.dataset.ruleIndex ) ]; if ( event.target.matches( '[data-rule-field]' ) ) condition.field = event.target.value; if ( event.target.matches( '[data-rule-operator]' ) ) { condition.operator = event.target.value; const valueInput = rule.querySelector( '[data-rule-value]' ); const valueDisabled = [ 'empty', 'not_empty' ].includes( condition.operator ); valueInput.disabled = valueDisabled; valueInput.placeholder = valueDisabled ? 'Not needed' : ''; valueInput.closest( '.cw-rule__value' )?.classList.toggle( 'is-disabled', valueDisabled ); } if ( event.target.matches( '[data-rule-value]' ) ) condition.value = event.target.value; } save(); }
		} );

		let dragged = null; let draggedType = ''; let draggedSource = null; let dropTarget = null; let dropMode = '';
		const clearDropTarget = () => { if ( dropTarget ) dropTarget.classList.remove( 'is-drop-before', 'is-drop-after', 'is-drop-left', 'is-drop-right' ); dropTarget = null; dropMode = ''; canvas.classList.remove( 'is-drop-target' ); };
		const clearDragState = () => { if ( dragged ) dragged.classList.remove( 'is-dragging' ); if ( draggedSource ) draggedSource.classList.remove( 'is-dragging' ); dragged = null; draggedType = ''; draggedSource = null; clearDropTarget(); };
		root.addEventListener( 'dragstart', ( event ) => {
			const fieldType = event.target.closest( '[data-cw-add-field]' );
			if ( fieldType ) { draggedType = fieldType.dataset.cwAddField; draggedSource = fieldType; fieldType.classList.add( 'is-dragging' ); event.dataTransfer.effectAllowed = 'copy'; event.dataTransfer.setData( 'text/plain', draggedType ); return; }
			if ( ! event.target.closest( '[data-drag-handle]' ) ) return;
			dragged = event.target.closest( '[data-field-index]' );
			if ( dragged ) {
				const bounds = dragged.getBoundingClientRect();
				const offsetX = Math.max( 0, Math.min( bounds.width, event.clientX - bounds.left ) );
				const offsetY = Math.max( 0, Math.min( bounds.height, event.clientY - bounds.top ) );
				event.dataTransfer.effectAllowed = 'move';
				event.dataTransfer.setData( 'text/plain', dragged.dataset.fieldIndex );
				event.dataTransfer.setDragImage( dragged, offsetX, offsetY );
				window.requestAnimationFrame( () => dragged?.classList.add( 'is-dragging' ) );
			}
		} );
		canvas.addEventListener( 'dragover', ( event ) => {
			if ( ! dragged && ! draggedType ) return;
			const target = event.target.closest( '[data-field-index]' );
			if ( dragged && target === dragged ) { clearDropTarget(); return; }
			event.preventDefault(); event.dataTransfer.dropEffect = draggedType ? 'copy' : 'move';
			clearDropTarget(); canvas.classList.add( 'is-drop-target' );
			if ( ! target ) return;
			dropTarget = target; const bounds = target.getBoundingClientRect();
			const relativeX = ( event.clientX - bounds.left ) / bounds.width;
			const relativeY = ( event.clientY - bounds.top ) / bounds.height;
			if ( relativeY < .25 ) dropMode = 'before';
			else if ( relativeY > .75 ) dropMode = 'after';
			else dropMode = relativeX < .5 ? 'left' : 'right';
			target.classList.add( `is-drop-${ dropMode }` );
		} );
		canvas.addEventListener( 'dragleave', ( event ) => { if ( ! canvas.contains( event.relatedTarget ) ) clearDropTarget(); } );
		canvas.addEventListener( 'drop', ( event ) => {
			if ( ! dragged && ! draggedType ) return;
			event.preventDefault(); const target = event.target.closest( '[data-field-index]' );
			const targetIndex = target ? Number( target.dataset.fieldIndex ) : -1;
			const pairedField = targetIndex >= 0 ? definition.fields[ targetIndex ] : null;
			const sideBySide = pairedField && [ 'left', 'right' ].includes( dropMode );
			let to = target ? targetIndex + ( [ 'after', 'right' ].includes( dropMode ) ? 1 : 0 ) : definition.fields.length; let item;
			if ( draggedType ) { item = newField( draggedType ); definition.fields.splice( to, 0, item ); }
			else { const from = Number( dragged.dataset.fieldIndex ); if ( target === dragged ) { clearDragState(); return; } [ item ] = definition.fields.splice( from, 1 ); if ( from < to ) to -= 1; definition.fields.splice( to, 0, item ); }
			item.width = sideBySide ? 'half' : 'full';
			if ( sideBySide ) pairedField.width = 'half';
			selectedFieldIndex = definition.fields.indexOf( item ); const action = draggedType ? 'added' : 'moved'; const message = sideBySide ? `${ item.label } placed to the ${ dropMode } of ${ pairedField.label }. Both fields are half width.` : `${ item.label } ${ action } at position ${ selectedFieldIndex + 1 }.`;
			clearDragState(); renderFields(); renderNotifications(); showSidebarPanel( 'settings' ); fieldInspector.querySelector( 'h2' )?.focus(); builderStatus.textContent = message;
		} );
		root.addEventListener( 'dragend', clearDragState );
		root.addEventListener( 'submit', save );
		renderFields(); renderNotifications();
	}

	document.querySelectorAll( '[data-cw-check-all]' ).forEach( ( checkbox ) => checkbox.addEventListener( 'change', () => checkbox.closest( 'table' ).querySelectorAll( 'tbody input[type="checkbox"]' ).forEach( ( item ) => { item.checked = checkbox.checked; } ) ) );
	document.querySelectorAll( 'select[name="entry_action"]' ).forEach( ( select ) => select.form.addEventListener( 'submit', ( event ) => { if ( select.value === 'delete' && ! window.confirm( 'Permanently delete the selected submissions? This cannot be undone.' ) ) event.preventDefault(); } ) );
} )();

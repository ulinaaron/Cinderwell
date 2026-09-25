<?php
namespace Cinderwell_Forms;

defined( 'ABSPATH' ) || exit;

/** Top-level Forms administration. */
class Admin {
	const PAGE = 'cinderwell-forms';
	const ENTRIES_PAGE = 'cinderwell-form-submissions';
	const SETTINGS_PAGE = 'cinderwell-form-settings';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
		add_action( 'admin_post_cinderwell_form_save', [ $this, 'save_form' ] );
		add_action( 'admin_post_cinderwell_form_delete', [ $this, 'delete_form' ] );
		add_action( 'admin_post_cinderwell_form_duplicate', [ $this, 'duplicate_form' ] );
		add_action( 'admin_post_cinderwell_form_entries', [ $this, 'entries_action' ] );
		add_action( 'admin_post_cinderwell_form_retry', [ $this, 'retry' ] );
		add_action( 'admin_post_cinderwell_form_export', [ $this, 'export' ] );
		add_action( 'admin_post_cinderwell_forms_settings', [ $this, 'save_settings' ] );
	}

	public function menu() {
		add_menu_page( __( 'Cinderwell Forms', 'cinderwell-forms' ), __( 'Forms', 'cinderwell-forms' ), Capabilities::MANAGE_FORMS, self::PAGE, [ $this, 'forms' ], 'dashicons-feedback', 27 );
		add_submenu_page( self::PAGE, __( 'Forms', 'cinderwell-forms' ), __( 'Forms', 'cinderwell-forms' ), Capabilities::MANAGE_FORMS, self::PAGE, [ $this, 'forms' ] );
		add_submenu_page( self::PAGE, __( 'Add New Form', 'cinderwell-forms' ), __( 'Add New', 'cinderwell-forms' ), Capabilities::MANAGE_FORMS, self::PAGE . '-new', [ $this, 'editor' ] );
		add_submenu_page( self::PAGE, __( 'Submissions', 'cinderwell-forms' ), __( 'Submissions', 'cinderwell-forms' ), Capabilities::VIEW_ENTRIES, self::ENTRIES_PAGE, [ $this, 'entries' ] );
		add_submenu_page( self::PAGE, __( 'Form Settings', 'cinderwell-forms' ), __( 'Settings', 'cinderwell-forms' ), Capabilities::MANAGE_SETTINGS, self::SETTINGS_PAGE, [ $this, 'settings' ] );
	}

	public function assets( $hook ) {
		if ( false === strpos( $hook, 'cinderwell-form' ) ) return;
		wp_enqueue_style( 'cinderwell-forms-admin', CINDERWELL_FORMS_URL . 'assets/admin.css', [], CINDERWELL_FORMS_VERSION );
		wp_enqueue_script( 'cinderwell-forms-admin', CINDERWELL_FORMS_URL . 'assets/admin.js', [], CINDERWELL_FORMS_VERSION, true );
		wp_localize_script( 'cinderwell-forms-admin', 'cinderwellFormsAdmin', [
			'fieldTypes' => Form_Repository::field_types(),
			'choicePresets' => Choice_Presets::all(),
			'adminEmail' => get_option( 'admin_email' ),
			'strings' => [
				'remove' => __( 'Remove', 'cinderwell-forms' ),
				'duplicate' => __( 'Duplicate', 'cinderwell-forms' ),
				'moveUp' => __( 'Move up', 'cinderwell-forms' ),
				'moveDown' => __( 'Move down', 'cinderwell-forms' ),
				'confirmRemove' => __( 'Remove this item?', 'cinderwell-forms' ),
				'choosePreset' => __( 'Choose a preset', 'cinderwell-forms' ),
				'applyPreset' => __( 'Replace choices', 'cinderwell-forms' ),
				'replaceChoices' => __( 'Replace the current choices with %s?', 'cinderwell-forms' ),
				'presetApplied' => __( '%s choices applied.', 'cinderwell-forms' ),
			],
		] );
	}

	private function header( $title, $description ) {
		echo '<header class="cw-forms-header"><div><span>' . esc_html__( 'Cinderwell Add-On', 'cinderwell-forms' ) . '</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div><b>v' . esc_html( CINDERWELL_FORMS_VERSION ) . '</b></header>';
	}

	private function notice() {
		if ( empty( $_GET['cw_notice'] ) ) return;
		$messages = [ 'saved' => __( 'Form saved.', 'cinderwell-forms' ), 'deleted' => __( 'Form moved to the trash. Stored submissions were retained.', 'cinderwell-forms' ), 'duplicated' => __( 'Form duplicated.', 'cinderwell-forms' ), 'entries' => __( 'Submissions updated.', 'cinderwell-forms' ), 'retried' => __( 'Notification retried.', 'cinderwell-forms' ), 'settings' => __( 'Settings saved.', 'cinderwell-forms' ) ];
		$key = sanitize_key( wp_unslash( $_GET['cw_notice'] ) );
		if ( isset( $messages[ $key ] ) ) echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $messages[ $key ] ) . '</p></div>';
	}

	public function forms() {
		$this->guard( Capabilities::MANAGE_FORMS );
		if ( 'edit' === ( $_GET['action'] ?? '' ) ) { $this->editor(); return; }
		$query = new \WP_Query( [ 'post_type' => Post_Type::POST_TYPE, 'post_status' => [ 'publish', 'draft' ], 'posts_per_page' => 100, 'orderby' => 'modified', 'order' => 'DESC' ] );
		?>
		<div class="wrap cw-forms-wrap"><?php $this->header( __( 'Forms', 'cinderwell-forms' ), __( 'Build accessible forms and review their activity.', 'cinderwell-forms' ) ); $this->notice(); ?>
			<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '-new' ) ); ?>"><?php esc_html_e( 'Add form', 'cinderwell-forms' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '-new&preset=newsletter' ) ); ?>"><?php esc_html_e( 'Add newsletter form', 'cinderwell-forms' ); ?></a></p>
			<div class="cw-forms-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Form', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Status', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Submissions', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Unread', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Modified', 'cinderwell-forms' ); ?></th></tr></thead><tbody>
			<?php if ( ! $query->posts ) : ?><tr><td colspan="5"><?php esc_html_e( 'No forms yet.', 'cinderwell-forms' ); ?></td></tr><?php endif; ?>
			<?php foreach ( $query->posts as $post ) : $edit = admin_url( 'admin.php?page=' . self::PAGE . '&action=edit&form=' . $post->ID ); ?>
				<tr><td><strong><a href="<?php echo esc_url( $edit ); ?>"><?php echo esc_html( get_the_title( $post ) ); ?></a></strong><div class="row-actions"><a href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit', 'cinderwell-forms' ); ?></a> | <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cinderwell_form_duplicate&form=' . $post->ID ), 'cinderwell_form_duplicate_' . $post->ID ) ); ?>"><?php esc_html_e( 'Duplicate', 'cinderwell-forms' ); ?></a> | <a class="submitdelete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cinderwell_form_delete&form=' . $post->ID ), 'cinderwell_form_delete_' . $post->ID ) ); ?>"><?php esc_html_e( 'Trash', 'cinderwell-forms' ); ?></a></div></td><td><?php echo esc_html( 'publish' === $post->post_status ? __( 'Published', 'cinderwell-forms' ) : __( 'Draft', 'cinderwell-forms' ) ); ?></td><td><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ENTRIES_PAGE . '&form_id=' . $post->ID ) ); ?>"><?php echo esc_html( Entry_Repository::count( $post->ID ) ); ?></a></td><td><?php echo esc_html( Entry_Repository::count( $post->ID, 'unread' ) ); ?></td><td><?php echo esc_html( get_the_modified_date( '', $post ) ); ?></td></tr>
			<?php endforeach; ?></tbody></table></div>
		</div><?php
	}

	public function editor() {
		$this->guard( Capabilities::MANAGE_FORMS );
		$form_id = absint( $_GET['form'] ?? 0 ); $form = $form_id ? Form_Repository::get( $form_id ) : null;
		if ( $form_id && ! $form ) wp_die( esc_html__( 'Form not found.', 'cinderwell-forms' ), '', [ 'response' => 404 ] );
		$preset = $form_id ? null : Form_Repository::starter_template( $_GET['preset'] ?? '' );
		$form = $form ?: [ 'id' => 0, 'title' => $preset['title'] ?? '', 'status' => 'publish', 'definition' => $preset['definition'] ?? Form_Repository::defaults() ];
		$pages = get_pages( [ 'post_status' => 'publish' ] );
		$field_groups = [
			__( 'Basic fields', 'cinderwell-forms' ) => [
				'text'     => 'dashicons-editor-textcolor',
				'textarea' => 'dashicons-editor-alignleft',
				'number'   => 'dashicons-editor-ol',
			],
			__( 'Contact fields', 'cinderwell-forms' ) => [
				'email' => 'dashicons-email',
				'tel'   => 'dashicons-phone',
			],
			__( 'Choice fields', 'cinderwell-forms' ) => [
				'select'     => 'dashicons-arrow-down-alt2',
				'radio'      => 'dashicons-marker',
				'checkboxes' => 'dashicons-yes-alt',
				'consent'    => 'dashicons-privacy',
			],
			__( 'Layout and data', 'cinderwell-forms' ) => [
				'content' => 'dashicons-media-text',
				'divider' => 'dashicons-minus',
				'hidden'  => 'dashicons-hidden',
			],
		];
		$field_types = Form_Repository::field_types();
		$has_fields  = ! empty( $form['definition']['fields'] );
		?>
		<div class="wrap cw-forms-wrap cw-forms-wrap--editor"><?php $this->header( $form_id ? __( 'Edit form', 'cinderwell-forms' ) : ( $preset ? __( 'Add newsletter form', 'cinderwell-forms' ) : __( 'Add form', 'cinderwell-forms' ) ), __( 'Build the form in reading order. Fields may be full or half width without changing that order.', 'cinderwell-forms' ) ); $this->notice(); ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ); ?>">&larr; <?php esc_html_e( 'Back to forms', 'cinderwell-forms' ); ?></a></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-cw-form-builder>
				<input type="hidden" name="action" value="cinderwell_form_save"><input type="hidden" name="form_id" value="<?php echo esc_attr( $form['id'] ); ?>"><?php wp_nonce_field( 'cinderwell_form_save' ); ?>
				<label class="cw-forms-title"><span><?php esc_html_e( 'Form name', 'cinderwell-forms' ); ?></span><input type="text" name="form_title" value="<?php echo esc_attr( $form['title'] ); ?>" required></label>
				<nav class="cw-forms-tabs" aria-label="<?php esc_attr_e( 'Form settings', 'cinderwell-forms' ); ?>" role="tablist" data-cw-tabs><button id="cw-form-tab-fields" type="button" role="tab" aria-controls="cw-form-panel-fields" aria-selected="true" tabindex="0" data-tab="fields"><?php esc_html_e( 'Fields', 'cinderwell-forms' ); ?></button><button id="cw-form-tab-notifications" type="button" role="tab" aria-controls="cw-form-panel-notifications" aria-selected="false" tabindex="-1" data-tab="notifications"><?php esc_html_e( 'Notifications', 'cinderwell-forms' ); ?></button><button id="cw-form-tab-confirmation" type="button" role="tab" aria-controls="cw-form-panel-confirmation" aria-selected="false" tabindex="-1" data-tab="confirmation"><?php esc_html_e( 'Confirmation', 'cinderwell-forms' ); ?></button><button id="cw-form-tab-settings" type="button" role="tab" aria-controls="cw-form-panel-settings" aria-selected="false" tabindex="-1" data-tab="settings"><?php esc_html_e( 'Settings', 'cinderwell-forms' ); ?></button></nav>
				<input type="hidden" name="form_definition" value="<?php echo esc_attr( wp_json_encode( $form['definition'] ) ); ?>" data-cw-definition>
				<section id="cw-form-panel-fields" class="cw-forms-panel" role="tabpanel" aria-labelledby="cw-form-tab-fields" data-panel="fields"><div class="cw-form-builder-layout"><div class="cw-form-canvas"><div data-cw-fields></div><p class="screen-reader-text" aria-live="polite" data-cw-builder-status></p></div><aside class="cw-builder-sidebar" aria-label="<?php esc_attr_e( 'Form builder tools', 'cinderwell-forms' ); ?>"><div class="cw-builder-sidebar__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Form builder tools', 'cinderwell-forms' ); ?>"><button id="cw-sidebar-tab-add" type="button" role="tab" aria-controls="cw-sidebar-panel-add" aria-selected="<?php echo $has_fields ? 'false' : 'true'; ?>" tabindex="<?php echo $has_fields ? '-1' : '0'; ?>" data-cw-sidebar-tab="add"><?php esc_html_e( 'Add fields', 'cinderwell-forms' ); ?></button><button id="cw-sidebar-tab-settings" type="button" role="tab" aria-controls="cw-sidebar-panel-settings" aria-selected="<?php echo $has_fields ? 'true' : 'false'; ?>" tabindex="<?php echo $has_fields ? '0' : '-1'; ?>" data-cw-sidebar-tab="settings"><?php esc_html_e( 'Field settings', 'cinderwell-forms' ); ?></button></div><section id="cw-sidebar-panel-add" class="cw-field-library" role="tabpanel" aria-labelledby="cw-sidebar-tab-add" data-cw-sidebar-panel="add" <?php echo $has_fields ? 'hidden' : ''; ?>><div class="cw-field-library__heading"><div><h2 id="cw-field-library-title"><?php esc_html_e( 'Add a field', 'cinderwell-forms' ); ?></h2><p><?php esc_html_e( 'Click a field or drag it into the canvas.', 'cinderwell-forms' ); ?></p></div></div><div class="cw-field-library__groups"><?php foreach ( $field_groups as $group_label => $types ) : ?><section class="cw-field-library__group"><h3><?php echo esc_html( $group_label ); ?></h3><div class="cw-field-library__items"><?php foreach ( $types as $type => $icon ) : ?><button type="button" draggable="true" class="cw-field-type" data-cw-add-field="<?php echo esc_attr( $type ); ?>"><span class="dashicons <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $field_types[ $type ] ); ?></span></button><?php endforeach; ?></div></section><?php endforeach; ?></div></section><section id="cw-sidebar-panel-settings" class="cw-field-inspector" role="tabpanel" aria-labelledby="cw-sidebar-tab-settings" data-cw-sidebar-panel="settings" data-cw-field-inspector <?php echo $has_fields ? '' : 'hidden'; ?>></section></aside></div></section>
				<section id="cw-form-panel-notifications" class="cw-forms-panel cw-notifications-panel" role="tabpanel" aria-labelledby="cw-form-tab-notifications" data-panel="notifications" hidden><div class="cw-panel-intro"><div><h2><?php esc_html_e( 'Notifications', 'cinderwell-forms' ); ?></h2><p><?php esc_html_e( 'Send independent messages to administrators or an email address submitted with the form.', 'cinderwell-forms' ); ?></p></div><p><?php esc_html_e( 'WordPress accepting a message does not confirm delivery.', 'cinderwell-forms' ); ?></p></div><div data-cw-notifications></div><button type="button" class="button button-secondary cw-add-notification" data-cw-add-notification><?php esc_html_e( 'Add notification', 'cinderwell-forms' ); ?></button></section>
				<section id="cw-form-panel-confirmation" class="cw-forms-panel cw-forms-panel--card" role="tabpanel" aria-labelledby="cw-form-tab-confirmation" data-panel="confirmation" hidden><div class="cw-form-settings-card"><header><h2><?php esc_html_e( 'Confirmation', 'cinderwell-forms' ); ?></h2><p><?php esc_html_e( 'Choose what visitors see after a successful submission.', 'cinderwell-forms' ); ?></p></header><div class="cw-form-settings-card__body"><fieldset class="cw-choice-list"><legend><?php esc_html_e( 'After a successful submission', 'cinderwell-forms' ); ?></legend><label><input type="radio" name="confirmation_type" value="message" <?php checked( $form['definition']['confirmation']['type'], 'message' ); ?>> <span><?php esc_html_e( 'Show a confirmation message', 'cinderwell-forms' ); ?></span></label><label><input type="radio" name="confirmation_type" value="page" <?php checked( $form['definition']['confirmation']['type'], 'page' ); ?>> <span><?php esc_html_e( 'Go to a WordPress page', 'cinderwell-forms' ); ?></span></label></fieldset><label data-confirmation-setting="message" <?php echo 'message' === $form['definition']['confirmation']['type'] ? '' : 'hidden'; ?>><span><?php esc_html_e( 'Confirmation message', 'cinderwell-forms' ); ?></span><textarea name="confirmation_message" rows="4"><?php echo esc_textarea( $form['definition']['confirmation']['message'] ); ?></textarea></label><label data-confirmation-setting="page" <?php echo 'page' === $form['definition']['confirmation']['type'] ? '' : 'hidden'; ?>><span><?php esc_html_e( 'Thank-you page', 'cinderwell-forms' ); ?></span><select name="confirmation_page"><option value="0"><?php esc_html_e( 'Select a page', 'cinderwell-forms' ); ?></option><?php foreach ( $pages as $page ) : ?><option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $form['definition']['confirmation']['page_id'], $page->ID ); ?>><?php echo esc_html( $page->post_title ); ?></option><?php endforeach; ?></select></label></div></div></section>
				<section id="cw-form-panel-settings" class="cw-forms-panel cw-forms-panel--card" role="tabpanel" aria-labelledby="cw-form-tab-settings" data-panel="settings" hidden><div class="cw-form-settings-card"><header><h2><?php esc_html_e( 'Form settings', 'cinderwell-forms' ); ?></h2><p><?php esc_html_e( 'Control presentation, submission behavior, and spam protection for this form.', 'cinderwell-forms' ); ?></p></header><div class="cw-form-settings-card__body"><fieldset class="cw-choice-list"><legend><?php esc_html_e( 'Form type', 'cinderwell-forms' ); ?></legend><label><input type="radio" name="form_type" value="standard" <?php checked( $form['definition']['settings']['type'], 'standard' ); ?>> <span><?php esc_html_e( 'Standard', 'cinderwell-forms' ); ?></span></label><label><input type="radio" name="form_type" value="newsletter" <?php checked( $form['definition']['settings']['type'], 'newsletter' ); ?>> <span><?php esc_html_e( 'Newsletter signup', 'cinderwell-forms' ); ?></span></label></fieldset><label><span><?php esc_html_e( 'Submit button label', 'cinderwell-forms' ); ?></span><input type="text" name="submit_label" value="<?php echo esc_attr( $form['definition']['settings']['submit_label'] ); ?>"></label><label class="cw-forms-check cw-setting-option"><input type="checkbox" name="turnstile" value="1" <?php checked( $form['definition']['settings']['turnstile'] ); ?>> <span><strong><?php esc_html_e( 'Use Cloudflare Turnstile', 'cinderwell-forms' ); ?></strong><small><?php esc_html_e( 'Adds spam protection when credentials are configured in Forms Settings.', 'cinderwell-forms' ); ?></small></span></label></div></div></section>
				<div class="cw-forms-save"><label><input type="checkbox" name="published" value="1" <?php checked( $form['status'], 'publish' ); ?>> <?php echo esc_html( $form_id ? __( 'Published', 'cinderwell-forms' ) : __( 'Publish immediately', 'cinderwell-forms' ) ); ?></label><button class="button button-primary button-large" type="submit"><?php echo esc_html( $form_id ? __( 'Save form', 'cinderwell-forms' ) : __( 'Publish form', 'cinderwell-forms' ) ); ?></button></div>
			</form>
		</div><?php
	}

	public function save_form() {
		$this->guard( Capabilities::MANAGE_FORMS ); check_admin_referer( 'cinderwell_form_save' );
		$id = absint( $_POST['form_id'] ?? 0 ); $title = sanitize_text_field( wp_unslash( $_POST['form_title'] ?? '' ) );
		if ( $id && ( Post_Type::POST_TYPE !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) ) wp_die( esc_html__( 'You cannot edit this form.', 'cinderwell-forms' ), '', [ 'response' => 403 ] );
		$raw = json_decode( wp_unslash( $_POST['form_definition'] ?? '{}' ), true ); $raw = is_array( $raw ) ? $raw : [];
		$raw['confirmation'] = [ 'type' => sanitize_key( $_POST['confirmation_type'] ?? 'message' ), 'message' => sanitize_textarea_field( wp_unslash( $_POST['confirmation_message'] ?? '' ) ), 'page_id' => absint( $_POST['confirmation_page'] ?? 0 ) ];
		$raw['settings'] = [ 'type' => sanitize_key( $_POST['form_type'] ?? 'standard' ), 'submit_label' => sanitize_text_field( wp_unslash( $_POST['submit_label'] ?? '' ) ), 'turnstile' => ! empty( $_POST['turnstile'] ) ];
		$post = [ 'post_type' => Post_Type::POST_TYPE, 'post_title' => $title ?: __( 'Untitled form', 'cinderwell-forms' ), 'post_status' => ! empty( $_POST['published'] ) ? 'publish' : 'draft' ]; if ( $id ) $post['ID'] = $id;
		$id = wp_insert_post( $post, true ); if ( is_wp_error( $id ) ) wp_die( esc_html( $id->get_error_message() ) );
		update_post_meta( $id, Post_Type::META_DEFINITION, Form_Repository::sanitize_definition( $raw ) );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&action=edit&form=' . $id . '&cw_notice=saved' ) ); exit;
	}

	public function delete_form() { $this->guard( Capabilities::MANAGE_FORMS ); $id = absint( $_GET['form'] ?? 0 ); check_admin_referer( 'cinderwell_form_delete_' . $id ); if ( Post_Type::POST_TYPE !== get_post_type( $id ) || ! current_user_can( 'delete_post', $id ) ) wp_die( esc_html__( 'You cannot delete this form.', 'cinderwell-forms' ), '', [ 'response' => 403 ] ); wp_trash_post( $id ); wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&cw_notice=deleted' ) ); exit; }
	public function duplicate_form() { $this->guard( Capabilities::MANAGE_FORMS ); $id = absint( $_GET['form'] ?? 0 ); check_admin_referer( 'cinderwell_form_duplicate_' . $id ); $form = Form_Repository::get( $id ); if ( $form && current_user_can( 'edit_post', $id ) ) { $new = wp_insert_post( [ 'post_type' => Post_Type::POST_TYPE, 'post_title' => $form['title'] . ' ' . __( 'Copy', 'cinderwell-forms' ), 'post_status' => 'draft' ] ); update_post_meta( $new, Post_Type::META_DEFINITION, $form['definition'] ); } wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE . '&cw_notice=duplicated' ) ); exit; }

	public function entries() {
		$this->guard( Capabilities::VIEW_ENTRIES );
		if ( ! empty( $_GET['entry'] ) ) { $this->entry( absint( $_GET['entry'] ) ); return; }
		$args = [ 'form_id' => absint( $_GET['form_id'] ?? 0 ), 'status' => sanitize_key( $_GET['status'] ?? '' ), 'starred' => isset( $_GET['starred'] ) ? true : null, 'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), 'date_from' => sanitize_text_field( $_GET['date_from'] ?? '' ), 'date_to' => sanitize_text_field( $_GET['date_to'] ?? '' ), 'page' => absint( $_GET['paged'] ?? 1 ) ];
		$result = Entry_Repository::query( $args ); $forms = get_posts( [ 'post_type' => Post_Type::POST_TYPE, 'post_status' => [ 'publish', 'draft', 'trash' ], 'numberposts' => -1 ] );
		?>
		<div class="wrap cw-forms-wrap"><?php $this->header( __( 'Submissions', 'cinderwell-forms' ), __( 'Review and export immutable form responses.', 'cinderwell-forms' ) ); $this->notice(); ?>
		<form method="get" class="cw-forms-filters"><input type="hidden" name="page" value="<?php echo esc_attr( self::ENTRIES_PAGE ); ?>"><select name="form_id" aria-label="<?php esc_attr_e( 'Filter by form', 'cinderwell-forms' ); ?>"><option value="0"><?php esc_html_e( 'All forms', 'cinderwell-forms' ); ?></option><?php foreach ( $forms as $form ) : ?><option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( $args['form_id'], $form->ID ); ?>><?php echo esc_html( $form->post_title ); ?></option><?php endforeach; ?></select><select name="status" aria-label="<?php esc_attr_e( 'Filter by status', 'cinderwell-forms' ); ?>"><option value=""><?php esc_html_e( 'All states', 'cinderwell-forms' ); ?></option><option value="unread" <?php selected( $args['status'], 'unread' ); ?>><?php esc_html_e( 'Unread', 'cinderwell-forms' ); ?></option><option value="read" <?php selected( $args['status'], 'read' ); ?>><?php esc_html_e( 'Read', 'cinderwell-forms' ); ?></option></select><label class="cw-forms-filter-check"><input type="checkbox" name="starred" value="1" <?php checked( null !== $args['starred'] ); ?>> <?php esc_html_e( 'Starred only', 'cinderwell-forms' ); ?></label><input type="date" name="date_from" value="<?php echo esc_attr( $args['date_from'] ); ?>" aria-label="<?php esc_attr_e( 'From date', 'cinderwell-forms' ); ?>"><input type="date" name="date_to" value="<?php echo esc_attr( $args['date_to'] ); ?>" aria-label="<?php esc_attr_e( 'To date', 'cinderwell-forms' ); ?>"><input type="search" name="s" value="<?php echo esc_attr( $args['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search submissions', 'cinderwell-forms' ); ?>"><button class="button"><?php esc_html_e( 'Filter', 'cinderwell-forms' ); ?></button><a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array_merge( $args, [ 'action' => 'cinderwell_form_export' ] ), admin_url( 'admin-post.php' ) ), 'cinderwell_form_export' ) ); ?>"><?php esc_html_e( 'Export CSV', 'cinderwell-forms' ); ?></a></form>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="cinderwell_form_entries"><?php wp_nonce_field( 'cinderwell_form_entries' ); ?><div class="cw-forms-bulk"><select name="entry_action" required><option value=""><?php esc_html_e( 'Bulk actions', 'cinderwell-forms' ); ?></option><option value="read"><?php esc_html_e( 'Mark read', 'cinderwell-forms' ); ?></option><option value="unread"><?php esc_html_e( 'Mark unread', 'cinderwell-forms' ); ?></option><option value="star"><?php esc_html_e( 'Star', 'cinderwell-forms' ); ?></option><option value="unstar"><?php esc_html_e( 'Remove star', 'cinderwell-forms' ); ?></option><option value="delete"><?php esc_html_e( 'Delete permanently', 'cinderwell-forms' ); ?></option></select><button class="button"><?php esc_html_e( 'Apply', 'cinderwell-forms' ); ?></button></div><div class="cw-forms-table-wrap"><table class="widefat striped"><thead><tr><td class="check-column"><input type="checkbox" data-cw-check-all aria-label="<?php esc_attr_e( 'Select all submissions', 'cinderwell-forms' ); ?>"></td><th><?php esc_html_e( 'Submission', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Form', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'State', 'cinderwell-forms' ); ?></th><th><?php esc_html_e( 'Submitted', 'cinderwell-forms' ); ?></th></tr></thead><tbody><?php if ( ! $result['items'] ) : ?><tr><td colspan="5"><?php esc_html_e( 'No submissions found.', 'cinderwell-forms' ); ?></td></tr><?php endif; foreach ( $result['items'] as $item ) : ?><tr><th class="check-column"><input type="checkbox" name="entry_ids[]" value="<?php echo esc_attr( $item['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Select submission %d', 'cinderwell-forms' ), $item['id'] ) ); ?>"></th><td><strong><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ENTRIES_PAGE . '&entry=' . $item['id'] ) ); ?>">#<?php echo esc_html( $item['id'] ); ?></a></strong><?php if ( $item['starred'] ) echo ' <span class="dashicons dashicons-star-filled"><span class="screen-reader-text">' . esc_html__( 'Starred', 'cinderwell-forms' ) . '</span></span>'; ?></td><td><?php echo esc_html( $item['form_title'] ); ?></td><td><?php echo esc_html( ucfirst( $item['status'] ) ); ?></td><td><?php echo esc_html( get_date_from_gmt( $item['created_utc'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></td></tr><?php endforeach; ?></tbody></table></div></form>
		<?php $this->pagination( $result['pages'], $args['page'] ); ?></div><?php
	}

	private function entry( $id ) {
		$entry = Entry_Repository::get( $id ); if ( ! $entry ) wp_die( esc_html__( 'Submission not found.', 'cinderwell-forms' ), '', [ 'response' => 404 ] );
		global $wpdb; $deliveries = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Database::table( 'deliveries' ) . ' WHERE entry_id=%d ORDER BY id', $id ), ARRAY_A );
		Entry_Repository::update_state( [ $id ], 'read' );
		?><div class="wrap cw-forms-wrap"><?php $this->header( sprintf( __( 'Submission #%d', 'cinderwell-forms' ), $id ), $entry['form_title'] ); ?><p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::ENTRIES_PAGE ) ); ?>">&larr; <?php esc_html_e( 'Back to submissions', 'cinderwell-forms' ); ?></a></p><div class="cw-entry-grid"><main class="cw-entry-values"><dl><?php foreach ( $entry['values'] as $value ) : ?><div><dt><?php echo esc_html( $value['label'] ); ?></dt><dd><?php echo esc_html( is_array( $value['value'] ) ? implode( ', ', $value['value'] ) : $value['value'] ); ?></dd></div><?php endforeach; ?></dl></main><aside><section class="cw-entry-card"><h2><?php esc_html_e( 'Details', 'cinderwell-forms' ); ?></h2><p><strong><?php esc_html_e( 'Submitted:', 'cinderwell-forms' ); ?></strong><br><?php echo esc_html( get_date_from_gmt( $entry['created_utc'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></p><p><strong><?php esc_html_e( 'Source:', 'cinderwell-forms' ); ?></strong><br><a href="<?php echo esc_url( $entry['source_url'] ); ?>"><?php echo esc_html( $entry['source_url'] ); ?></a></p></section><section class="cw-entry-card"><h2><?php esc_html_e( 'Notifications', 'cinderwell-forms' ); ?></h2><?php if ( ! $deliveries ) : ?><p><?php esc_html_e( 'No notification rules ran.', 'cinderwell-forms' ); ?></p><?php endif; foreach ( $deliveries as $delivery ) : ?><div class="cw-delivery"><strong><?php echo esc_html( $delivery['rule_name'] ); ?></strong><span><?php echo esc_html( $delivery['recipient'] . ' — ' . $delivery['status'] ); ?></span><?php if ( 'failed' === $delivery['status'] ) : ?><small><?php echo esc_html( $delivery['last_error'] ); ?></small><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cinderwell_form_retry&delivery=' . $delivery['id'] ), 'cinderwell_form_retry_' . $delivery['id'] ) ); ?>"><?php esc_html_e( 'Retry', 'cinderwell-forms' ); ?></a><?php endif; ?></div><?php endforeach; ?></section></aside></div></div><?php
	}

	public function entries_action() { $this->guard( Capabilities::MANAGE_ENTRIES ); check_admin_referer( 'cinderwell_form_entries' ); $action = sanitize_key( $_POST['entry_action'] ?? '' ); if ( in_array( $action, [ 'read', 'unread', 'star', 'unstar', 'delete' ], true ) ) Entry_Repository::update_state( (array) ( $_POST['entry_ids'] ?? [] ), $action ); wp_safe_redirect( admin_url( 'admin.php?page=' . self::ENTRIES_PAGE . '&cw_notice=entries' ) ); exit; }
	public function retry() { $this->guard( Capabilities::MANAGE_ENTRIES ); $id = absint( $_GET['delivery'] ?? 0 ); check_admin_referer( 'cinderwell_form_retry_' . $id ); global $wpdb; $entry = absint( $wpdb->get_var( $wpdb->prepare( 'SELECT entry_id FROM ' . Database::table( 'deliveries' ) . ' WHERE id=%d', $id ) ) ); Notifications::send( $id ); wp_safe_redirect( admin_url( 'admin.php?page=' . self::ENTRIES_PAGE . '&entry=' . $entry . '&cw_notice=retried' ) ); exit; }

	public function export() {
		$this->guard( Capabilities::VIEW_ENTRIES ); check_admin_referer( 'cinderwell_form_export' ); $args = [ 'form_id' => absint( $_GET['form_id'] ?? 0 ), 'status' => sanitize_key( $_GET['status'] ?? '' ), 'starred' => isset( $_GET['starred'] ) ? true : null, 'search' => sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) ), 'date_from' => sanitize_text_field( $_GET['date_from'] ?? '' ), 'date_to' => sanitize_text_field( $_GET['date_to'] ?? '' ), 'per_page' => 200, 'page' => 1 ];
		nocache_headers(); header( 'Content-Type: text/csv; charset=utf-8' ); header( 'Content-Disposition: attachment; filename=cinderwell-form-submissions-' . gmdate( 'Y-m-d' ) . '.csv' ); $out = fopen( 'php://output', 'w' ); fputcsv( $out, [ 'ID', 'Form', 'Submitted', 'State', 'Field', 'Value' ] ); do { $result = Entry_Repository::query( $args ); foreach ( $result['items'] as $row ) { $entry = Entry_Repository::get( $row['id'] ); foreach ( $entry['values'] as $value ) fputcsv( $out, [ $entry['id'], self::csv( $entry['form_title'] ), $entry['created_utc'], $entry['status'], self::csv( $value['label'] ), self::csv( is_array( $value['value'] ) ? implode( ', ', $value['value'] ) : $value['value'] ) ] ); } $args['page']++; } while ( $args['page'] <= $result['pages'] ); fclose( $out ); exit;
	}

	private static function csv( $value ) { $value = (string) $value; return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value; }

	public function settings() {
		$this->guard( Capabilities::MANAGE_SETTINGS ); $settings = wp_parse_args( get_option( 'cinderwell_forms_settings', [] ), [ 'turnstile_site_key' => '', 'turnstile_secret' => '', 'retention_days' => 0 ] );
		?><div class="wrap cw-forms-wrap"><?php $this->header( __( 'Form Settings', 'cinderwell-forms' ), __( 'Configure shared spam protection and submission retention.', 'cinderwell-forms' ) ); $this->notice(); ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cw-forms-settings"><input type="hidden" name="action" value="cinderwell_forms_settings"><?php wp_nonce_field( 'cinderwell_forms_settings' ); ?><section><h2><?php esc_html_e( 'Cloudflare Turnstile', 'cinderwell-forms' ); ?></h2><p><?php esc_html_e( 'Forms only render Turnstile when enabled on that form and both credentials are available.', 'cinderwell-forms' ); ?></p><label><span><?php esc_html_e( 'Site key', 'cinderwell-forms' ); ?></span><input type="text" name="turnstile_site_key" value="<?php echo esc_attr( $settings['turnstile_site_key'] ); ?>" autocomplete="off"></label><label><span><?php esc_html_e( 'Secret key', 'cinderwell-forms' ); ?></span><input type="password" name="turnstile_secret" value="" placeholder="<?php echo esc_attr( $settings['turnstile_secret'] ? __( 'Stored — leave blank to keep it', 'cinderwell-forms' ) : '' ); ?>" autocomplete="new-password"></label><?php if ( defined( 'CINDERWELL_FORMS_TURNSTILE_SECRET' ) ) : ?><p class="description"><?php esc_html_e( 'The secret is supplied by CINDERWELL_FORMS_TURNSTILE_SECRET and cannot be changed here.', 'cinderwell-forms' ); ?></p><?php endif; ?></section><section><h2><?php esc_html_e( 'Retention', 'cinderwell-forms' ); ?></h2><label><span><?php esc_html_e( 'Delete submissions after', 'cinderwell-forms' ); ?></span><input type="number" name="retention_days" min="0" step="1" value="<?php echo esc_attr( $settings['retention_days'] ); ?>"><small><?php esc_html_e( 'Days. Use 0 to keep submissions indefinitely. Deletion includes field values and notification history.', 'cinderwell-forms' ); ?></small></label></section><button class="button button-primary" type="submit"><?php esc_html_e( 'Save settings', 'cinderwell-forms' ); ?></button></form></div><?php
	}

	public function save_settings() { $this->guard( Capabilities::MANAGE_SETTINGS ); check_admin_referer( 'cinderwell_forms_settings' ); $old = get_option( 'cinderwell_forms_settings', [] ); $secret = defined( 'CINDERWELL_FORMS_TURNSTILE_SECRET' ) ? ( $old['turnstile_secret'] ?? '' ) : sanitize_text_field( wp_unslash( $_POST['turnstile_secret'] ?? '' ) ); update_option( 'cinderwell_forms_settings', [ 'turnstile_site_key' => sanitize_text_field( wp_unslash( $_POST['turnstile_site_key'] ?? '' ) ), 'turnstile_secret' => $secret ?: ( $old['turnstile_secret'] ?? '' ), 'retention_days' => absint( $_POST['retention_days'] ?? 0 ) ], false ); wp_safe_redirect( admin_url( 'admin.php?page=' . self::SETTINGS_PAGE . '&cw_notice=settings' ) ); exit; }
	private function pagination( $pages, $current ) { if ( $pages < 2 ) return; echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( [ 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => max( 1, $current ), 'total' => $pages ] ) ) . '</div></div>'; }
	private function guard( $cap ) { if ( ! current_user_can( $cap ) ) wp_die( esc_html__( 'You are not allowed to access this screen.', 'cinderwell-forms' ), '', [ 'response' => 403 ] ); }
}

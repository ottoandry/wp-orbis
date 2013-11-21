<?php

class Orbis_Core_Plugin extends Orbis_Plugin {
	public function __construct( $file ) {
		parent::__construct( $file );

		$this->set_name( 'orbis' );
		$this->set_db_version( '1.0.0' );

		// Actions
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'p2p_init', array( $this, 'p2p_init' ) );

		// Includes
		$this->plugin_include( 'includes/deprecated.php' );
		$this->plugin_include( 'includes/administration.php' );
		$this->plugin_include( 'includes/email.php' );
		$this->plugin_include( 'includes/post.php' );
		$this->plugin_include( 'includes/project.php' );
		$this->plugin_include( 'includes/template.php' );
		$this->plugin_include( 'includes/project-template.php' );

		// Tables
		orbis_register_table( 'orbis_companies' );
		orbis_register_table( 'orbis_projects' );

		// API
		$this->api = new Orbis_API();

		// Admin
		if ( is_admin() ) {
			global $orbis_admin;

			$orbis_admin = new Orbis_Core_Admin( $this );
		}
	}

	public function init() {
		// Scripts
		wp_register_script(
			'select2',
			$this->plugin_url( 'includes/select2/select2.js' ),
			array( 'jquery' ),
			'3.4.2'
		);

		wp_register_script(
			'orbis-autocomplete',
			$this->plugin_url( 'includes/js/autocomplete.js' ),
			array( 'jquery', 'jquery-ui-autocomplete', 'select2' ),
			'1.0.0'
		);

		$translation_array = array(
			'noMatches'             => __( 'No matches found', 'orbis' ),
			'inputTooShort'         => sprintf( __( 'Please enter %s more characters', 'orbis' ), '{todo}' ),
			'selectionTooBigSingle' => sprintf( __( 'You can only select %s item', 'orbis' ), '{limit}' ),
			'selectionTooBigPlural' => sprintf( __( 'You can only select %s items', 'orbis' ), '{limit}' ),
			'loadMore'              => __( 'Loading more results...', 'orbis' ),
			'searching'             => __( 'Searching...', 'orbis' )
		);

		wp_localize_script( 'orbis-autocomplete', 'orbisl10n', $translation_array );

		// Styles
		wp_register_style(
			'select2',
			$this->plugin_url( 'includes/select2/select2.css' ),
			array(),
			'3.4.1'
		);
	}

	/**
	 * Posts to posts initialize
	 */
	public function p2p_init() {
		p2p_register_connection_type( array(
			'name' => 'orbis_person_to_company',
			'from' => 'orbis_person',
			'to'   => 'orbis_company'
		) );
	}

	public function loaded() {
		$this->load_textdomain( 'orbis', '/languages/' );
	}

	/**
	 * Install
	 * 
	 * @mysql UPDATE wp_options SET option_value = 0 WHERE option_name = 'orbis_db_version';
	 * 
	 * @see Orbis_Plugin::install()
	 */
	public function install() {
		// Tables
		orbis_install_table( 'orbis_companies', '
			id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED DEFAULT NULL,
			name VARCHAR(128) NOT NULL,
			PRIMARY KEY  (id)
		' );

		orbis_install_table( 'orbis_projects', '
			id BIGINT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED DEFAULT NULL,
			name VARCHAR(128) NOT NULL,
			principal_id BIGINT(16) UNSIGNED DEFAULT NULL,
			start_date DATE NOT NULL DEFAULT "0000-00-00",
			number_seconds INT(16) NOT NULL DEFAULT 0,
			invoicable BOOLEAN NOT NULL DEFAULT TRUE,
			invoiced BOOLEAN NOT NULL DEFAULT FALSE,
			invoice_number VARCHAR(128) DEFAULT NULL,
			finished BOOLEAN NOT NULL DEFAULT FALSE,
			PRIMARY KEY  (id),
			KEY principal_id (principal_id)
		' );

		// Roles
		$roles = $this->get_roles();

		$this->update_roles( $roles );

		// Parent
		parent::install();
	}

	//////////////////////////////////////////////////

	/**
	 * Get roles
	 *
	 * @return array
	 */
	public function get_roles() {
		// @see http://codex.wordpress.org/Function_Reference/register_post_type
		global $wp_post_types;

		// Default roles
		$roles = array(
			'super_administrator' => array(
				'manage_orbis'                      => true,
				'edit_orbis_project_administration' => true,
			),
			'administrator' => array(
				'manage_orbis'                      => true,
				'edit_orbis_project_administration' => true,
			),
			'editor' => array(

			),
			'employee' => array(

			),
		);

		// Roles post capabilities
		$roles_post_cap = array(
			'super_administrator' => array(
				'orbis_company' => orbis_post_type_capabilities( true, array(

				) ),
				'orbis_project' => orbis_post_type_capabilities( true, array(

				) ),
			),
			'administrator' => array(
				'orbis_company' => orbis_post_type_capabilities( true, array(
					'delete_post' => false,
				) ),
				'orbis_project' => orbis_post_type_capabilities( true, array(
					'delete_post' => false,
				) ),
			),
			'editor' => array(
				'orbis_company' => orbis_post_type_capabilities( false, array(
					'read_post' => true,
				) ),
				'orbis_project' => orbis_post_type_capabilities( false, array(
					'read_post' => true,
				) ),
			),
			'employee' => array(
				'orbis_company' => orbis_post_type_capabilities( false, array(
					'read_post' => true,
				) ),
				'orbis_project' => orbis_post_type_capabilities( false, array(
					'read_post' => true,
				) ),
			)
		);

		foreach ( $roles_post_cap as $role => $post_types ) {
			foreach ( $post_types as $post_type => $capabilities ) {
				orbis_translate_post_type_capabilities( $post_type, $capabilities, $roles[$role] );
			}
		}

		return $roles;
	}
}

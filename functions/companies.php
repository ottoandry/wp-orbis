<?php

/**
 * Add person meta boxes
 */
function orbis_company_add_meta_boxes() {
    add_meta_box( 
        'orbis_company',
        __( 'Company Details', 'orbis' ),
        'orbis_company_meta_box',
        'orbis_company' ,
        'side' ,
        'high'
    );
}

add_action( 'add_meta_boxes', 'orbis_company_add_meta_boxes' );

/**
 * Peron details meta box
 * 
 * @param array $post
 */
function orbis_company_meta_box( $post ) {
	include dirname( Orbis::$file ) . '/admin/meta-box-company-details.php';
}

/**
 * Save person details
 */
function orbis_save_company( $post_id, $post ) {
	// Doing autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) { 
		return;
	}

	// Verify nonce
	$nonce = filter_input( INPUT_POST, 'orbis_company_details_meta_box_nonce', FILTER_SANITIZE_STRING );
	if( ! wp_verify_nonce( $nonce, 'orbis_save_company_details' ) ) {
		return;
	}

	// Check permissions
	if ( ! ( $post->post_type == 'orbis_company' && current_user_can( 'edit_post', $post_id ) ) ) {
		return;
	}

	// OK
	$definition = array(
		'_orbis_company_kvk_number' => FILTER_SANITIZE_STRING
	);
	
	$data = filter_input_array(INPUT_POST, $definition);

	foreach ( $data as $key => $value ) {		
		if ( empty( $value ) ) {
			delete_post_meta( $post_id, $key);
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

add_action( 'save_post', 'orbis_save_company', 10, 2 );

/**
 * Sync company with Orbis tables
 */
function orbis_save_company_sync( $post_id, $post ) {
	// Doing autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) { 
		return;
	}

	// Check post type
	if ( ! ( $post->post_type == 'orbis_company' ) ) {
		return;
	}

	// Not revision
	if ( ! wp_is_post_revision( $post_id ) ) {
		global $wpdb;

		// Orbis company ID
		$orbis_id = get_post_meta( $post_id, '_orbis_company_id', true );
	
		if ( empty( $orbis_id ) ) {
			$result = $wpdb->insert( 
				'orbis_companies' , 
				array(
					'name' => $post->post_title , 
					'post_id' => $post_id 
				) , 
				array(
					'%s' , 
					'%d' 
				)
			);
	
			if ( $result !== false ) {
				$orbis_id = $wpdb->insert_id;
	
				update_post_meta( $post_id, '_orbis_company_id', $orbis_id );
			}
		} else {
			$result = $wpdb->update(
				'orbis_companies' , 
				array( 'name' => $post->post_title ) , 
				array( 'id' => $orbis_id ) , 
				array( '%s' ) , 
				array( '%d' ) 
			);
		}
	}
}

add_action( 'save_post', 'orbis_save_company_sync', 10, 2 );

/**
 * Keychain edit columns
 */
function orbis_company_edit_columns($columns) {
	return array(
        'cb'                       => '<input type="checkbox" />' , 
        'title'                    => __('Title', 'orbis') , 
		'orbis_company_id'         => __('Orbis ID', 'orbis') , 
		'orbis_company_kvk_number' => __('KvK Number', 'orbis') , 
		'author'                   => __('Author', 'orbis') , 
		'comments'                 => __('Comments', 'orbis') ,  
        'date'                     => __('Date', 'orbis') , 
	);
}

add_filter('manage_edit-orbis_company_columns' , 'orbis_company_edit_columns');

/**
 * Keychain column
 * 
 * @param string $column
 */
function orbis_company_column( $column, $post_id ) {
	switch ( $column ) {
		case 'orbis_company_id':
			$id = get_post_meta( $post_id, '_orbis_company_id', true );

			if ( ! empty( $id ) ) {
				$url = sprintf( 'http://orbis.pronamic.nl/bedrijven/details/%s/', $id );

				printf( '<a href="%s" target="_blank">%s</a>', $url, $id );
			}

			break;
		case 'orbis_company_kvk_number':
			$kvk_number = get_post_meta( $post_id, '_orbis_company_kvk_number', true );

			if ( ! empty( $kvk_number ) ) {
				$url = sprintf( 'http://www.openkvk.nl/%s', $kvk_number );

				printf( '<a href="%s" target="_blank">%s</a>', $url, $kvk_number );
			}

			break;
	}
}

add_action( 'manage_posts_custom_column' , 'orbis_company_column', 10, 2 );

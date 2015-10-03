<?php
/**
 * WP-Glossary Post Types
 */
class WPG_Taxonomies Extends WPG{
    public static $options;
    
 	public function __construct() {
        self::$options = get_option( 'wp_glossary' );
        self::$options["termtype"] = is_string(self::$options["termtype"]) ? self::$options["termtype"] : "glossary";
		add_action( 'init', array(&$this, 'register_taxonomies'), 0 );
	}

	public function register_taxonomies(){
		$labels = array(
			'name'              => __( 'Glossary Groups',         'wp-glossary' ),
			'singular_name'     => __( 'Glossary Group',          'wp-glossary' ),
			'search_items'      => __( 'Search Glossary Groups',  'wp-glossary' ),
			'all_items'         => __( 'All Glossary Groups',     'wp-glossary' ),
			'parent_item'       => __( 'Parent Glossary Group',   'wp-glossary' ),
			'parent_item_colon' => __( 'Parent Glossary Group:',  'wp-glossary' ),
			'edit_item'         => __( 'Edit Glossary Group',     'wp-glossary' ),
			'update_item'       => __( 'Update Glossary Group',   'wp-glossary' ),
			'add_new_item'      => __( 'Add New Glossary Group',  'wp-glossary' ),
			'new_item_name'     => __( 'New Glossary Group Name', 'wp-glossary' ),
		);
	
		register_taxonomy( 'wpglossarygroup', array( self::$options["termtype"] ), array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'query_var'         => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => __('glossary/group', 'wp-glossary') ),
		) );
	} // register_taxonomies
} // WPG_Taxonomies

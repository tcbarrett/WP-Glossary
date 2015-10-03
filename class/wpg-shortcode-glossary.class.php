<?php
class WPG_Shortcodes Extends WPG{
    public static $options;
    
	public function __construct() {
        self::$options = get_option( 'wp_glossary' );
        self::$options["termtype"] = is_string(self::$options["termtype"]) ? self::$options["termtype"] : "glossary";
		// Shortcode
		add_shortcode( "glossary", array(&$this, "glossary") );

		// Help functions..
		add_action( 'save_post',  array(&$this, 'save_post_check_for_glossary_usage'), 10, 2 );
		add_action( 'get_header', array(&$this, 'glossary_usage_reset_for_post') );
		add_action( 'wp_footer',  array(&$this, 'glossary_remove_update_marker') );
	}

	/** 
	 * If post has glossary shortcode in it when it is saved, mark the post as needing be updated
	 */
	public function save_post_check_for_glossary_usage( $post_id, $post ){
		$glossary_options = get_option( 'wp_glossary' );
		$termusage        = isset($glossary_options['termusage'] )  ? $glossary_options['termusage']   : 'on';

		if( $termusage != 'on' )
			return $post_id;

		if( !wp_is_post_revision($post_id)  ):
			if( strpos($post->post_content,'[glossary ') !== false || strpos($post->post_content,'[glossary]') !== false ):
				update_post_meta( $post_id, 'wpg_update_term_usage', current_time('mysql') );
			else :
				if(get_post_meta( $post_id, 'wpg_has_terms', $single=true) ):
					// Also posts that used to have terms should be updated.
					delete_post_meta( $post_id, 'wpg_has_terms' );
					update_post_meta( $post_id, 'wpg_update_term_usage', current_time('mysql') );
				endif;
			endif;
		endif;
	}

	/** 
	 * If current post (or page or whatever) has been marked as needing updating,
	 *  then delete all the meta entries for this post.
	 * These are stored on the glossary term meta
	 */
	public function glossary_usage_reset_for_post(){
		global $post;
		if( is_singular() && get_post_meta( $post->ID, 'wpg_update_term_usage') ):
			// Find all glossary terms that have this post noted.
			$args = array(
				'post_type'   => self::$options["termtype"],
				'numberposts' => -1,
				'post_status' => 'publish',
				'meta_query'  => array( array(
					'key'   => 'wpg_term_used',
					'value' => $post->ID,
					'type'  => 'DECIMAL'
				) )
			);
			$terms = get_posts( $args );
			foreach( $terms as $term ):
				// Delete the meta entry
				delete_post_meta( $term->ID, 'wpg_term_used', $post->ID );
			endforeach;
		endif;
	}

	/** */
	public function glossary_remove_update_marker(){
		global $post;
		if( is_singular() && get_post_meta( $post->ID, 'wpg_update_term_usage') ):
			delete_post_meta( $post->ID, 'wpg_update_term_usage' );
		endif;
	}

	/** */
	public function glossary( $atts, $content='' ){
		global $wpdb, $tcb_wpg_scripts, $wpg_glossary_count, $post, $wpg_doing_shortcode;
/*var_dump($wpdb->posts);
        die();*/
		$wpg_glossary_count++;

		// Get WP Glossary options
		$glossary_options = get_option( 'wp_glossary', array() );

		// JS data to pass through to jQuery libraries
		$jsdata = array();

		// Let shortcode attributes override general settings
		foreach( $glossary_options as $k => $v ):
			if( isset($atts[$k]) ):
				$jsdata[] = 'data-' . $k . '="' . trim( esc_attr($atts[$k]) ) . '"';
				$glossary_options[$k] = trim( $atts[$k] );
			endif;
		endforeach;
		$tooltip_option   = isset($glossary_options['tooltips'])    ? $glossary_options['tooltips']    : 'excerpt';
		$qtipstyle        = isset($glossary_options['qtipstyle'])   ? $glossary_options['qtipstyle']   : 'cream';
		$linkopt          = isset($glossary_options['termlinkopt']) ? $glossary_options['termlinkopt'] : 'standard';
		$termusage        = isset($glossary_options['termusage'] )  ? $glossary_options['termusage']   : 'on';

		extract( shortcode_atts( array(
			'id'   => 0,
			'slug' => '',
			'text' => '',
		), $atts) );

		// Set text to default to content. This allows syntax like: [glossary]Cheddar[/glossary]
		if( empty($text) ) $text = $content;

		$glossary = false;
	
		// Find the term in the database
		if( !empty($id) ):
			$glossary = get_post( $id );
		else :
			if( empty($slug) ):
				if( empty($text) ):
					// No id, slug or text available to identify a glossary term, so return the original content.
					return $content;
				endif;
				$slug = sanitize_title( $text );
			endif;
			$slug      = strtolower($slug);
        $sqlstring = "SELECT ID FROM {$wpdb->posts} WHERE post_name='%s' AND post_type='glossary' AND post_status='publish' LIMIT 1";
			$id        = $wpdb->get_var( $wpdb->prepare($sqlstring, $slug) );
			if( $id ):
				$glossary = get_post( $id );
			endif;
		endif;
		if( empty($glossary) ) return $text; // No glossary term found. Return the original text.

		// Term Usage
		if( $termusage && $termusage == 'on' && !$wpg_doing_shortcode ):
			if( get_post_meta( $post->ID, 'wpg_update_term_usage') ):
				if( !in_array($post->ID, get_post_meta($glossary->ID, 'wpg_term_used')) ):
					// Note this post against the glossary
					add_post_meta( $glossary->ID, 'wpg_term_used', $post->ID );
					// Note this post/page has glossary terms
					update_post_meta( $post->ID, 'wpg_has_terms', current_time('mysql') );
				endif;
 	   endif;
		endif;
	
		// Get the term title, and use as default text to use for the shortcode
		$title = get_the_title( $glossary->ID );
		if( empty($text) ) 
			$text = $title; 

		$link = $text; // Set to just plain text (used if 'none' linkopt set in settings)
		if( $linkopt != 'none' ):
			$href   = apply_filters( 'wpg_term_link', get_post_permalink($glossary->ID) );
			$target = ($linkopt == 'blank') ? 'target="_blank"'  : '';
			$link   = '<a href="' . $href . '" ' . $target . ' title="' . esc_attr($title) . '">' . $text . '</a>';
		endif;

		$span = '<span class="wp-glossary">' . $link . '</span>'; // Trivial default when tooltips switched off.
		if( $tooltip_option != 'off' ):
			// Global variable that tells WP to print related js files.
			$tcb_wpg_scripts = true;

			// qtip jquery data
			$jsdata[] = 'data-termid="' . $glossary->ID . '"';
			$jsdata[] = 'data-content="' . $tooltip_option . '"';
			$jsdata[] = 'data-qtipstyle="' . $qtipstyle . '"';

			// Span that qtip finds
			$span = '<span class="wp-glossary wpg-tooltip" '.implode(' ',$jsdata).'>' . $link . '</span>';
		endif;

		return $span;
	}
}

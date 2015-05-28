<?php
/**
 * Media template functions. These functions are meant to handle various features needed in theme templates 
 * for media and attachments.
 *
 * @package    HybridCore
 * @subpackage Includes
 * @author     Justin Tadlock <justin@justintadlock.com>
 * @copyright  Copyright (c) 2008 - 2015, Justin Tadlock
 * @link       http://themehybrid.com/hybrid-core
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Prints media meta directly to the screen.  The `$property` parameter can be any of the public 
 * properties in the `Hybrid_Media_Meta` object.
 *
 * @see    Hybrid_Media_Meta
 * @since  3.0.0
 * @access public
 * @param  string  $property
 * @param  array   $args
 * @return void
 */
function hybrid_media_meta( $property, $args = array() ) {
	echo hybrid_get_media_meta( $property, $args );
}

/**
 * Returns media meta from a media meta object.  The `$property` parameter can be any of the public 
 * properties in the `Hybrid_Media_Meta` object.
 *
 * @see    Hybrid_Media_Meta
 * @since  3.0.0
 * @access public
 * @param  string  $property
 * @param  array   $args
 * @return string
 */
function hybrid_get_media_meta( $property, $args = array() ) {

	$defaults = array(
		'post_id' => get_the_ID(),
		'text'    => '%s',
		'before'  => '',
		'after'   => '',
		'wrap'    => '<span %s>%s</span>'
	);

	$args = wp_parse_args( $args, $defaults );

	// Get the media metadata.
	$meta = hybrid_media_meta_factory()->get_media_meta( $args['post_id'] )->get( $property );

	// Return the formatted meta or an empty string.
	return $meta ? $args['before'] . sprintf( $args['wrap'], 'class="data"', sprintf( $args['text'], $meta ) ) . $args['after'] : '';
}

/**
 * Returns an instance of the `Hybrid_Media_Meta_Factory` singleton.  While theme authors can access 
 * this function directly, it's best to use the `hybrid_media_meta()` and `hybrid_get_media_meta()` 
 * functions for printing/getting media meta object data.
 *
 * @see    Hybrid_Media_Meta_Factory
 * @since  3.0.0
 * @access public
 * @return object
 */
function hybrid_media_meta_factory() {
	return Hybrid_Media_Meta_Factory::get_instance();
}

/**
 * Returns the main attachment mime type.  For example, `image` when the file has an `image / jpeg` 
 * mime type.
 *
 * @since  3.0.0
 * @access public
 * @param  int    $post_id
 * @return string
 */
function hybrid_get_attachment_type( $post_id = 0 ) {

	$post_id   = empty( $post_id ) ? get_the_ID() : $post_id;
	$mime_type = get_post_mime_type( $post_id );

	list( $type, $subtype ) = false !== strpos( $mime_type, '/' ) ? explode( '/', $mime_type ) : array( $mime_type, '' );

	return $type;
}

/**
 * Returns the attachment mime subtype.  For example, `jpeg` when the file has an `image / jpeg` 
 * mime type.
 *
 * @since  3.0.0
 * @access public
 * @param  int    $post_id
 * @return string
 */
function hybrid_get_attachment_subtype( $post_id = 0 ) {

	$post_id   = empty( $post_id ) ? get_the_ID() : $post_id;
	$mime_type = get_post_mime_type( $post_id );

	list( $type, $subtype ) = false !== strpos( $mime_type, '/' ) ? explode( '/', $mime_type ) : array( $mime_type, '' );

	return $subtype;
}

/**
 * Checks if the current post has a mime type of 'audio'.
 *
 * @since  1.6.0
 * @access public
 * @param  int    $post_id
 * @return bool
 */
function hybrid_attachment_is_audio( $post_id = 0 ) {
	return 'audio' === hybrid_get_attachment_type( $post_id ) ? true : false;
}

/**
 * Checks if the current post has a mime type of 'video'.
 *
 * @since  1.6.0
 * @access public
 * @param  int    $post_id
 * @return bool
 */
function hybrid_attachment_is_video( $post_id = 0 ) {
	return 'video' === hybrid_get_attachment_type( $post_id ) ? true : false;
}

/**
 * Returns a set of image attachment links based on size.
 *
 * @since  2.0.0
 * @access public
 * @return string
 */
function hybrid_get_image_size_links() {

	// If not viewing an image attachment page, return.
	if ( !wp_attachment_is_image( get_the_ID() ) )
		return;

	// Set up an empty array for the links.
	$links = array();

	// Get the intermediate image sizes and add the full size to the array.
	$sizes   = get_intermediate_image_sizes();
	$sizes[] = 'full';

	// Loop through each of the image sizes.
	foreach ( $sizes as $size ) {

		// Get the image source, width, height, and whether it's intermediate.
		$image = wp_get_attachment_image_src( get_the_ID(), $size );

		// Add the link to the array if there's an image and if $is_intermediate (4th array value) is true or full size.
		if ( !empty( $image ) && ( true === $image[3] || 'full' == $size ) ) {

			// Translators: Media dimensions - 1 is width and 2 is height.
			$label = sprintf( esc_html__( '%1$s &#215; %2$s', 'hybrid-core' ), number_format_i18n( absint( $image[1] ) ), number_format_i18n( absint( $image[2] ) ) );

			$links[] = sprintf( '<a class="image-size-link">%s</a>', $label );
		}
	}

	// Join the links in a string and return.
	return join( ' <span class="sep">/</span> ', $links );
}

/**
 * Gets the "transcript" for an audio attachment.  This is typically saved as "unsynchronised_lyric", which is 
 * the ID3 tag sanitized by WordPress.
 *
 * @since  2.0.0
 * @access public
 * @param  int     $post_id
 * @return string
 */
function hybrid_get_audio_transcript( $post_id = 0 ) {
	return hybrid_get_media_meta( 'lyrics', array( 'wrap' => '', 'post_id' => $post_id ? $post_id : get_the_ID() ) );
}

/**
 * Loads the correct function for handling attachments.  Checks the attachment mime type to call 
 * correct function. Image attachments are not loaded with this function.  The functionality for them 
 * should be handled by the theme's attachment or image attachment file.
 *
 * Ideally, all attachments would be appropriately handled within their templates. However, this could 
 * lead to messy template files.
 *
 * @since  0.5.0
 * @access public
 * @return void
 */
function hybrid_attachment() {

	$file       = wp_get_attachment_url();
	$mime       = get_post_mime_type();
	$attachment = '';

	$mime_type = false !== strpos( $mime, '/' ) ? explode( '/', $mime ) : array( $mime, '' );

	// Loop through each mime type. If a function exists for it, call it. Allow users to filter the display.
	foreach ( $mime_type as $type ) {
		if ( function_exists( "hybrid_{$type}_attachment" ) )
			$attachment = call_user_func( "hybrid_{$type}_attachment", $mime, $file );

		$attachment = apply_filters( "hybrid_{$type}_attachment", $attachment );
	}

	echo apply_filters( 'hybrid_attachment', $attachment );
}

/**
 * Handles application attachments on their attachment pages.  Uses the <object> tag to embed media 
 * on those pages.
 *
 * @since  0.3.0
 * @access public
 * @param  string $mime attachment mime type
 * @param  string $file attachment file URL
 * @return string
 */
function hybrid_application_attachment( $mime = '', $file = '' ) {
	$embed_defaults = wp_embed_defaults();
	$application  = '<object class="text" type="' . esc_attr( $mime ) . '" data="' . esc_url( $file ) . '" width="' . esc_attr( $embed_defaults['width'] ) . '" height="' . esc_attr( $embed_defaults['height'] ) . '">';
	$application .= '<param name="src" value="' . esc_url( $file ) . '" />';
	$application .= '</object>';

	return $application;
}

/**
 * Handles text attachments on their attachment pages.  Uses the <object> element to embed media 
 * in the pages.
 *
 * @since  0.3.0
 * @access public
 * @param  string $mime attachment mime type
 * @param  string $file attachment file URL
 * @return string
 */
function hybrid_text_attachment( $mime = '', $file = '' ) {
	$embed_defaults = wp_embed_defaults();
	$text  = '<object class="text" type="' . esc_attr( $mime ) . '" data="' . esc_url( $file ) . '" width="' . esc_attr( $embed_defaults['width'] ) . '" height="' . esc_attr( $embed_defaults['height'] ) . '">';
	$text .= '<param name="src" value="' . esc_url( $file ) . '" />';
	$text .= '</object>';

	return $text;
}

/**
 * Handles the output of the media for audio attachment posts. This should be used within The Loop.
 *
 * @since  0.2.2
 * @access public
 * @return string
 */
function hybrid_audio_attachment() {
	return hybrid_media_grabber( array( 'type' => 'audio' ) );
}

/**
 * Handles the output of the media for video attachment posts. This should be used within The Loop.
 *
 * @since  0.2.2
 * @access public
 * @return string
 */
function hybrid_video_attachment() {
	return hybrid_media_grabber( array( 'type' => 'video' ) );
}

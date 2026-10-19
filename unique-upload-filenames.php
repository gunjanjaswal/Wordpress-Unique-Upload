<?php
/**
 * Plugin Name:       Unique Upload Filenames for g33ki Cloud Storage
 * Plugin URI:        https://github.com/gunjanjaswal/Wordpress-Unique-Upload
 * Description:       Companion add-on for g33ki Cloud Storage for Media Library. Gives every uploaded file a globally unique name so two uploads that share a filename can never overwrite each other on your cloud bucket. g33ki removes the local copy after offloading, which leaves WordPress's built-in duplicate check blind; this closes that gap. Also works alongside any other offload or CDN plugin.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.0
 * Author:            Gunjan Jaswal
 * Author URI:        https://www.gunjanjaswal.me
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unique-upload-filenames
 *
 * Built as an add-on for g33ki Cloud Storage for Media Library:
 * https://wordpress.org/plugins/g33ki-cloud-storage-for-media-library/
 *
 * Works as a normal plugin (activate it) or as a must-use plugin (drop it in
 * wp-content/mu-plugins/). Zero configuration. Existing files are never touched.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wuuf_unique_filename' ) ) {

	/**
	 * Rewrite an incoming upload's filename so it is globally unique.
	 *
	 * WordPress normally keeps names unique with wp_unique_filename(), but that
	 * only scans the LOCAL uploads folder. g33ki Cloud Storage (and other offload
	 * or CDN plugins) can delete the local copy after pushing it to remote
	 * storage, which leaves that check blind, so a second "photo.jpg" keeps its
	 * name and overwrites the first one on the bucket. This appends a short date
	 * + random token to the base name before the file is ever saved, which
	 * removes the collision entirely.
	 *
	 * Hooked on both the browser-upload and the sideload prefilters, so media
	 * library uploads, the block/classic editor, and programmatic imports
	 * (wp media import, media_sideload_image) are all covered.
	 *
	 * @param array $file An element of the $_FILES-style upload array (has 'name').
	 * @return array The same array with a unique 'name'.
	 */
	function wuuf_unique_filename( $file ) {

		// Let site owners switch the behaviour off, or skip specific files.
		if ( ! apply_filters( 'wuuf_enabled', true, $file ) ) {
			return $file;
		}

		if ( empty( $file['name'] ) ) {
			return $file;
		}

		$name = $file['name'];
		$dot  = strrpos( $name, '.' );

		if ( false === $dot || 0 === $dot ) {
			$base = $name;
			$ext  = '';
		} else {
			$base = substr( $name, 0, $dot );
			$ext  = substr( $name, $dot ); // Keeps the leading dot, e.g. ".png".
		}

		$base = sanitize_file_name( $base );
		if ( '' === $base || '-' === $base ) {
			$base = 'file';
		}

		// Short, essentially collision-proof token: YYMMDD + 6 random hex chars.
		try {
			$rand = bin2hex( random_bytes( 3 ) );
		} catch ( Exception $e ) {
			$rand = substr( md5( uniqid( '', true ) ), 0, 6 );
		}

		/**
		 * Filter the uniqueness token that gets appended to the base name.
		 *
		 * @param string $token Default "YYMMDD-xxxxxx".
		 * @param array  $file  The upload array.
		 */
		$token = apply_filters( 'wuuf_token', gmdate( 'ymd' ) . '-' . $rand, $file );

		// Do not stack a second token if the name already carries one of ours.
		if ( ! preg_match( '/-\d{6}-[0-9a-f]{6}$/', $base ) ) {
			$base .= '-' . $token;
		}

		$new_name = $base . $ext;

		/**
		 * Filter the final, rewritten filename before it is applied.
		 *
		 * @param string $new_name The new unique filename.
		 * @param string $name     The original filename.
		 * @param array  $file     The upload array.
		 */
		$file['name'] = apply_filters( 'wuuf_filename', $new_name, $name, $file );

		return $file;
	}

	add_filter( 'wp_handle_upload_prefilter', 'wuuf_unique_filename', 5 );
	add_filter( 'wp_handle_sideload_prefilter', 'wuuf_unique_filename', 5 );
}

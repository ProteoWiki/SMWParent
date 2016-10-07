<?php
if (!defined('MEDIAWIKI')) { die(-1); } 
 
# Class for handling SMW queries directly from the extension

class SMWParentParser {


	public static function parseParent( $parser, $frame, $args ) {
		
		global $wgSMWParentdefault;

		// Whether returning a link or not
		$link = 0;

		$parser->disableCache();

		// Let's get first Fulltext of page
		$child_text =  $parser->getTitle()->getFullText();
		if ( isset( $args[0] ) ) {
			$child = trim( $frame->expand( $args[0] ) );
			if ( $child != '' ) {
				$child_text = $child;   
			}
		}

		$parent_type = $wgSMWParentdefault;

		if ( isset( $args[1] ) ) {
			$parent_type = trim( $frame->expand( $args[1] ) );
		}

		if ( isset( $args[2] ) ) {
			$extra = trim( $frame->expand( $args[2] ) );
			if ( $extra == 'link' ) {
				$link = 1;
			}
		}
		
		$list = SMWParent::executeGetParent( $input );
		
		// TODO Process below
		
	}
	
	public static function parseChildren( $parser, $frame, $args ) {
		
		global $wgSMWChildrendefault;

		// Whether returning a link or not
		$link = 0;

		$parser->disableCache();

		// Let's get first Fulltext of page
		$parent_text =  $parser->getTitle()->getFullText();
		if ( isset( $args[0] ) ) {

			$parent = trim( $frame->expand( $args[0] ) );
			if ( $parent != '' ) {
				$parent_text = $parent;
			}
		}

		$children_type = $wgSMWChildrendefault;

		if ( isset( $args[1] ) ) {
			$children_type = trim( $frame->expand( $args[1] ) );
		}

		if ( isset( $args[2] ) ) {

			$extra = trim( $frame->expand( $args[2] ) );
			if ( $extra == 'link' ) {
				$link = 1;
			}
		}
		
		$list = SMWParent::executeGetChildren( $input );
		
		// TODO Process below

	}
	
}
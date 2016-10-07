<?php
if (!defined('MEDIAWIKI')) { die(-1); } 
 
# Class for handling SMW queries directly from the extension

class SMWParentParser {


	public static function parseParent( $parser, $frame, $args ) {
		
		global $wgSMWParentdefault;
		global $wgSMWParentTypeProperty;
		global $wgSMWParentProps;
		global $wgSMWParentPrintProps;

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

		$input = array();
		$input["child_text"] = $child_text;
		$input["parent_type"] = $parent_type;
		$input["link_properties"] = $wgSMWParentProps;
		$input["type_properties"] = $wgSMWParentTypeProperty;
		$input["level"] = 1;
		$input["print_properties"] = $wgSMWParentPrintProps;

		$list = SMWParent::executeGetParent( $input );
		
		if ( $link > 0 ) {

			$newlist = array();

			foreach ( $list as $entry ) {
				array_push( $newlist, self::makeLink( $entry ) );
			}

			$list = $newlist;
		}
		
		// TODO: Further processing later
		return join(",", $list );

		
	}
	
	public static function parseChildren( $parser, $frame, $args ) {
		
		global $wgSMWChildrendefault;
		global $wgSMWParentTypeProperty;
		global $wgSMWParentProps;
		global $wgSMWParentPrintProps;

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

		$input = array();
		$input["parent_text"] = $parent_text;
		$input["children_type"] = $children_type;
		$input["link_properties"] = $wgSMWParentProps;
		$input["type_properties"] = $wgSMWParentTypeProperty;
		$input["level"] = 1;
		$input["print_properties"] = $wgSMWParentPrintProps;

		$list = SMWParent::executeGetChildren( $input );

		if ( $link > 0 ) {

			$newlist = array();

			foreach ( $list as $entry ) {
				array_push( $newlist, self::makeLink( $entry ) );
			}

			$list = $newlist;
		}
		
		// TODO: Further processing later
		return join(",", $list );

	}
	

	/**
	* This function checks the type of an entry
	* @param $fulltitle String : text title of a page
	* @return linked page title
	*/

	private static function makeLink ( $fulltitle ) {

		$link = $fulltitle;

		if ( is_object(Title::newFromText($fulltitle)) ) {
			$title = Title::newFromText($fulltitle)->getText();
	
			$link = "[[$fulltitle|$title]]";
		}

		return $link;
	}

}
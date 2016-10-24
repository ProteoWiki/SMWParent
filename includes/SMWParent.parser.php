<?php
if (!defined('MEDIAWIKI')) { die(-1); } 
 
# Class for handling SMW queries directly from the extension

class SMWParentParser {


	public static function parseParent( $parser, $frame, $args ) {

		$input = self::parseElement( "parent", $parser, $frame, $args );
		
		$input = self::retrieveTypes( $input );
		
		$listStruct = SMWParent::executeGetParent( $input );

		$leaves = array();
		$leaves = SMWParent::getLeavesTree( $listStruct, $leaves );

		// TODO: Depending on args, also printouts

		$list = self::getArrayKeys( $leaves );

		// link
		if ( array_key_exists( "link", $input ) ) {

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

		$input = self::parseElement( "children", $parser, $frame, $args );
		
		$input = self::retrieveTypes( $input );

		$listStruct = SMWParent::executeGetChildren( $input );

		$leaves = array();
		$leaves = SMWParent::getLeavesTree( $listStruct, $leaves );

		$list = self::getArrayKeys( $leaves );

		// TODO: Depending on args, also printouts

		// link
		if ( array_key_exists( "link", $input ) ) {

			$newlist = array();

			foreach ( $list as $entry ) {
				array_push( $newlist, self::makeLink( $entry ) );
			}

			$list = $newlist;
		}
		
		// TODO: Further processing later
		return join(",", $list );
	}

	public static function parseTree( $parser, $frame, $args ) {

		$input = self::parseElement( "tree", $parser, $frame, $args );

		$input = self::retrieveTypes( $input );
		
		$listStruct = SMWParent::executeGetTree( $input );

		// Think how to represent this
		return "";
	}

	public static function parseElement( $type="parent", $parser, $frame, $args ) {

		global $wgSMWParentdefault;
		global $wgSMWParentTypeProperty;
		global $wgSMWParentProps;
		global $wgSMWParentPrintProps;

		// Whether returning a link or not
		$link = 0;

		$parser->disableCache();

		// Let's get first Fulltext of page
		$target_text =  $parser->getTitle()->getFullText();
		if ( isset( $args[0] ) ) {
			$target = trim( $frame->expand( $args[0] ) );
			if ( $target != '' ) {
				$target_text = $target;   
			}
		}

		$parent_type = $wgSMWParentdefault;

		if ( isset( $args[1] ) ) {
			$source_type = explode( "," , trim( $frame->expand( $args[1] ) ) );
		}

		$input = array();

		if ( $type === "parent" ) {
			$input["child_text"] = $target_text;
			$input["parent_type"] = $source_type[0];
		}
		if ( $type === "children" ) {
			$input["parent_text"] = $target_text;
			$input["children_type"] = $source_type[0];
		}
		if ( $type === "tree" ) {
			$input["child_text"] = $target_text;
			$input["parent_text"] = $target_text;
			$input["parent_type"] = $source_type[0];

			if ( count( $source_type ) > 1 ) {
				$input["children_type"] = $source_type[1];
			} else {
				$input["children_type"] = $source_type[0];
			}

		}

		$input["link_properties"] = $wgSMWParentProps;
		$input["type_properties"] = $wgSMWParentTypeProperty;
		$input["level"] = 1;
		$input["print_properties"] = $wgSMWParentPrintProps;


		if ( count( $args ) > 2 ) {

			for ( $i=2; $i < count( $args ); $i++ ) {
				
				$params = self::processArg( $args[$i], $frame );

				/** Backcompatibility **/
				if ( count( $params ) > 0 ) {

					if ( count( $params ) > 1 ) {

						if ( strpos( $params[0], "_properties" ) !== false ) {
							$input[$params[0]] = self::processIntoArray( $params[1] ); 
						} else {
							$input[$params[0]] = $params[1];
						}

					} else {
						$input[$params[0]] = 1;
					}
				}

			}
		}

		return( $input );
	}

	
	/**
	* Retrieving types of properties
	* @param $array Array of inputs
    * @return Modified input
	*/

	private static function retrieveTypes( $input ) {

		$input["print_properties_types"] = array();
		
		if ( array_key_exists( "print_properties", $input ) ) {
			
			if ( array_count( $input["print_properties"] ) > 0 ) {
				$input["print_properties_types"] = SMWParent::retrievePropertyTypes( $input["print_properties"] );
			}
			
		}

		return $input;
	}

	/**
	* Converting structures
	* @param $array Array of structs with only one key
    * @return Actual keys
	*/

	private static function getArrayKeys( $array ) {

		$keys = array();

		foreach ( $array as $element ) {
			foreach ( $element as $key => $value ) {
				array_push( $keys, $key );
			}
		}
		
		return $keys;
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

	private static function processArg( $param, $frame ) {

		$param = trim( $frame->expand( $param ) );

		$keyval = explode( "=", $param, 2 );

		$keyvaltrim = array_map('trim', $keyval);

		return $keyvaltrim;
	}

	private static function processIntoArray( $string ) {
		
		$array = explode( ",", $string );
		$arrayclean = array_map('trim', $array);

		return $arrayclean;
	}


}
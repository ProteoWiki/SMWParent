<?php
if (!defined('MEDIAWIKI')) { die(-1); } 
 
# Class for handling SMW queries directly from the extension

class SMWParentParser {


	public static function parseParent( $parser, $frame, $args ) {

		$input = self::parseElement( "parent", $parser, $frame, $args );
		$list = SMWParent::executeGetParent( $input );

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
		$list = SMWParent::executeGetChildren( $input );

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
			$source_type = trim( $frame->expand( $args[1] ) );
		}

		$input = array();

		if ( $type === "parent" ) {
			$input["child_text"] = $target_text;
			$input["parent_type"] = $source_type;
		} else {
			$input["parent_text"] = $target_text;
			$input["child_type"] = $source_type;
		}

		$input["link_properties"] = $wgSMWParentProps;
		$input["type_properties"] = $wgSMWParentTypeProperty;
		$input["level"] = 1;
		$input["print_properties"] = $wgSMWParentPrintProps;


		if ( count( $args ) > 2 ) {

			/** Backcompatibility **/
			if ( isset( $args[2] ) ) {
				$extra = trim( $frame->expand( $args[2] ) );
				if ( $extra == 'link' ) {
					$input['link'] = 1;
				}
			}

			for ( $i=2; $i <= count( $args ); $i++ ) {
				
				$params = self::processArg( $args[$i], $frame );

				/** Backcompatibility **/
				if ( count( $params ) > 1 ) {
					if ( $params[0] == 'link' ) {
						$input['link'] = 1;
					}

					if ( strpos( $params[0], "_properties" ) !== false ) {
						$input[$params[0]] = self::processIntoArray( $params[1] ); 
					} else {
						$input[$params[0]] = $params[1];
					}

				}

			}
		}

		return( $input );
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
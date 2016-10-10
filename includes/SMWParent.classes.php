<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'Not a valid entry point';
	exit( 1 );
}

if ( !defined( 'SMW_VERSION' ) ) {
	echo 'This extension requires Semantic MediaWiki to be installed.';
	exit( 1 );
}


/**
 * This class handles the search for Parent.
 */
class SMWParent {

	private static $parent_round;
	private static $children_round;
	
	public static function executeGetParent( $input ) {

		self::$parent_round = 0;

		$parent_list = self::getElement( "parent", $input['child_text'], $input['parent_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $parent_list;

	}

	public static function executeGetChildren( $input ) {

		self::$children_round = 0;

		$children_list = self::getElement( "children", $input['parent_text'], $input['children_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $children_list;

	}

	private static function getElement( $type="parent", $target_text, $source_type, $link_properties, $type_properties, $level=1, $print_properties ) {

		global $wgSMWParentlimit;

		// After results query, we add parent round
		if (! isset(self::$parent_round) ) {
			self::$parent_round = 0;
		}
		self::$parent_round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$parent_round ) {
			return array();
		}

		// Query -> current page
		$targetlist = array();

		foreach ( $link_properties as $prop ) {

			if ( $type === "parent" ) {
				$printout_properties = $print_properties;
				array_unshift( $printout_properties, $prop );
				$results = self::getQueryResults( "[[$target_text]]", $printout_properties, false );
			} else {
				$results = self::getQueryResults( "[[$prop::$target_text]]", $print_properties, true );
			}

			// In theory, there is only one row
			while ( $row = $results->getNext() ) {

				if ( $type === "parent" ) {
					$targetCont = $row[1];
				} else {
					$targetCont = $row[0];
				}

				if ( !empty($targetCont) ) {

					// TODO: Process printouts below
					while ( $obj = $targetCont->getNextObject() ) {

						array_push( $targetlist, $obj->getWikiValue() );
					}

					// TODO: More printout processing...
				}

			}

		}

		// Final ones to retrieve
		$targetout = array();

		foreach ( $targetlist as $target ) {

			// Children round
			if ( ( is_numeric($source_type) && $source_type == $level ) || ( self::isEntryType( $target, $source_type, $type_properties ) ) ) {

				array_push( $targetout, $target );
		
			} else {

				// We increase level here
				$itera = $level + 1;
				$temparray = self::getElement( $type, $target, $source_type, $link_properties, $type_properties, $itera, $print_properties );
				
				foreach ($temparray as $temp) {
				
					if ($temp != '') {

						array_push( $targetout, $temp );
					}
				}
			}
		}

		return $targetout;

	}


	/**
	* This function checks the type of an entry
	* @param $entry String : entry type
	* @param $type String: the type checked
	* @param $type_properties Array: Properties that assign tyoe
	* @return boolean
	*/

	private static function isEntryType( $entry, $type, $type_properties ) {

		// Are we asking for a category
		if ( in_array( "Categories", $type_properties ) ) {

			if ( is_object(Title::newFromText($entry)) ) {
				$titleObj = Title::newFromText($entry);
				$wikiPage = WikiPage::factory( $titleObj );
				$categories_objects = $wikiPage->getCategories();

				while ( $category = $categories_objects->next() ) {
					if ( is_object( $category ) ) {
						$title = $category->getBaseText();
						if ( $title == $type ) {
							return true;
						}
					}
				}
			}

		}
	}


	/**
	* This function returns to results of a certain query
	* Thank you Yaron Koren for advices concerning this code
	* @param $query_string String : the query
	* @param $properties_to_display array(String): array of property names to display
	* @param $display_title Boolean : add the page title in the result
	* @return TODO
	*/
	static function getQueryResults( $query_string, $properties_to_display, $display_title ) {
	
		// We use the Semantic MediaWiki Processor
		// $smwgIP is defined by Semantic MediaWiki, and we don't allow
		// this file to be sourced unless Semantic MediaWiki is included.
		global $smwgIP;
		
		if ( file_exists( $smwgIP . "/includes/SMW_QueryProcessor.php") ) {
			include_once( $smwgIP . "/includes/SMW_QueryProcessor.php" );
		} else {
			include_once( $smwgIP . "/includes/query/SMW_QueryProcessor.php" );
		}

		$params = array();
		$inline = true;
		$printlabel = "";
		$printouts = array();

		// add the page name to the printouts
		if ( $display_title ) {
			$to_push = new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, $printlabel );
			array_push( $printouts, $to_push );
		}

		// Push the properties to display in the printout array.
		foreach ( $properties_to_display as $property ) {
			if ( class_exists( 'SMWPropertyValue' ) ) { // SMW 1.4
				$to_push = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, $printlabel, SMWPropertyValue::makeProperty( $property ) );
			} else {
				$to_push = new SMWPrintRequest( SMWPrintRequest::PRINT_PROP, $printlabel, Title::newFromText( $property, SMW_NS_PROPERTY ) );
			}
			array_push( $printouts, $to_push );
		}

		if ( version_compare( SMW_VERSION, '1.6.1', '>' ) ) {
			SMWQueryProcessor::addThisPrintout( $printouts, $params );
			$params = SMWQueryProcessor::getProcessedParams( $params, $printouts );
			$format = null;
		}
		else {
			$format = 'auto';
		}

		$query = SMWQueryProcessor::createQuery( $query_string, $params, $inline, $format, $printouts );
		$results = smwfGetStore()->getQueryResult( $query );

		return $results;
	}



}

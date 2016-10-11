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

	private static $round;
	
	public static function executeGetParent( $input ) {

		self::$round = 0;

		$parentList = self::getElement( "parent", $input['child_text'], $input['parent_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $parentList;

	}

	public static function executeGetChildren( $input ) {

		self::$round = 0;

		$childrenList = self::getElement( "children", $input['parent_text'], $input['children_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $childrenList;

	}

	private static function getElement( $type="parent", $targetText, $sourceType, $linkProperties, $typeProperties, $level=1, $printProperties ) {

		global $wgSMWParentlimit;

		// After results query, we add parent round
		if (! isset(self::$round) ) {
			self::$round = 0;
		}
		self::$round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$parentRound ) {
			return array();
		}

		// Query -> current page
		$targetList = array();


		foreach ( $linkProperties as $prop ) {

			$printoutProperties = $printProperties;

			if ( $type === "parent" ) {
				array_unshift( $printoutProperties, $prop );
				$results = self::getQueryResults( "[[$targetText]]", $printoutProperties, false );
			} else {
				$results = self::getQueryResults( "[[$prop::$targetText]]", $printoutProperties, true );
			}

			// In theory, there is only one row
			while ( $row = $results->getNext() ) {

				$start = 1; // Start point for counting printouts

				if ( $type === "parent" ) {
					$targetCont = $row[1];
					$start = 2;
				} else {
					$targetCont = $row[0];
				}

				$numColumns = count( $row );

				if ( !empty($targetCont) ) {

					$pageEntry = null;

					// We assume value is only a single page
					while ( $obj = $targetCont->getNextObject() ) {

						$pageEntry = $obj->getWikiValue();
					}

					$printKeys = array();
					for ( $v = $start; $v <= $numColumns; $v++ ) {
						$printKey = $printoutProperties[ $v ];

						$valueCont = $row[ $v ];
						if ( !empty($valueCont) ) {
							if ( $valueCont->getCount() > 1 ) {
								$list = array();
								while ( $obj = $valueCont->getNextObject() ) {
									array_push( $list, $obj->getWikiValue() );
								}
								$printKeys[$printkey] = $list;
							} else {
								while ( $obj = $valueCont->getNextObject() ) {
									$printKeys[$printkey] = $obj->getWikiValue();
								}
							}
						}
					}

					if ( $pageEntry ) {
						$targetList[ $pageEntry ] = $printKeys;
					}
				}
			}

		}

		// Final ones to retrieve
		$targetOut = array();

		foreach ( $targetList as $target => $content ) {

			// Children round
			if ( ( is_numeric($sourceType) && $sourceType == $level ) || ( self::isEntryType( $target, $sourceType, $typeProperties ) ) ) {

				array_push( $targetOut, $target );
		
			} else {

				// We increase level here
				$itera = $level + 1;
				$temparray = self::getElement( $type, $target, $sourceType, $linkProperties, $typeProperties, $itera, $printProperties );
				
				foreach ($temparray as $temp) {
				
					if ($temp != '') {

						array_push( $targetOut, $temp );
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
	* @param $typeProperties Array: Properties that assign tyoe
	* @return boolean
	*/

	private static function isEntryType( $entry, $type, $typeProperties ) {

		// Are we asking for a category
		if ( in_array( "Categories", $typeProperties ) ) {

			if ( is_object(Title::newFromText($entry)) ) {
				$titleObj = Title::newFromText($entry);
				$wikiPage = WikiPage::factory( $titleObj );
				$categoriesObjects = $wikiPage->getCategories();

				while ( $category = $categoriesObjects->next() ) {
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

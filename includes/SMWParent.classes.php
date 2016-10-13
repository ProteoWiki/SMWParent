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

		$out = array();
				
		$out[ $input['child_text'] ] = array(
			"type" => "start",
			"link" => self::getElementTree( "parent", $input['child_text'], $input['parent_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] )
		);
		
		return $out;

	}

	public static function executeGetChildren( $input ) {

		self::$round = 0;
		
		$out = array();
		
		$out[ $input['parent_text'] ] = array(
			"type" => "start",
			"link" => self::getElementTree( "children", $input['parent_text'], $input['children_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] )
		);
		
		return $out;

	}


	private static function getElementTree( $type="parent", $targetText, $sourceType, $linkProperties, $typeProperties, $level=1, $printProperties ) {

		global $wgSMWParentlimit;

		// After results query, we add parent round
		if (! isset(self::$round) ) {
			self::$round = 0;
		}
		self::$round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$round ) {
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

			if ( $results->getCount() > 0 ) {
				$targetList[ $prop ] = array();
			}

			// In theory, there is only one row
			while ( $row = $results->getNext() ) {

				$start = 2; // Start point for counting printouts
				$add = 0;

				if ( $type === "parent" ) {
					$targetCont = $row[1];
				} else {
					$targetCont = $row[0];
					$start = 1;
					$add = 1;
				}

				$numColumns = count( $row );

				if ( !empty($targetCont) ) {

					$pageEntry = null;

					// We assume value is only a single page
					while ( $obj = $targetCont->getNextObject() ) {

						$pageEntry = $obj->getWikiValue();
					}

					$printKeys = array();
					for ( $v = $start; $v < $numColumns; $v++ ) {

						if ( array_key_exists( $v - 1, $printoutProperties ) && array_key_exists( $v + $add, $row ) ) {

							$printKey = $printoutProperties[ $v - 1 ];
							$valueCont = $row[ $v + $add ];

							if ( $valueCont && !empty($valueCont) ) {
								if ( count( $valueCont ) > 1 ) {
									$list = array();
									while ( $obj = $valueCont->getNextObject() ) {
										// TODO: We might be interested in handling type here
										array_push( $list, $obj->getWikiValue() );
									}
									$printKeys[$printKey] = $list;
								} else {
									while ( $obj = $valueCont->getNextObject() ) {
										// TODO: We might be interested in handling type here
										$printKeys[$printKey] = $obj->getWikiValue();
									}
								}
							}

						}
					}

					if ( $pageEntry ) {
						$targetList[$prop][ $pageEntry ] = $printKeys;
					}
				}
			}

		}

		// Final ones to retrieve
		$targetOut = array();

		foreach ( $targetList as $prop => $matches ) {

			if ( count( $matches ) > 0 ) {
				$targetOut[ $prop ] = array();
			}

			foreach ( $matches as $target => $content ) {
	
				// Children round
				if ( ( is_numeric($sourceType) && $sourceType == $level ) || ( self::isEntryType( $target, $sourceType, $typeProperties ) ) ) {
	
					$struct = array();
					$struct[ $target ] = array();
					// For now, only printout in the last one
					$struct[ $target ]["type"] = "end";
					$struct[ $target ]["printouts"] = $content;
					array_push( $targetOut[ $prop ], $struct );
			
				} else {
	
					// We increase level here.
					$targetOut[ $prop ][ $target ]["type"] = "mid";
					$targetOut[ $prop ][ $target ]["printouts"] = $content;
	
					$itera = $level + 1;
					$temparray = self::getElementTree( $type, $target, $sourceType, $linkProperties, $typeProperties, $itera, $printProperties );
					

					foreach ( $temparray as $key => $temp ) {

						if ( ! array_key_exists( $key, $targetOut[ $prop ][ $target ]["link"] ) ) {
							$targetOut[ $prop ][ $target ]["link"][ $key ] = array();
						}

						array_push( $targetOut[ $prop ][ $target ]["link"][ $key ], $temp );

					}
				}
			}

		}

		// Returns an array of hashes
		return $targetOut;

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

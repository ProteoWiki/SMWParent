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

		$parent_list = self::getParent( $input['child_text'], $input['parent_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $parent_list;

	}

	public static function executeGetChildren( $input ) {

		self::$children_round = 0;

		$children_list = self::getChildren( $input['parent_text'], $input['children_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] );

		return $children_list;

	}

	private static function getParent( $child_text, $parent_type, $link_properties, $type_properties, $level=1, $print_properties ) {

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
		$results = self::getQueryResults( "[[$child_text]]", $link_properties, false );

		#Containers
		$processes = array();
		$samples = array();
		$requests = array();

		// In theory, there is only one row
		while ( $row = $results->getNext() ) {

			if ( isset( $row[1] ) && !empty( $row[1]) ) {
				$processCont = $row[1];

				while ( $obj = $processCont->getNextObject() ) {
					 array_push( $processes, $obj->getWikiValue() );
				}
			}

			if ( isset( $row[2] ) && !empty( $row[2]) ) {

				$sampleCont = $row[2];

				while ( $obj = $sampleCont->getNextObject() ) {

					array_push( $samples, $obj->getWikiValue() );
					}
				}

			if ( isset( $row[3] ) && !empty( $row[3]) ) {

					$requestCont = $row[3];

					while ( $obj = $requestCont->getNextObject() ) {

						array_push( $requests, $obj->getWikiValue() );
					}
				}
			}

			if ( count($requests) == 0 && count($samples) == 0 && count($processes) == 0 ) {

				// This page is not in the flow
				return "";

			} else {

				//If requests has content, that's it
				if ( count($requests) > 0 ) {

					$casesout = array();
					foreach ($requests as $temp) {
						if ($temp != '') {
							array_push($casesout, $temp);
						}
					}
			
					// We assume not only one
					return( join(",", $casesout) );
			
				} else {

					if ( count($samples) > 0 && count($processes) > 0 ) {
						// Problem here -> return blank for now
						return "";
						
					} else {
						// Direct ancestor is sample/process. We assume all of the same kind. We use case as generic
						
						$cases = array();
						if ( count($samples) > 0 ) { $cases = $samples; }
						else {  $cases = $processes; }

						$casesout = array();

						foreach ($cases as $case) {
				
							// We accept parent numbers
							if (  ( is_numeric($parent_type) && $parent_type == $level )  || ( self::isEntryType( $case, $parent_type, $type_properties )  ) ) {
								array_push($casesout, $case);
							} else {
								$itera = $level + 1;
								$temparray = self::getParent( $case, $parent_type, $itera );
								foreach ($temparray as $temp) {
									if ($temp != '') {
										array_push($casesout, $temp);
									}
								}
							}

						}

					return $casesout;
				}
			}
		}
		
		// Return blank if anything left
		
		return array();
	}


	// Default level=1, direct child
	private static function getChildren( $parent_text, $children_type, $link_properties, $type_properties, $level=1, $print_properties ) {
	
		global $wgSMWParentlimit;

		// After results query, we add parent round
		if (! isset(self::$children_round) ) {
			self::$children_round = 0;
		}
		self::$children_round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$children_round ) {
			return array();
		}

		// Query -> current page
		$childrenlist = array();

		foreach ( $link_properties as $prop ) {

			$results = self::getQueryResults( "[[$prop::$parent_text]]", $print_properties, true );

			// In theory, there is only one row
			while ( $row = $results->getNext() ) {

				$childrenCont = $row[0];
				if ( !empty($childrenCont) ) {

					while ( $obj = $childrenCont->getNextObject() ) {

						array_push( $childrenlist, $obj->getWikiValue() );
					}
				}

			}

		}

		// Final ones to retrieve
		$childrenout = array();

		foreach ( $childrenlist as $children ) {

			// Children round
			if ( ( is_numeric($children_type) && $children_type == $level ) || ( self::isEntryType( $children, $children_type, $type_properties ) ) ) {

				array_push($childrenout, $children);
		
			} else {

				// We increase level here
				$itera = $level + 1;
				$temparray = self::getChildren( $children, $children_type, $itera );
				
				foreach ($temparray as $temp) {
				
					if ($temp != '') {

						array_push($childrenout, $temp);
					}
				}
			}
		}

		return $childrenout;

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
				$categories_objects = $wikiPage::getCategories();

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

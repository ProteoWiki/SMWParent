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
	
	public static function executeGetParent(  $parser, $frame, $args ) {

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

		self::$parent_round = 0;

		return self::getParent( $child_text, $parent_type, 1, $link );

	}

	public static function executeGetChildren(  $parser, $frame, $args ) {

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


		self::$children_round = 0;

		return self::getChildren( $parent_text, $children_type, 1, $link );

	}

	// Default $level=1; direct parent
	public static function getParent( $child_text, $parent_type, $level=1, $link=0 ) {

		global $wgSMWParentlimit;
		global $wgSMWParentProps;

		// Properties to check: Comes from Process, Comes from Sample, Has Request
		$properties = $wgSMWParentProps;

		// After results query, we add parent round
		if (! isset(self::$parent_round) ) {
			self::$parent_round = 0;
		}
		self::$parent_round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$parent_round ) {
			return "";
		} 

		// Query -> current page
		$results = self::getQueryResults( "[[$child_text]]", $properties, false );

		#Containers
		$processes = array();
		$samples = array();
		$requests = array();

		// In theory, there is only one row
		while ( $row = $results->getNext() ) {

			$processCont = $row[1];
			if ( !empty($processCont) ) {

				while ( $obj = $processCont->getNextObject() ) {
					 array_push( $processes, $obj->getWikiValue() );
				}
			}

			$sampleCont = $row[2];

			if ( !empty($sampleCont) ) {

				while ( $obj = $sampleCont->getNextObject() ) {

					array_push( $samples, $obj->getWikiValue() );
					}
				}

				$requestCont = $row[3];

				if (!empty($requestCont)) {

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
							if ($link == 1) {
								$temp = self::makeLink($temp);	       
							}
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
							if (  ( is_numeric($parent_type) && $parent_type == $level )  || ( self::isEntryType($case, $parent_type )  ) ) {
								if ($link == 1) {
									$case = self::makeLink($case);
								}
								array_push($casesout, $case);
							} else {
								$itera = $level + 1;
								$outcome = self::getParent( $case, $parent_type, $itera, $link );
								$temparray = explode(",", $outcome);
								foreach ($temparray as $temp) {
									if ($temp != '') {
										if ($link == 1) {
										 $temp = self::makeLink($temp);	       
										}
									array_push($casesout, $temp);
								}
							}
						}

					}

					return implode(",", $casesout);
				}
			}
		}
		
		// Return blank if anything left
		
		return "";
	}

	// Default level=1, direct child
	public static function getChildren( $parent_text, $children_type, $level=1, $link=0 ) {
	
		global $wgSMWParentlimit;
		global $wgSMWParentProps;

		// Properties to check: Comes from Process, Comes from Sample, Has Request
		// We put - for inverse properties
		$properties = $wgSMWParentProps;

		// After results query, we add parent round
		if (! isset(self::$children_round) ) {
			self::$children_round = 0;
		}
		self::$children_round++;

		// Ancestors limit
		if ( $wgSMWParentlimit < self::$children_round ) {
			return "";
		}

		// Query -> current page
		$childrenlist = array();

		foreach ( $properties as $prop ) {

			$results = self::getQueryResults( "[[$prop::$parent_text]]", array(), true );

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
			if ( ( is_numeric($children_type) && $children_type == $level ) || ( self::isEntryType($children, $children_type ) ) ) {

				if ($link == 1) {
					$children = self::makeLink($children);
				}

				array_push($childrenout, $children);
		
			} else {

				// We increase level here
				$itera = $level + 1;
				$outcome = self::getChildren( $children, $children_type, $itera, $link );
				$temparray = explode(",", $outcome);
				
				foreach ($temparray as $temp) {
				
					if ($temp != '') {

						if ($link == 1) {
							$temp = self::makeLink($temp);
						}

						array_push($childrenout, $temp);
					}
				}
			}
		}

		return implode(",", $childrenout);

	}


	private static function isEntryType( $entry, $type ) {

		global $wgSMWParentTypeProperty;
		$properties = $wgSMWParentTypeProperty;

		// Query -> current page
		$results = self::getQueryResults( "[[$entry]]", $properties, false );

		while ( $row = $results->getNext() ) {
			$typeCont = $row[1];
			if ( !empty($typeCont) ) {

				while ( $obj = $typeCont->getNextObject() ) {
					if ( $obj->getWikiValue() == $type ) {
						// If the same type
						return true;
					}
				}   
			}  
		}

		return false;
	}


	private static function makeLink ( $fulltitle ) {

		$link = $fulltitle;

		if ( is_object(Title::newFromText($fulltitle)) ) {
			$title = Title::newFromText($fulltitle)->getText();
	
			$link = "[[$fulltitle|$title]]";
		}

		return $link;
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

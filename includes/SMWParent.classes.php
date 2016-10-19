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
			"pos" => "start",
			"type" => self::getProperties( $input['child_text'], $input['type_properties'] ),
			"link" => self::getElementTree( "parent", $input['child_text'], $input['parent_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] ),
			"printouts" => self::getProperties( $input['child_text'], $input['print_properties'] )
		);
		
		return $out;

	}

	public static function executeGetChildren( $input ) {

		self::$round = 0;

		$out = array();
		
		$out[ $input['parent_text'] ] = array(
			"pos" => "start",
			"type" => self::getProperties( $input['parent_text'], $input['type_properties'] ),
			"link" => self::getElementTree( "children", $input['parent_text'], $input['children_type'], $input['link_properties'], $input['type_properties'], $input['level'], $input['print_properties'] ),
			"printouts" => self::getProperties( $input['parent_text'], $input['print_properties'] )
		);
		
		return $out;

	}

	public static function executeGetTree( $input ) {

		$childrenOut = self::executeGetChildren( $input );
		$parentOut = self::executeGetParent( $input );

		// Get the inverse of the parent
		$parentInvertedTree = self::invertTree( $parentOut );

		// We add the children to the inverted parent structure
		$out = self::addChildren( $parentInvertedTree, $childrenOut );
		
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
	
				// Retrieve properties
				$typePropertiesValues = self::getProperties( $target, $typeProperties );

				if ( ( is_numeric( $sourceType ) && $sourceType == $level ) || ( self::checkType( $sourceType, $typePropertiesValues ) ) ) {
	
					$struct = array();
					// For now, only printout in the last one
					$struct["pos"] = "end";
					$struct["printouts"] = $content;
					$struct["type"] = $typePropertiesValues;
					$targetOut[ $prop ][ $target ] = $struct;
			
				} else {
	
					// We increase level here.
					$targetOut[ $prop ][ $target ]["pos"] = "mid";
					$targetOut[ $prop ][ $target ]["printouts"] = $content;
					$targetOut[ $prop ][ $target ]["type"] = $typePropertiesValues;

					$itera = $level + 1;
					$temparray = self::getElementTree( $type, $target, $sourceType, $linkProperties, $typeProperties, $itera, $printProperties );
					

					foreach ( $temparray as $key => $temp ) {

						if ( ! array_key_exists( "link", $targetOut[ $prop ][ $target ] ) ) {
							$targetOut[ $prop ][ $target ]["link"] = array();
						}

						if ( ! array_key_exists( $key, $targetOut[ $prop ][ $target ]["link"] ) ) {
							$targetOut[ $prop ][ $target ]["link"][ $key ] = array();
						}

						$targetOut[ $prop ][ $target ]["link"][ $key ] = $temp;

					}
				}
			}

		}

		// Returns an array of hashes
		return $targetOut;

	}

	/** 
	* Getting a list of properties
	* @element string
	* @properties Array of properties
	**/
	public static function getProperties( $element, $printoutProperties ) {

		// Values
		$printKeys = array();

		// Semantic query
		$results = self::getQueryResults( "[[$element]]", $printoutProperties, false ); // To check

		// In theory, there is only one row
		while ( $row = $results->getNext() ) {

			$start = 2; // Start point for counting printouts
			$add = 0;

			$targetCont = $row[1];

			$numColumns = count( $row );

			if ( !empty($targetCont) ) {

				$pageEntry = null;

				// We assume value is only a single page
				while ( $obj = $targetCont->getNextObject() ) {

					$pageEntry = $obj->getWikiValue();
				}

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
			}
		}

		// Include the case of the Categories here
		if ( in_array( "Categories", $printoutProperties ) ) {

			if ( is_object(Title::newFromText( $element ) ) ) {
				$titleObj = Title::newFromText( $element );
				$categoryPages = $titleObj->getParentCategories();

				$categoryValues = array();

				foreach ( $categoryPages as $categoryPage ) {
					$parts = explode( ":", $categoryPage, 2 );
					array_push( $categoryValues, $parts[0] );
				}

				$printKeys["Categories"] = $categoryValues;

			}

		}

		return $printKeys;
	}

	/**
	* This function is for storing types of properties
	* @param $store : Array the store of the types
	* @param $properties Array: List of properties
	* @return $store
	*/
	private static function retrievePropertyTypes( $store, $properties ) {

		foreach ( $properties as $property ) {

			// We skip category
			if ( $property !== "Categories" ) {

				// We query Has Type property
				$result = self::getProperties( "Property:".$property, array("Has_type") );

				if ( array_key_exists( "Has_type", $result ) ) {
					$store[ $property ]  = $result["Has_type"];
				}
			}
		}

		return $store;

	}


	/**
	* This function checks the type of an entry
	* @param $entry String : entry type
	* @param $typeProperties Array: Properties that assign tyoe
	* @return boolean
	*/

	private static function checkType( $type, $typePropertiesValues ) {

		foreach ( $typePropertiesValues as $property => $values ) {
			if ( is_array( $values ) ) {
				if ( in_array( $type, $values ) ) {
					return true;
				}
			} else {
				if ( $type == $values ) {
					return true;
				}
			}
		}

		return false;

	}


	/**
	* Get the leaves of the tree
	* @param $tree Tree of relationships
    * @return list of leaves
	*/

	public static function getLeavesTree( $tree, $leaves ) {

		foreach ( $tree as $key => $content ) {

			if ( is_array($content) && array_key_exists( "pos", $content ) && $content["pos"] === "end" ) {
				array_push( $leaves, array( $key => $content ) );
			} else {
				if ( is_array($content) && array_key_exists( "link", $content ) ) {

					$links = $content["link"];
					// Iterate links
					foreach ( $links as $link ) {
						foreach ( $link as $entry => $content ) {
								$leaves = self::getLeavesTree( array( $entry => $content ), $leaves );
						}
					}
				}
			}
		}
		return $leaves;
	}

	/** inverse an array tree **/

	private static function invertTree( $tree ) {

		$inverted = array();

		$listKeys = array(
			array()
		);
		$struct = array();

		$links = array();

		$input = array( "tree" => $tree, "keys" => $listKeys, "struct" => $struct, "links" => $links );

		$pre = null;
		$link = null; #start

		$input = self::getPathKeys( $input, $pre, $link );

		var_dump( $input["keys"] );
		var_dump( $input["struct"] );
		var_dump( $input["links"] );

		return $inverted;

	}


	private static function getPathKeys( $input, $pre=null, $connect=null ) {

		$iter = 0;

		$keys = array_keys( $input["tree"] );

		// TODO: Fix iteration here

		if ( count( $keys ) > 1 ) {
			// Copy array
		} else {

			foreach ( $input["tree"] as $key => $value ) {

				// Put links
				if ( $pre && $connect ) {
					$input["links"][$pre][$key] = $connect;
				}

				array_unshift( $input["keys"][$iter], $key );
				$input["struct"][$key] = self::chooseProps( $value ); // Not value but other stuff

				if ( array_key_exists( "link", $value ) ) {
					foreach( $value["link"] as $link => $content ) {
						$input["tree"] = $content;

						$input = self::getPathKeys( $input, $key, $link );
					}
				}
			}
		}

		return $input;

	}

	/** Selecting only certain props **/

	private static function chooseProps( $content ) {

		$struct = array();
		$props = array( "type", "pos" );

		foreach ( $content as $key => $value ) {
			if ( in_array( $key, $props ) ) {
				$struct[$key] = $value;
			}
		}

		return $struct;
	}



	/** Add children nodes to the tree **/

	private static function addChildren( $tree, $childrenOut ) {

		foreach ( $childrenOut as $key => $children ) {

			foreach ( $tree as $keyTree => $content ) {
				if ( $key == $keyTree ) {
					$tree[$keyTree] = $children;
				} else {
					
				}
			}

		}


		return $tree;
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

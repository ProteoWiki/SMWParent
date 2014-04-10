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
 * This class handles further iteration and Views of SMWParent.
 */
class SMWParentIterate {


	public static function doIteration(  $parser, $frame, $args ) {

		global $wgSMWParentdefault;
		global $wgSMWChildrendefault;

		$limit = 100;
		$iter = 1;

		// Whether returning a link or not
		$link = 0;

		$parser->disableCache();

		$function = "parent";

		/**
		/* 1. Function
		/* 2. Page
		**/

		if ( isset( $args[0] ) ) {
			$function = trim( $frame->expand( $args[0] ) );
		}

		// Let's get first Fulltext of page
		$entity_text =  $parser->getTitle()->getFullText();
		if ( isset( $args[1] ) ) {
			$entity = trim( $frame->expand( $args[1] ) );
			if ( $entity != '' ) {
				$entity_text = $entity;   
			}
		}

		$output = "";

		// Refactor this, dude!

		if ( $function == 'parent' ) {

			$end = $wgSMWParentdefault;

			$resultiter = SMWParent::getParent( $entity_text, $iter, 1, $link );
			$output.=  "<p>$iter -->".$resultiter."</p>";

			if ( !empty( $resultiter )  ) {

				while ( strpos( $resultiter, $end.":" ) === false ) {
				//	echo $resultiter;
				//
				//	// We avoid processes
					$iter = $iter + 1;
					$resultiter = SMWParent::getParent( $entity_text, $iter, 1, $link );
					
				
					if ( $iter > $limit || empty( $resultiter ) ) {
						break;
					} else {
						$output.= "<p>$iter -->".$resultiter."</p>";
					}
				}
			}

		} else {

			$end = $wgSMWChildrendefault;

			$resultiter = SMWParent::getChildren( $entity_text, $iter, 1, $link );
			$output.= "<p>$iter -->".$resultiter."</p>";

			if ( !empty( $resultiter )  ) {

				while ( strpos( $resultiter, $end.":" ) === false ) {
				//	echo $resultiter;
				//
				//	// We avoid processes
					$iter = $iter + 1;
					$resultiter = SMWParent::getChildren( $entity_text, $iter, 1, $link );
				
					if ( $iter > $limit || empty( $resultiter ) ) {
						break;
					} else {
						$output.= "<p>$iter -->".$resultiter."</p>";
					}
				}
			}

		}

		return $output;

	}


}


//-- Get SMWParent
//function p.EntityParent(frame)
//    local entity = mw.text.trim(frame.args[1])
//    local output = ""
//    output = EntityIterate("SMWParent", entity, 0, frame, output) 
//    return output
//end
// 
//function EntityIterate(func, entity, iter, frame, output)
//    iter = iter + 2
//    local parsercall = "{{#"..func..":"..entity.."|"..iter.."}}"
//    local result = frame:preprocess(parsercall)
//    -- If empty or request 
//    if ( result ~= "" ) then
//        if ( mw.ustring.find( result, "^Request") ) then
//            output = output.."<p>"..result.."</p>"
//        else
//            output = output.."<p>"..result.."</p>"
//            output = EntityIterate(func, entity, iter, frame, output)
//        end
//--]]
//    end
//    return output    
<?php
class SMWParentHooks {

	// Hook our callback function into the parser
	public static function onParserFirstCallInit( $parser ) {

		$parser->setFunctionHook( 'SMWParent', 'SMWParentParser::parseParent', Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'SMWChildren', 'SMWParentParser::parseChildren', Parser::SFH_OBJECT_ARGS );
		// $parser->setFunctionHook( 'SMWTree', 'SMWParentParser::parseTree', Parser::SFH_OBJECT_ARGS ); Disabled for now

		// Always return true from this function. The return value does not denote
		// success or otherwise have meaning - it just must always be true.
		return true;
	}

}

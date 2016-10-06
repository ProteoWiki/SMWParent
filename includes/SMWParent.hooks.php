<?php
class SMWParentHooks {

	// Hook our callback function into the parser
	public static function onParserFirstCallInit( $parser ) {

		$parser->setFunctionHook( 'SMWParent', 'SMWParent::executeGetParent', Parser::SFH_OBJECT_ARGS );
		$parser->setFunctionHook( 'SMWChildren', 'SMWParent::executeGetChildren', Parser::SFH_OBJECT_ARGS );

		// Always return true from this function. The return value does not denote
		// success or otherwise have meaning - it just must always be true.
		return true;
	}

}

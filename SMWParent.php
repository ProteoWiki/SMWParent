<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'Not a valid entry point';
	exit( 1 );
}

if ( !defined( 'SMW_VERSION' ) ) {
	echo 'This extension requires Semantic MediaWiki to be installed.';
	exit( 1 );
}

#
# This is the path to your installation of SemanticTasks as
# seen from the web. Change it if required ($wgScriptPath is the
# path to the base directory of your wiki). No final slash.
# #
$spScriptPath = $wgScriptPath . '/extensions/SMWParent';
#

# Extension credits
$wgExtensionCredits[defined( 'SEMANTIC_EXTENSION_TYPE' ) ? 'semantic' : 'other'][] = array(
	'path' => __FILE__,
	'name' => 'SMWParent',
	'author' => array(
		'[https://www.mediawiki.org/wiki/User:Toniher Toni Hermoso]'
	),
	'version' => '0.1',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SMWParent',
	'descriptionmsg' => 'smwparent-desc',
);


// i18n
$wgExtensionMessagesFiles['SMWParent'] = dirname( __FILE__ ) . '/SMWParent.i18n.php';
$wgExtensionMessagesFiles['SMWParentMagic'] = dirname( __FILE__ ) . '/SMWParent.i18n.magic.php';

// Autoloading
$wgAutoloadClasses['SMWParent'] = dirname( __FILE__ ) . '/SMWParent.classes.php';

// Hooks
$wgHooks['ParserFirstCallInit'][] = 'wfRegisterSMWParent';


// We set the limit of ancestors to check
$wgSMWParentlimit = 100;
$wgSMWParentdefault = "Request"; 
$wgSMWChildrendefault = "File"; 
$wgSMWParentProps = array('Comes_from_Process', 'Comes_from_Sample', 'Has_Request');

function wfRegisterSMWParent( $parser ) {
	
	$parser->setFunctionHook( 'SMWParent', 'SMWParent::executeGetParent', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'SMWChildren', 'SMWParent::executeGetChildren', SFH_OBJECT_ARGS );
	
	return true;

}


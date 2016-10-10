<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo 'Not a valid entry point';
	exit( 1 );
}

if ( !defined( 'SMW_VERSION' ) ) {
	echo 'This extension requires Semantic MediaWiki to be installed.';
	exit( 1 );
}

//self executing anonymous function to prevent global scope assumptions
call_user_func( function() {

	# Extension credits
	$GLOBALS['wgExtensionCredits'][defined( 'SEMANTIC_EXTENSION_TYPE' ) ? 'semantic' : 'other'][] = array(
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
	$GLOBALS['wgMessagesDirs']['SMWParent'] = __DIR__ . '/i18n';
	$GLOBALS['wgExtensionMessagesFiles']['SMWParent'] = __DIR__ . '/SMWParent.i18n.php';
	$GLOBALS['wgExtensionMessagesFiles']['SMWParentMagic'] = __DIR__ . '/SMWParent.i18n.magic.php';
	
	// Autoloading
	$GLOBALS['wgAutoloadClasses']['SMWParent'] = __DIR__ . '/includes/SMWParent.classes.php';
	$GLOBALS['wgAutoloadClasses']['SMWParentHooks'] = __DIR__ . '/includes/SMWParent.hooks.php';
	$GLOBALS['wgAutoloadClasses']['SMWParentParser'] = __DIR__ . '/includes/SMWParent.parser.php';
	$GLOBALS['wgAutoloadClasses']['ApiSMWParent'] = __DIR__ . '/includes/api/SMWParent.api.php';

	// Hooks
	$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'SMWParentHooks::onParserFirstCallInit';
	
	
	// We set the limit of ancestors to check
	$GLOBALS['wgSMWParentlimit'] = 100;
	// Default/last parent element
	$GLOBALS['wgSMWParentdefault'] = "Request";
	// Default/last child element
	$GLOBALS['wgSMWChildrendefault'] = "File";
	// Which property designs the parent or children
	$GLOBALS['wgSMWParentTypeProperty'] = array("Is_Type");
	// Properties that allow the linking
	$GLOBALS['wgSMWParentProps'] = array('Comes_from_Process', 'Comes_from_Sample', 'Has_Request');
	// Print properties. Apart from the actual page
	$GLOBALS['wgSMWParentPrintProps'] = array('');

	// API
	$GLOBALS['wgAPIModules']['smwparent'] = 'ApiSMWParent';

});


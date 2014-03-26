# SMWParent

Extension for printing out ancestors and descendants of a certain page based on interconnected Semantic MediaWiki properties.

## Functions 

* {{#SMWParent:}} 
  Show the parent / ancestor pages
  
* {{#SMWChildren:}}
  Show the children / descendant pages

## Parameters and default values

You can override these values by modifying LocalSettings.php below extension requirement.

$wgSMWParentlimit = 100; // Limit of pages to transverse

$wgSMWParentTypeProperty = array("Is_Type"); // Defines the SMW Property that assigns a particular type to a page

$wgSMWParentdefault = "Request"; // Default type of a page

$wgSMWChildrendefault = "File"; // Default type of a file

$wgSMWParentProps = array('Comes_from_Process', 'Comes_from_Sample', 'Has_Request'); // Properties that provide de glueing between the different pages.

## Usage

* {{#SMWParent:FULLPAGENAME|PARENTTYPE/PARENTLEVEL|link}}
** 1st parameter: If FULLPAGENAME is skipped, current page is used
** 2nd parameter: Which parent type to retrieve, or up to which level arrive
** 3rd parameter: If input is 'link', resulting pages are shown as links instead of as text.


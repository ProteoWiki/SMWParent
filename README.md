# SMWParent

[![DOI](https://zenodo.org/badge/24464261.svg)](https://zenodo.org/badge/latestdoi/24464261)

Extension for printing out ancestors, descendants and relationship trees of pages interconnected with Semantic MediaWiki properties.

## Functions 

* {{#SMWParent:}} 
  Show the parent / ancestor pages
  
* {{#SMWChildren:}}
  Show the children / descendant pages

### Usage

* {{#SMWParent:FULLPAGENAME|PARENT_TYPE/PARENT_LEVEL|link}}


Params:

- If FULLPAGENAME is skipped, current page is used
- PARENT_TYPE to retrieve, or alternately up to which level PARENT_LEVEL to reach
- If input is 'link', resulting pages are shown as links instead of as text.

## API

An API endpoint is available. action=smwparent.
* retrieve: 3 possible methods (parent, children, tree)
* title: fullpage title of page in the wiki
* type: the type of pages to be retrieved (according to a given properties)
* link_properties: properties used for linking between pages
* type_properties: properties used for defining the types. If 'Categories', MediaWiki categories are also used.
* print_properties: properties to be printed and appended to the nodes.

## Parameters and default values

You can override these values by modifying LocalSettings.php below extension requirement.

$wgSMWParentlimit = 100; // Limit of pages to transverse

$wgSMWParentTypeProperty = array("Is_Type"); // Defines the SMW Property that assigns a particular type to a page

$wgSMWParentdefault = "Request"; // Default type of a page

$wgSMWChildrendefault = "File"; // Default type of a file

$wgSMWParentProps = array('Comes_from_Process', 'Comes_from_Sample', 'Has_Request'); // Properties that provide de glueing between the different pages.

$wgSMWParentPrintProps = array('Start', 'End'); // Properties associated to an object which are printed

## TODO

* Better handling of SMW property types
* Refactor some functions and variables


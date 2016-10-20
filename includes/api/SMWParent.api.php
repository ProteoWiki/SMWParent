<?php
class ApiSMWParent extends ApiBase {

	public function execute() {

		$params = $this->extractRequestParams();

		$output = array();

		$input = array();
		$input['child_text'] = $params['title'];
		$input['parent_text'] = $params['title'];
		$input['parent_type'] = $params['type'];
		$input['children_type'] = $params['type'];

		if ( array_key_exists( "link_properties", $params ) ) {
			$input['link_properties'] = explode( ",", $params['link_properties'] );
		} else {
			global $wgSMWParentProps;
			$input['link_properties'] = $wgSMWParentProps;
		}

		if ( array_key_exists( "type_properties", $params ) ) {
			$input['type_properties'] = explode( ",", $params['type_properties'] );
		} else {
			global $wgSMWParentTypeProperty;
			$input['type_properties'] = $wgSMWParentTypeProperty;
		}

		if ( array_key_exists( "print_properties", $params ) ) {
			$input['print_properties'] = explode( ",", $params['print_properties'] );
		} else {
			global $wgSMWParentPrintProps;
			$input['print_properties'] = $wgSMWParentPrintProps;
		}

		switch ( $params['retrieve'] )  {

			case "parent":
				$output = SMWParent::executeGetParent( $input );

			case "children":
				$output = SMWParent::executeGetChildren( $input );

			case "tree":
				$output = SMWParent::executeGetTree( $input );

		}

		// TODO, generate processing
		$this->getResult()->addValue( null, $this->getModuleName(), array ( 'status' => "OK", 'content' => $output ) );

		return true;

	}

	public function getAllowedParams() {
		return array(
			'retrieve' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'title' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'type' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'link_properties' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'type_properties' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'print_properties' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			)
		);
	}

	public function getDescription() {
		return array(
			'API for handling content from SMWParent'
		);
	}
	public function getParamDescription() {
		return array(
			'retrieve' => 'Either parent, children or tree',
			'title' => 'Actual starting page',
			'type' => 'Level or page type',
			'link_properties' => 'Properties used for linking',
			'type_properties' => 'Properties used for defining types',
			'print_properties' => 'Properties to be printed'
		);
	}

	public function getVersion() {
		return __CLASS__ . ': 1.1';
	}

}
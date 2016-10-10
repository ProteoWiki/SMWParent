<?php
class ApiSMWParent extends ApiBase {

	public function execute() {

		$params = $this->extractRequestParams();

		// Handle array

		// TODO, generate processing
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
			'retrieve' => 'Either parent or children',
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
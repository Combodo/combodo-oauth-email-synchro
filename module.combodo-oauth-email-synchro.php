<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'combodo-oauth-email-synchro/1.2.3',
	array(
		// Identification
		//
		'label'        => 'OAuth email syncro',
		'category'     => 'business',

		// Setup
		//
		'dependencies' => array(
			'combodo-email-synchro/3.5.2',
			'itop-standard-email-synchro/3.4.1',
			'itop-oauth-client/2.7.7',
		),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'vendor/autoload.php',
			'model.combodo-oauth-email-synchro.php', // Contains the PHP code generated by the "compilation" of datamodel.combodo-oauth-email-synchro.xml
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
			// Module specific settings go here, if any
		),
	)
);

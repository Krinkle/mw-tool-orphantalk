<?php
/**
 * Main index
 *
 * @author Timo Tijhof, 2011-2014
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-orphantalk
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once __DIR__ . '/../lib/basetool/InitTool.php';
require_once KR_TSINT_START_INC;

// Class for this tool
require_once __DIR__ . '/../class.php';
$kgTool = new OrphanTalk();

$I18N = new Intuition( 'orphantalk2' );

$toolConfig = array(
	'displayTitle' => $I18N->msg( 'title' ),
	'remoteBasePath' => dirname( $kgConf->getRemoteBase() ). '/',
	'revisionId' => '0.3.0',
	'I18N' => $I18N,
	'styles' => array(
		'resources/chosen/chosen.css',
		'resources/bootstrap-chosen.css',
		'main.css',
	),
	'scripts' => array(
		'resources/chosen/chosen.jquery.min.js',
		'main.js',
	),
	'requireJS' => true,
);

$kgBaseTool = BaseTool::newFromArray( $toolConfig );
$kgBaseTool->setSourceInfoGithub( 'Krinkle', 'mw-tool-orphantalk', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$kgTool->run();
$kgBaseTool->flushMainOutput();

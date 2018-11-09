<?php
/**
 * Main index
 *
 * @copyright 2011-2018 Timo Tijhof
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';

$tool = new OrphanTalk();
$I18N = new Intuition( 'orphantalk2' );
$I18N->registerDomain( 'orphantalk2', __DIR__ . '/../messages' );

$kgBase = BaseTool::newFromArray( [
	'displayTitle' => $I18N->msg( 'title' ),
	'remoteBasePath' => dirname( $_SERVER['PHP_SELF'] ),
	'I18N' => $I18N,
	'styles' => [
		'resources/chosen/chosen.css',
		'resources/bootstrap-chosen.css',
		'main.css',
	],
	'scripts' => [
		'resources/chosen/chosen.jquery.min.js',
		'main.js',
	],
	'requireJS' => true,
] );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-orphantalk', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();

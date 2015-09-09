<?php
/**
 * Main index
 *
 * @author Timo Tijhof
 * @license http://krinkle.mit-license.org/
 * @package mw-tool-orphantalk
 */

/**
 * Configuration
 * -------------------------------------------------
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../class.php';
require_once __DIR__ . '/../config.php';

$tool = new OrphanTalk();
$I18N = new Intuition( 'orphantalk2' );
$I18N->registerDomain( 'orphantalk2', __DIR__ . '/../messages' );

$kgBase = BaseTool::newFromArray( array(
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
) );
$kgBase->setSourceInfoGithub( 'Krinkle', 'mw-tool-orphantalk', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */

$tool->run();
$kgBase->flushMainOutput();

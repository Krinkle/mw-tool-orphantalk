<?php
/**
 * @copyright 2011-2018 Timo Tijhof
 * @license MIT
 */

use Krinkle\Intuition\Intuition;
use Krinkle\Toolbase\BaseTool;

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
	'sourceInfo' => array(
		'issueTrackerUrl' => 'https://phabricator.wikimedia.org/tag/orphantalk/',
	),
	'requireJS' => true,
] );
$kgBase->setSourceInfoGerrit( 'labs/tools/orphantalk', dirname( __DIR__ ) );

/**
 * Output
 * -------------------------------------------------
 */


// Mock the wiki replicas during local development:

// use Krinkle\Toolbase\Cache;
// global $kgCache;
// '@phan-var Cache $kgCache';
// $kgCache->set(
// 	Cache::makeKey( 'base', 'labsdb', 'meta', 'dbinfos' ),
// 	array(
// 		'metawiki' => array(
// 			'dbname' => 'metawiki',
// 			'family' => 'special',
// 			'url' => 'https://meta.wikimedia.org',
// 			'slice' => 's0.local'
// 		)
// 	)
// );

$tool->run();
$kgBase->flushMainOutput();

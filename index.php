<?php
/**
 * OrphanTalk2
 * Created on January 29, 2011
 *
 * @author Krinkle <krinklemail@gmail.com>, 2011 - 2012
 * @license CC-BY 3.0
 */

/**
 * Configuration
 * -------------------------------------------------
 */
// BaseTool & Localization
require_once( '../ts-krinkle-basetool/InitTool.php' );
require_once( KR_TSINT_START_INC );

// Local functions
require_once( __DIR__ . '/functions.php' );

$I18N = new TsIntuition( 'Orphantalk2' );

$toolConfig = array(
	'displayTitle'     => 'OrphanTalk2',
	'remoteBasePath'   => $kgConf->getRemoteBase() . '/OrphanTalk2/',
	'localBasePath'    => __DIR__,
	'revisionId'       => '0.2.1',
	'revisionDate'     => '2011-10-24',
	'I18N'             => $I18N,
);

$Tool = BaseTool::newFromArray( $toolConfig );
$Tool->setSourceInfoGithub( 'Krinkle', 'ts-krinkle-Orphantalk2' );

$Tool->doHtmlHead();
$Tool->doStartBodyWrapper();


/**
 * Database connections
 * -------------------------------------------------
 */
kfConnectToolserverDB();


/**
 * Settings
 * -------------------------------------------------
 */
$toolSettings = array(
	'isSubmit' => false,
	'limits' => array( 10, 25, 50, 100 ),
	'limitDefault' => 10,
);

// Parameters
$Params = array(
	'wikidb' => getParamVar( 'wikidb' ),
	'update' => getParamVar( 'update' ),
	'namespace' => getParamInt( 'namespace' ),
	'hideredirects' => getParamCheck( 'hideredirects' ),
	'hidesubpages' => getParamCheck( 'hidesubpages' ),
	'sort' => getParamCheck( 'sort' ),
	'limit' => getParamInt( 'limit' ),
);
if ( !is_odd( $Params['namespace'] ) ) {
	unset( $Params['namespace'] );
}
if ( !in_array( $Params['limit'], $toolSettings['limits'] ) ) {
	$Params['limit'] = $toolSettings['limitDefault'];
}
$toolSettings['permalink'] = $Tool->generatePermalink( $Params );

// Submitted ?
if ( !empty( $Params['wikidb'] ) && !empty( $Params['namespace'] ) && empty( $Params['update'] ) ) {
	$toolSettings['isSubmit'] = true;
	// Determine subject and talkspace
	$toolSettings['talkspace'] = $Params['namespace'];
	$toolSettings['subjectspace'] = $Params['namespace']-1;
}


/**
 * Input form
 * -------------------------------------------------
 */
$oddNSs = ot2_getOddNamespacesByDB( $Params['wikidb'] );

// Talk namespace selector
$talkSelect = ot2_talkSelect( $oddNSs );

// Limit selector
$limitSelect = ot2_limitSelect();

// Build input form
$checkedAttr = ' checked="checked"';
$form =
		'<form action="' . $Tool->remoteBasePath . '" method="get" class="colly ns"><fieldset>'
	.	'<legend>' . _( 'form-legend-settings', 'krinkle' ) . '</legend>'

			// Select wiki
	.		kfGetAllWikiSelect( array( 'current' => $Params['wikidb'] ) )
	.		'<br />'

			// Update form
	.		'<label></label><input type="submit" nof value="' . _( 'update' ) . '" /><br />'

			// Select talk space
	.		"$talkSelect<br />"

			// Toggle redirects
	.		'<label for="hideredirects">' . _( 'hideredirects' ) . '</label>'
	.		'<input type="checkbox" name="hideredirects" value="on" ' . ( $Params['hideredirects'] ? $checkedAttr : '' ) . ' />'
	.		'<br />'

			// Toggle subpages
	.		'<label for="hidesubpages">' . _( 'hidesubpages' ) . '</label>'
	.		'<input type="checkbox" name="hidesubpages" value="on" ' . ( $Params['hidesubpages'] ? $checkedAttr : '' ) . ' />'
	.		'<br />'

			// Select limit
	.		"$limitSelect<br />"

			// Submit form
	.		'<label></label><input type="submit" nof value="' . _g( 'form-submit' ) . '" /><br />'
	

	.	'</fieldset></form>';

$Tool->addOut( $form );

// Close connection(s) related to getting allwiki/namespaces data
kfCloseAllConnections();


/**
 * Output
 * -------------------------------------------------
 */
// Only if the form was submitted and connecting returned true
if ( $toolSettings['isSubmit'] && kfConnectRRServerByDBName( $Params['wikidb'] )  ) {

	$dbQuery = ot2_prepareQuery( $Params );
	kfLog( $dbQuery );
	$dbResults = kfDoSelectQueryRaw( $dbQuery );
	$Tool->addOut( ot2_renderOutput( $dbResults, $Params['wikidb'], $oddNSs ) );

}

$Tool->addOut( $I18N->getPromoBox() );


/**
 * Close up
 * -------------------------------------------------
 */
kfCloseAllConnections();
$Tool->flushMainOutput();

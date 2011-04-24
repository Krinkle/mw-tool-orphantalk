<?php
/**
 * Local functions for OrphanTalk2
 */

function ot2_getOddNamespacesByDB( $wikidb ) {
	$dbQuery = "
		SELECT ns_id, ns_name FROM toolserver.namespacename
		WHERE dbname='" . mysql_clean( $wikidb ) . "'
		AND ns_type='primary' AND ns_id%2=1
		ORDER BY ns_id;";
	$dbResult = kfDoSelectQueryRaw( $dbQuery );
	if ( empty( $dbResult ) ) {
		return false;
	}
	$oddNSs = array();
	foreach( $dbResult as $ns ) {
		$oddNSs[$ns->ns_id] = $ns->ns_name;
	}
	return $oddNSs;
}

function ot2_getAllNamespacesByDB( $wikidb ) {
	$dbQuery = "
		SELECT ns_id, ns_name FROM toolserver.namespacename
		WHERE dbname='" . mysql_clean( $wikidb ) . "'
		AND ns_type='primary'
		ORDER BY ns_id;";
	$dbResult = kfDoSelectQueryRaw( $dbQuery );
	if ( empty( $dbResult ) ) {
		return false;
	}
	$namespaces = array();
	foreach( $dbResult as $ns ) {
		$namespaces[$ns->ns_id] = $ns->ns_name;
	}
	return $namespaces;
}

function ot2_prepareQuery( $p ) {
	global $toolSettings;

	$whereClauses = array();
	$orderClause = 'ORDER BY p2.page_title ASC';
	if ( $p['hideredirects'] ) {
		$whereClauses[] = 'AND p2.page_is_redirect=0';
	}
	
	if ( $p['hidesubpages'] ) {
		$whereClauses[] = 'AND p2.page_title NOT LIKE "%/%"';
	
	}
	
	if ( $p['sort'] == 'older' ) {
		$orderClause = 'ORDER BY p2.page_id DESC';
	}
	$limit = $p['limit'] + 1;
	$dbQuery = "
		SELECT
			p2.page_title,
			p2.page_namespace,
			p2.page_id,
			p2.page_is_redirect
		
		FROM page as p1
			RIGHT JOIN page as p2
				ON p1.page_title = p2.page_title
				AND p1.page_namespace=" . (int)$toolSettings['subjectspace'] . "
		
		WHERE	p2.page_namespace=" . (int)$toolSettings['talkspace'] . "
		AND		p1.page_title IS NULL
		" . implode( ' ', $whereClauses ) . "

		" . $orderClause . "
		LIMIT " . $limit;

	return $dbQuery;
}

function ot2_talkSelect( $oddNSs ) {
	global $Params;

	$talkSelect = '<label for="namespace">' . _g( 'namespace' ) . '</label>';
	if ( !empty( $oddNSs ) ) {
		$talkSelect .= '<select name="namespace">';
		foreach( $oddNSs as $nsID => $nsName ) {
			$attr = '';
			if ( $Params['namespace'] == $nsID ) {
				$attr = ' selected="selected"';
			}
			$talkSelect .= "<option value=\"$nsID\"$attr>" . htmlspecialchars( $nsName ) . '</option>';
		}
	} else {
		$talkSelect .= '<select name="namespace" disabled="disabled">';
		$talkSelect .= '<option value="">' . _( 'select-wiki-first' ) . '</option>';
	}
	$talkSelect .= '</select>';
	return $talkSelect;
}

function ot2_limitSelect(){
	global $toolSettings, $Params;

	$limitSelect = '<label for="limit">' . _( 'limit' ) . '</label><select name="limit">';
	foreach( $toolSettings['limits'] as $limit ) {
		$attr = '';
		if ( $Params['limit'] == $limit ) {
			$attr = ' selected="selected"';
		}
		$limitSelect .= "<option value=\"$limit\"$attr>$limit</option>";
	}
	$limitSelect .= '</select>';
	return $limitSelect;
}

function ot2_link( $href, $text ){
	return '<a target="_blank" href="' . htmlspecialchars( $href ) . '">' . htmlspecialchars( $text ) . '</a>';
}

function ot2_renderOutput( $dbResults, $dbname, $oddNSs ) {
global $toolSettings, $Params;

$allnamespaces = ot2_getAllNamespacesByDB( $dbname );

$output =
	'<h3 id="output">' . _( 'output' ) . '</h3>'
.	'<table class="wikitable ns">'
.	'<tr><th>' . _( 'page' )  . '</th><th>' . _( 'redirect' )  . '</th></tr>';

$wikiData = kfGetWikiDataFromDBName( $dbname );

$limited = false;
if ( count( $dbResults ) > $Params['limit'] ) {
	array_pop( $dbResults ); $limited = true;
}
foreach( $dbResults as $i => $res ) {
		$p_view = array(
			'curid' => $res->page_id,
			'redirect' => 'no',
		);
		$p_diff = array(
			'curid' => $res->page_id,
			'redirect' => 'no',
			'diff' => 'curr',
		);
		$p_hist = array(
			'curid' => $res->page_id,
			'action' => 'history',
		);
		$p_viewsubject = array(
			'title' => $allnamespaces[$toolSettings['subjectspace']] . ':' . $res->page_title,
			'redirect' => 'no',
		);
		$p_links = array(
			'title' => 'Special:WhatLinksHere',
			'target' => $oddNSs[$res->page_namespace] . ':' . $res->page_title,
		);
		$deleteSummary = _( 'deletesummary', array( 'variables' => array( '[[m:User:Krinkle/Tools#OrphanTalk2|Krinkle/OrphanTalk2]]' )));
		$p_delete = array(
			'curid' => $res->page_id,
			'redirect' => 'no',
			'action' => 'delete',
			'wpReason' => $deleteSummary,
		);
		$globalUsage = '';
		if ( $Params['wikidb'] == 'commonswiki_p' && $toolSettings['subjectspace'] == 6  ) {
			$p_globalusage = array(
				'title' => 'Special:GlobalUsage',
				'target' => $res->page_title,
			);
			$globalUsage = ' | ' . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_globalusage ), _( 'tools-globalusage' ) );
		}
		$output .= '
			<tr>
				<td>'
					. '<small>('
						 . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_delete ), _( 'tools-delete' ) )
						 . $globalUsage
						 . ' | '
						 . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_links ), _( 'tools-links' ) )
						 . ' | '
						 . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_viewsubject ), _( 'tools-subject' ) )
						 . ' | '
						 . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_hist ), _( 'tools-hist' ) )
						 . ' | '
						 . ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_diff ), _( 'tools-curr' ) )
					 . ')</small> &middot; '
					. ot2_link( $wikiData['url'] . '/?' .http_build_query( $p_view ), $oddNSs[$res->page_namespace] . ':' . $res->page_title ) . '</td>
				<td>' . $res->page_is_redirect . '</td>
			</tr>';
	}
	if ( empty( $dbResults ) ) {
		$output .= '<tr><td colspan="2"><em>' . _html( 'noresults') . '</em></td></tr>';
	}
	$output .= '</table>';
	if ( $limited ) {
		$output .= '<p><em>' . _html( 'resultslimited', array('variables'=>array($Params['limit']))) . '</em></p>';
	}
	return $output;
}

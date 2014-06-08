<?php
/**
 * Main class
 *
 * @package mw-tool-orphantalk
 */
class OrphanTalk extends KrToolBaseClass {

	protected $settingsKeys = array();
	protected $params = null;

	protected function show() {
		global $kgBaseTool, $I18N, $kgReq;

		$kgBaseTool->setLayout( 'header', array( 'captionText' => $I18N->msg( 'description' ) ) );

		$kgBaseTool->addOut( '<div class="container">' );

		$this->params['wiki'] = $kgReq->hasKey( 'wiki' ) ? $kgReq->getVal( 'wiki' ) : null;
		$this->params['ns'] = $kgReq->hasKey( 'ns' ) ? $kgReq->getInt( 'ns' ) : null;
		$this->params['hideredirects'] = $kgReq->hasKey( 'hideredirects' );
		$this->params['hidesubpages'] = $kgReq->hasKey( 'hidesubpages' );
		$this->params['limit'] = $kgReq->getInt( 'limit', 25 );

		$this->showForm();
		if ( $kgReq->wasPosted() ) {
			$this->execute();
		}

		// Close wrapping container
		$kgBaseTool->addOut( '</div>' );
	}

	protected function showForm() {
		global $kgBaseTool, $I18N;
		$section = new KfLogSection( __METHOD__ );

		$wikiOptionsHtml = kfGetAllWikiOptionHtml( array( 'current' => $this->params['wiki'] ) );

		$nsOptionsHtml = '';
		// Namespace dropdown is usually populated client-side with AJAX when using the form.
		// If a wiki has been set in the request already, prepopulate it.
		if ( $this->params['wiki'] ) {
			$namespaces = $this->getNamespaces( $this->params['wiki'] );
			foreach ( $namespaces as $nsId => $nsText ) {
				if ( $nsId > 0 && $nsId % 2 ) {
					$nsOptionsHtml .= Html::element( 'option', array(
						'value' => $nsId,
						'selected' => $nsId === $this->params['ns'],
					), $nsText );
				}
			}
		}

		$limitOptionsHtml = '';
		foreach ( array( 10, 25, 50, 100 ) as $limit ) {
			$limitOptionsHtml .= Html::element( 'option', array(
				'value' => $limit,
				'selected' => $limit === $this->params['limit'],
			), $limit );
		}

		$kgBaseTool->addOut(
			'<form class="form-horizontal" role="form" id="ot-form" method="post">'
			. '<fieldset>'
			. Html::element( 'legend', array(), $I18N->msg( 'form-legend-settings', 'krinkle' ) )
			. '<div class="form-group">'
			. Html::element( 'label', array(
				'for' => 'ot-form-wiki',
				'class' => 'control-label col-sm-2',
			), $I18N->msg( 'label-wiki' ) )
				. '<div class="col-sm-5">'
				. Html::rawElement( 'select', array(
					'id' => 'ot-form-wiki',
					'name' => 'wiki',
					'class' => 'form-control chosen-select',
				),
					Html::element( 'option', array(
						'disabled' => true,
						'selected' => !$this->params['wiki'],
					), $I18N->msg( 'select-wiki-first' ) )
					. $wikiOptionsHtml
				)
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
			. Html::element( 'label', array(
				'for' => 'ot-form-ns',
				'class' => 'control-label col-sm-2',
			), $I18N->msg( 'namespace', 'general' ) )
				. '<div class="col-sm-5">'
				. Html::rawElement( 'select', array(
					'id' => 'ot-form-ns',
					'name' => 'ns',
					'class' => 'form-control',
				), $nsOptionsHtml )
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
				. '<div class="col-sm-offset-2 col-sm-5">'
					. '<div class="checkbox">'
					. Html::openElement( 'label' )
					. Html::element( 'input', array(
						'type' => 'checkbox',
						'name' => 'hideredirects',
						'checked' => $this->params['hideredirects'],
					) )
					. ' ' . htmlspecialchars( $I18N->msg( 'hideredirects' ) )
					. Html::closeElement( 'label' )
					. '</div>'
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
				. '<div class="col-sm-offset-2 col-sm-5">'
					. '<div class="checkbox">'
					. Html::element( 'input', array(
						'type' => 'checkbox',
						'name' => 'hidesubpages',
						'checked' => $this->params['hidesubpages'],
						'id' => 'ot-form-hidesubpages',
					) )
					. Html::element( 'label', array(
						'for' => 'ot-form-hidesubpages',
					), $I18N->msg( 'hidesubpages' ) )
					. '</div>'
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
			. Html::element( 'label', array(
				'for' => 'ot-form-limit',
				'class' => 'control-label col-sm-2',
			), $I18N->msg( 'limit' ) )
				. '<div class="col-sm-5">'
				. Html::rawElement( 'select', array(
					'name' => 'limit',
					'id' => 'ot-form-limit',
					'class' => 'form-control',
				), $limitOptionsHtml )
				. '</div>'
			. '</div>'
			. '<div class="form-group">'
				. '<div class="col-sm-offset-2 col-sm-5">'
				. Html::element( 'button', array(
					'class' => 'btn btn-primary',
					'id' => 'ot-form-submit',
				), $I18N->msg( 'form-submit', 'general' ) )
				. '</div>'
			. '</div>'
			. '</fieldset>'
			. '</form>'
		);
	}

	protected function execute() {
		global $kgBaseTool, $I18N;
		$section = new KfLogSection( __METHOD__ );

		// Required
		if ( !$this->params['wiki'] || !$this->params['ns'] ) {
			throw new Exception( 'Missing required parameters' );
		}

		if ( $this->params['ns'] < 0 || $this->params['ns'] % 2 === 0 ) {
			throw new Exception( 'Talk namespace must have an odd non-zero id.' );
		}

		$kgBaseTool->addOut( Html::element( 'h2', array( 'id' => 'output' ), $I18N->msg( 'output' ) ) );

		$rows = $this->fetchOrphans();
		if ( !count( $rows ) ) {
			$kgBaseTool->addOut( Html::element( 'p', array( 'class' => 'lead text-muted' ), $I18N->msg( 'noresults' ) ) );
			return;
		}

		$wikiInfo = LabsDB::getDbInfo( $this->params['wiki'] );

		$html = Html::openElement( 'table', array( 'class' => 'table table-bordered table-hover table-xs-stack ot-table' ) )
			. '<colgroup>'
			. '<col class="col-sm-1"></col><col class="col-sm-4"></col><col class="col-sm-7"></col>'
			. '</colgroup>'
			. '<thead><tr>'
			. Html::element( 'th', array(), '#' )
			. Html::element( 'th', array( 'colspan' => '2' ), $I18N->msg( 'page' ) )
			. '</tr></thead><tbody>';

		foreach ( $rows as $i => &$row ) {
			$links = $this->getPageActionLinks( $wikiInfo, $row );
			$html .= '<tr>'
				. Html::element( 'td', array(), $i +1 )
				. Html::openElement( 'td', array(
					'class' => array(
						'ot-cell-trim',
						'ot-page',
						'ot-page-redirect' => !!$row['page_is_redirect'],
					),
					'title' => $row['page_is_redirect'] ? $I18N->msg( 'tooltip-redirect' ) : null
				) )
				. Html::rawElement( 'a', array(
					'href' => $links['view']['url'],
					'target' => '_blank',
				),
					( $row['page_is_redirect']
						? '<span class="glyphicon glyphicon-forward"></span> '
						: '<span class="glyphicon glyphicon-file"></span> '
					)
					. ' '
					. htmlspecialchars( str_replace( '_', ' ', $row['page_title'] ) )
				)
				. '</td>'
				. '<td>'
				. join( ' &bull; ', array_map( function ( $link ) {
					return Html::element( 'a', array( 'href' => $link['url'], 'target' => '_blank' ), $link['label'] );
				}, $links ) )
				. '</td>'
				. '</tr>';
		}
		$html .= '</tbody></table>';
		$kgBaseTool->addOut( $html );
	}

	protected function getPageActionLinks( Array &$wikiInfo, Array &$pageRow ) {
		global $I18N;

		$namespaces = $this->getNamespaces( $wikiInfo['dbname'] );
		$links = array(
			'view' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'curid' => $pageRow['page_id'],
					'redirect' => $pageRow['page_is_redirect'] ? 'no' : null,
				) ),
				'label' => $I18N->msg( 'tools-view' ),
			),
			'delete' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'curid' => $pageRow['page_id'],
					'action' => 'delete',
					'wpReason' => $I18N->msg( 'deletesummary', array(
						'variables' => array( '[[m:User:Krinkle/Tools#OrphanTalk|Krinkle/' . $I18N->msg( 'title' ) . ']]' )
					) ),
				) ),
				'label' => $I18N->msg( 'tools-delete' ),
			),
			'links' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'title' => 'Special:WhatLinksHere',
					'target' => $namespaces[ $pageRow['page_namespace'] ] . ':' . $pageRow['page_title'],
				) ),
				'label' => $I18N->msg( 'tools-links' ),
			),
			'subject' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'title' => $namespaces[ $pageRow['page_namespace'] - 1 ] . ':' . $pageRow['page_title'],
				) ),
				'label' => $I18N->msg( 'tools-subject' ),
			),
			'hist' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'curid' => $pageRow['page_id'],
					'action' => 'history',
				) ),
				'label' => $I18N->msg( 'tools-hist' ),
			),
			'curr' => array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'curid' => $pageRow['page_id'],
					'diff' => 'curr',
				) ),
				'label' => $I18N->msg( 'tools-curr' ),
			),
		);

		// Add link to GlobalUsage in case of File_talk pages on Commons.
		// Sometimes people create talk pages for files that may not or no
		// longer exist on Commons, and yet somehow referenced from local wikis.
		if ( $wikiInfo['dbname'] === 'commonswiki' && $pageRow['page_namespace']-1 == 6 ) {
			$links['globalusage'] = array(
				'url' => $wikiInfo['url'] . '/w/index.php?' . http_build_query( array(
					'title' => 'Special:WhatLinksHere',
					'target' => $pageRow['page_title'],
				) ),
				'label' => $I18N->msg( 'tools-globalusage' ),
			);
		}

		return $links;
	}

	/**
	 * @return Array
	 */
	protected function fetchOrphans() {
		$conn = LabsDB::getDB( $this->params['wiki'] );

		$where = array();
		if ( $this->params['hideredirects'] ) {
			$where[] = 'AND p2.page_is_redirect=0';
		}
		if ( $this->params['hidesubpages'] ) {
			$where[] = 'AND p2.page_title NOT LIKE "%/%"';
		}

		$m = $conn->prepare( '/* OrphanTalk::getOrphans */
			SELECT
				p2.page_title,
				p2.page_namespace,
				p2.page_id,
				p2.page_is_redirect

			FROM page as p1
			RIGHT JOIN page as p2
				ON p1.page_title = p2.page_title
				AND p1.page_namespace = :p1ns

			WHERE p2.page_namespace = :p2ns
			AND   p1.page_title IS NULL
		' . implode( ' ', $where ) . '

		ORDER BY p2.page_namespace ASC, p2.page_title ASC
		LIMIT :limit
		' );

		$m->bindValue( ':p1ns', $this->params['ns'] - 1, PDO::PARAM_INT );
		$m->bindValue( ':p2ns', $this->params['ns'], PDO::PARAM_INT );
		$m->bindValue( ':limit', $this->params['limit'], PDO::PARAM_INT );
		$m->execute();
		return $m->fetchAll( PDO::FETCH_ASSOC );
	}

	/**
	 * @param string $url
	 * @return array
	 */
	protected function getNamespaces( $dbname ) {
		return Wiki::byDbname( $dbname )->getNamespaces();
	}
}

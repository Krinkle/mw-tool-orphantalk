/*global $, KRINKLE */
(function () {
	var supported, wiki, limit, pNsInit, $msgs, $form, $wiki, $ns, $limit, $submit;

	// Enhancements only for modern and up-to-date browsers.
	supported = !!(
		window.localStorage &&
			window.JSON &&
			JSON.parse &&
			JSON.stringify &&
			'dataset' in document.documentElement
	);

	if (!supported) {
		$('html').addClass('client-nojs').removeClass('client-js');
		return;
	}

	$form = $('#ot-form');
	$wiki = $('#ot-form-wiki');
	$ns = $('#ot-form-ns');
	$limit = $('#ot-form-limit');
	$submit = $('#ot-form-submit');

	function msg(type, content) {
		if (!$msgs) {
			$msgs = $('<div>').insertBefore($form);
		} else {
			$msgs.empty();
		}
		$msgs.append(
			$('<div>').addClass('alert alert-' + type).append(content)
		);
	}

	function updateNamespaces(hostname) {
		// Fetch namespaces
		return $.ajax({
			// @todo Assumes all WMF wikis support HTTPS
			// @todo Assumes script path is /w (meta_p.wiki in WMFLabs doesn't provide scriptpath)
			url: '//' + hostname + '/w/api.php',
			data: {
				format: 'json',
				action: 'query',
				meta: 'siteinfo',
				siprop: 'namespaces'
			},
			dataType: 'jsonp',
			cache: true
		}).then(function (data) {
			if (!data || data.error) {
				return $.Deferred().reject();
			}
			var options = $.map(data.query.namespaces, function (ns) {
				if ( ns.id > 0 && ns.id % 2 ) {
					// @todo Localise
					return $('<option>').prop('value', ns.id).text(ns['*']).get( 0 );
				}
			});
			$ns.empty().append(options);
		}).then(null, function () {
			// @todo Localise
			msg('danger', 'Namespace update failed!');
		});
	}

	function handleWikiSelect(node) {
		var option = node.options[ node.selectedIndex ];
		var url = option && option.dataset.url;
		return updateNamespaces(url);
	}

	$wiki.on('change', function () {
		handleWikiSelect(this);
	});

	// Remember the user's choise of wiki between sessions
	if (!KRINKLE.baseTool.req.wasPosted) {
		wiki = localStorage.getItem('orpht-form-wiki');
		limit = localStorage.getItem('orpht-form-limit');
		if (limit) {
			$limit.val(limit);
		}
		if (wiki) {
			$wiki.val(wiki);

			// If we preselected a remembered value, then fetch namespaces
			// immediately. Otherwise the value is just the default value
			// (e.g. aawiki) and we wait for the user to make a choice first.
			pNsInit = handleWikiSelect($wiki.get(0));
		}
	}

	$wiki.on('change', function () {
		localStorage.setItem('orpht-form-wiki', this.value);
	});

	$limit.on('change', function () {
		localStorage.setItem('orpht-form-limit', this.value);
	});

	// Prevent Chosen from hardcode-copying the width,
	// as that causes issues when the width changes dynamically
	// through the responsive stylesheet.
	$('.chosen-select').chosen({ 'width': '100%' });
}());

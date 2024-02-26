<script type="text/javascript">
	(function (w, d, id, ns, s) {
		var c = w[ns] = w[ns] || {};
		if (!c.ready) {
			c.q = [];
			c.ready = function () {
				c.q.push(arguments);
			};
			<?php foreach ( $ready as $args ) : ?>
			c.ready.apply(c, <?php print $this->kit->exportJsVar( $args ); // WPCS: XSS ok. ?>);
			<?php endforeach; ?>
		}

		<?php if ( $isDevMode ) : ?>
		// The only reliable solution for multiple scripts on IE < 10 and buggy
		// Firefox is to use AJAX. As dev server is almost always from the same
		// origin, we don't have to deal with the cross-domain requests. It's slower
		// to load them one by one, but less code than managing a pool of requests.
		(function (urls) {
			if (d.getElementById(id)) {
				return;
			}

			var e = d.createElement('meta');
			e.id = id;
			var h = d.getElementsByTagName(s)[0];
			h.parentNode.insertBefore(e, h);

			var i = 0;
			var exec = window.execScript || function (data) {
				window["eval"].call(window, data);
			};
			var continueLoading = function () {
				if (!urls[i]) {
					return;
				}

				var url = urls[i++];
				var xhr = new XMLHttpRequest();
				xhr.onreadystatechange = function () {
					if (this.readyState == 4 && this.status == 200) {
						exec(xhr.responseText + "\n//# sourceURL=" + url);
						setTimeout(continueLoading, 0);
					}
				};
				xhr.open("GET", url, true);
				xhr.send();
			};
			continueLoading();
		})(<?php print $this->kit->exportJsVar( array_values( $urls ) ); // WPCS: XSS ok. ?>);
		<?php else : ?>
		if (!d.getElementById(id)) {
			var e = d.createElement(s);
			e.id = id;
			e.src = <?php print $this->kit->exportJsVar( reset( $urls ) ); // WPCS: XSS ok. ?>;
			var h = d.getElementsByTagName(s)[0];
			h.parentNode.insertBefore(e, h);
		}
		<?php endif; ?>
	})(window, document, 'ctx-loader', 'Contextly', 'script');

	<?php print $code; // WPCS: XSS ok. ?>
</script>

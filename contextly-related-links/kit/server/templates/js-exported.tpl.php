(function(c, ns){var v=c[ns]=c[ns]||{};<?php
foreach ( $vars as $name => $value ) {
	?>v[<?php print $name; // WPCS: XSS ok. ?>]=<?php print $value; // WPCS: XSS ok. ?>;
	<?php
}
?>
})(Contextly,<?php print $namespace; // WPCS: XSS ok. ?>);

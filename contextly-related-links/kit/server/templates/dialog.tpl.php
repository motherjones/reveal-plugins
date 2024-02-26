<?php
/**
 *
 * @var string $language
 * @var string $head
 */

?><!DOCTYPE html>
<html lang="<?php print $language; // WPCS: XSS ok. ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<?php print $head; // WPCS: XSS ok. ?>
</head>
<body class="contextly-overlay">
<script type="text/javascript">
Contextly.ready('load', <?php print $this->kit->exportJsVar( 'overlay-dialogs/' . $type ); // WPCS: XSS ok. ?>, function() {
    Contextly.overlayDialog.Controller.render(<?php print $this->kit->exportJsVar( $type ); // WPCS: XSS ok. ?>);
});
</script>
</body>
</html>

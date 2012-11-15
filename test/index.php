<?php
require("../stanbicmm.php");

try {
	$mm = new StanbicMM('2348181019104', '0000');
	print_r($mm->get_transactions());
} catch(SyntaxException $e) {
	echo 'Syntax ish :/';
} catch(Exception $e) {
	echo $e;
}
?>
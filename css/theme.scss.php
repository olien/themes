$mycolor: <?php echo $theme->getValue('color1'); ?>;
$margin: 16px;

.content-navigation {
	border-color: $mycolor;
	color: darken($mycolor, 9%);
}

.border {
	padding: $margin / 2;
	margin: $margin / 2;
	border-color: $mycolor;
}

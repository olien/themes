<?php

// check if necessary dirs/files exists
$scssPhpFile = rex_website_theme::getScssPhpSourceFile();
$scssPhpFilePath = dirname(rex_website_theme::getScssPhpSourceFile());
$cssPath = dirname(rex_website_theme::constructCSSFileWithPathForBackend(1));

if (!is_dir($scssPhpFilePath)) {
	echo rex_warning($I18N->msg('website_manager_theme_dir_not_found', $scssPhpFilePath));
}

if (!file_exists($scssPhpFile)) {
	echo rex_warning($I18N->msg('website_manager_theme_file_not_found', $scssPhpFile));
}

if (!is_dir($cssPath)) {
	echo rex_warning($I18N->msg('website_manager_theme_dir_not_found', $cssPath));
}

$func = rex_request('func', 'string');
$theme_id = rex_request('theme_id', 'int');

// delete theme (when link clicked from rex list)
if($func == 'delete' && $theme_id > 0) {
	$sql = rex_sql::factory();
	//  $sql->debugsql = true;
	$sql->setTable('rex_website_theme');
	$sql->setWhere('id='. $theme_id . ' LIMIT 1');

	if ($sql->delete()) {
		echo rex_info($I18N->msg('website_manager_theme_deleted'));
	} else {
		echo rex_warning($sql->getErrro());
	}

	// delete css file
	rex_website_theme::deleteCSSFile($theme_id);
	
	$func = '';
}

// add or edit theme (after form submit)
rex_register_extension('REX_FORM_SAVED', function ($params) {
	global $REX;

	// get theme id
	if (isset($params['form']->params['theme_id'])) {
		// edit
		$themeId = $params['form']->params['theme_id'];
	} else {
		// add
		$themeId = rex_website_manager_utils::getLastInsertedId($params['sql']);
	}

	// generated css file
	rex_website_theme::generateCSSFile($themeId);

	// use exit statement, if you want to debug
	return true;
});

// delete theme (after form submit)
rex_register_extension('REX_FORM_DELETED', function ($params) {
	global $REX;

	// get theme id
	$themeId = $params['form']->params['theme_id'];
	
	// delete css file
	rex_website_theme::deleteCSSFile($themeId);

	// use exit statement, if you want to debug
	return true;
});

// output
echo '<div class="rex-addon-output-v2">';

if ($func == '') {
	// rex list
	$query = 'SELECT * FROM rex_website_theme ORDER BY id';

	$list = rex_list::factory($query);
	$list->setNoRowsMessage($I18N->msg('website_manager_theme_no_sytles_available'));
	$list->setCaption($I18N->msg('website_manager_theme_list_of_themes'));
	$list->addTableAttribute('summary', $I18N->msg('website_manager_theme_list_of_themes'));
	$list->addTableColumnGroup(array(40, 40, '*', 80, 80));

	$list->removeColumn('icon');
	$list->removeColumn('color1');

	$list->setColumnLabel('id', $I18N->msg('website_manager_website_id'));
	$list->setColumnLabel('name', $I18N->msg('website_manager_theme_name'));
	$list->setColumnParams('name', array('func' => 'edit', 'theme_id' => '###id###'));

	// icon column
	$thIcon = '<a class="rex-i-element rex-i-generic-add" href="'. $list->getUrl(array('func' => 'add')) .'"><span class="rex-i-element-text">' . $I18N->msg('website_manager_theme_create') . '</span></a>';
	$tdIcon = '<span class="rex-i-element rex-i-generic"><span class="rex-i-element-text">###name###</span></span>';
	$list->addColumn($thIcon, $tdIcon, 0, array('<th class="rex-icon">###VALUE###</th>','<td class="rex-icon">###VALUE###</td>'));
	$list->setColumnParams($thIcon, array('func' => 'edit', 'theme_id' => '###id###'));

	// functions column spans 2 data-columns
	$funcs = $I18N->msg('website_manager_theme_functions');
	$list->addColumn($funcs, $I18N->msg('website_manager_theme_edit'), -1, array('<th colspan="2">###VALUE###</th>','<td>###VALUE###</td>'));
	$list->setColumnParams($funcs, array('func' => 'edit', 'theme_id' => $theme_id, 'theme_id' => '###id###'));

	$delete = 'deleteCol';
	$list->addColumn($delete, $I18N->msg('website_manager_theme_delete'), -1, array('','<td>###VALUE###</td>'));
	$list->setColumnParams($delete, array('theme_id' => '###id###', 'func' => 'delete'));
	$list->addLinkAttribute($delete, 'onclick', 'return confirm(\'' . $I18N->msg('website_manager_theme_delete_confirm') . '\');');

	$list->show();
} elseif ($func == 'add' || $func == 'edit' && $theme_id > 0) {
	// rex form
	if ($func == 'edit') {
		$formLabel = $I18N->msg('website_manager_theme_theme_edit');
	} elseif ($func == 'add') {
		$formLabel = $I18N->msg('website_manager_theme_theme_add');
	}

	$form = rex_form::factory('rex_website_theme', $formLabel, 'id=' . $theme_id);
	$form->addErrorMessage(REX_FORM_ERROR_VIOLATE_UNIQUE_KEY, $I18N->msg('website_manager_theme_theme_exists'));

	// name
	$field =& $form->addTextField('name'); 
	$field->setLabel($I18N->msg('website_manager_theme_name')); 

	// color1
	$field =& $form->addTextField('color1'); 
	$field->setLabel($I18N->msg('website_manager_theme_color1'));
	$field->setAttribute('class', 'colorpicker');
	$field->setAttribute('style', 'visibility: hidden; height: 20px;');

	// add here more stuff

	if ($func == 'edit') {
		$form->addParam('theme_id', $theme_id);
	} elseif ($func == 'add') {
		// do nothing
	}

	// show generated css file
	$msg = rex_website_theme::constructCSSFileWithPathForFrontend($theme_id);

	if ($func == 'edit') {
		if (!file_exists(rex_website_theme::constructCSSFileWithPathForBackend($theme_id))) {
			$msg .= ' ' . $I18N->msg('website_manager_theme_css_not_found');
		}
	} elseif ($func == 'add') {
		$msg = rex_website_theme::constructCSSFileWithPathForFrontend('X');
	}

	$field =& $form->addReadOnlyField('css_file', $msg);
	$field->setLabel($I18N->msg('website_manager_theme_css_file'));

	$form->show();
}

echo '</div>';
?>

<link rel="stylesheet" type="text/css" href="../<?php echo $REX['MEDIA_ADDON_DIR']; ?>/website_manager/spectrum.css" />
<script type="text/javascript" src="../<?php echo $REX['MEDIA_ADDON_DIR']; ?>/website_manager/spectrum.js"></script>
<script type="text/javascript">jQuery(".colorpicker input").spectrum({ showInput: true,  preferredFormat: "hex", clickoutFiresChange: true, showPalette: false, /* palette: [ ["#d1513c", "#8eb659", "#dfaa3c", "#cb41d2"] ], */ chooseText: "<?php echo $I18N->msg('website_manager_website_colorpicker_choose'); ?>", cancelText: "<?php echo $I18N->msg('website_manager_website_colorpicker_cancel'); ?>" });</script>

<script type="text/javascript">
jQuery(document).ready(function($) {
	$('.rex-form form').submit(function() {
		doSubmit = true;

		$( "input.colorpicker" ).each(function( index ) {
			if ($(this).val() === '') {
				doSubmit = false;
			}
		});
		
		if (!doSubmit) {
			alert('Bitte alle Farbfelder ausfüllen!');
			return false;
		} else {
			return true;
		}
	});
});
</script>


<?php
class rex_website_theme {
	protected $id;
	protected $name;
	protected $themeSql;

	const cssFileStart = 'theme';

	public function __construct($id) {
		$this->id = $id;

		// gets set by $this->init() called from website manager
		$this->name = 'undefined';
		$this->themeSql = null;
	}

	public function getValue($key) {
		if ($this->themeSql != null) {
			return $this->themeSql->getValue($key);
		} else {
			return '';
		}
	}

	public function init() {
		global $REX;

		if (!$REX['SETUP']) {
			if ($this->id > 0) {
				$sql = rex_sql::factory();
				//$sql->debugsql = true;
				$sql->setQuery('SELECT * FROM rex_website_theme WHERE id = ' . $this->id);

				if ($sql->getRows() > 0) {
					$this->themeSql = $sql;
				}
			}
		}
	}

	public function isAvailable() {
		if ($this->themeSql == null) {
			return false;
		} else {
			return true;
		}
	}

	public function getCSSFile() { // also checks if source scss php file is newer and compiles to target css file
		$scssPhpFile = rex_website_theme::getScssPhpSourceFile();
		$cssFile = rex_website_theme::constructCSSFileWithPathForBackend($this->id);

		if (filemtime($scssPhpFile) > filemtime($cssFile)) {
			self::generateCSSFile($this->id);
		}

		return self::constructCSSFile($this->id);
	}

	public static function constructCSSFile($themeId) {
		return self::cssFileStart . $themeId . '.css';
	}

	public static function constructCSSFileWithPathForBackend($themeId) {
		global $REX;

		return $REX['FRONTEND_PATH'] .self::constructCSSFileWithPathForFrontend($themeId);
	}

	public static function constructCSSFileWithPathForFrontend($themeId) {
		global $REX;

		return '/' . trim($REX['ADDON']['themes']['settings']['css_dir'], '/') . '/' . self::constructCSSFile($themeId);
	}

	public static function getScssPhpSourceFile() {
		global $REX;

		return $REX['ADDON']['themes']['settings']['theme_file_path'] . $REX['ADDON']['themes']['settings']['theme_file'];
	}

	public static function generateCSSFile($themeId) {
		global $REX;

		// include scss compiler
		if (!class_exists('scssc')) {
			require_once($REX['INCLUDE_PATH'] . '/addons/website_manager/plugins/themes/classes/class.scss.inc.php');
		}

		// vars
		$scssPhpFile = self::getScssPhpSourceFile();
		$cssFile = self::constructCSSFileWithPathForBackend($themeId);

		// get sql for scss php file
		$theme = rex_sql::factory();
		$theme->setQuery('SELECT * FROM rex_website_theme WHERE id = ' . $themeId);

		// interpret php to scss
		ob_start();
		include($scssPhpFile);
		$interpretedPhp = ob_get_contents();
		ob_end_clean();

		// strip comments
		$interpretedPhp = self::stripCSSComments($interpretedPhp);

		// compile scss to css
		try {
			$scss = new scssc();
			$scss->setFormatter('scss_formatter');
			$compiledScss = $scss->compile($interpretedPhp);
		} catch (Exception $e) {
			echo "<strong>SCSS Compile Error:</strong> <br/>";
		    echo $e->getMessage();
			exit;
		}

		// write css
		$fileHandle = fopen($cssFile, 'w');
		fwrite($fileHandle, $compiledScss);
		fclose($fileHandle);
	}

	public static function stripCSSComments($css) {
		return preg_replace('/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $css);
	}

	public static function deleteCSSFile($themeId) {
		global $REX;

		$cssFile = self::constructCSSFileWithPathForBackend($themeId);

		if (file_exists($cssFile)) {
			unlink($cssFile);
		}
	}
}

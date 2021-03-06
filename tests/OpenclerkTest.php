<?php

require_once(__DIR__ . "/../inc/global.php");

/**
 * Tests related to the release quality of Openclerk - i.e. more like integration tests.
 */
class OpenclerkTest extends PHPUnit_Framework_TestCase {

	function recurseFindFiles($dir, $name) {
		$result = array();
		if ($handle = opendir($dir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if (substr(strtolower($entry), -4) == ".php") {
						$result[] = $dir . "/" . $entry;
					} else if (is_dir($dir . "/" . $entry)) {
						if ($name == 'inc') {
							// ignore subdirs of inc
							continue;
						}
						if ($name == 'vendor') {
							// ignore subdirs of vendor
							continue;
						}
						if ($name == 'git') {
							// ignore 'git' dir (temporarily)
							continue;
						}
						$result = array_merge($result, $this->recurseFindFiles($dir . "/" . $entry, $entry));
					}
				}
			}
			closedir($handle);
		}
		return $result;
	}

}

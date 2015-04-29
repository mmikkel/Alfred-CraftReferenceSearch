<?php

class CraftReferenceParser {
	
	public static function parse( $referenceUrl, $baseClassUrl )
	{

		if (!$raw = file_get_contents($referenceUrl)) {
			return false;
		}

		$results = array();

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML( mb_convert_encoding( $raw, 'HTML-ENTITIES', 'UTF-8' ) );

		$xpath = new DomXPath($doc);

		$classRows = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' summaryTable ')]//tbody//tr");

		if (!$classRows || $classRows->length === 0) {
			return false;
		}

		$lastPackage = '';

		foreach ($classRows as $row) {

			// Class info
			$classInfoNodeList = $xpath->query("(td[contains(concat(' ', normalize-space(@class), ' '), ' col-class ')])[1]", $row);
			if ($classInfoNodeList->length === 0 ) {
				continue;
			}

			$classNode = $classInfoNodeList->item(0);

			// Link
			$classAnchorNodeList = $xpath->query("(a)[1]",$classNode);
			$classLink = $classAnchorNodeList->length > 0 ? $classAnchorNodeList->item(0) : false;
			if (!$classLink) {
				continue;
			}

			// Package
			$packageNodeList = $xpath->query("(td[contains(concat(' ', normalize-space(@class), ' '), ' col-package ')])[1]", $row);
			$classPackage = $packageNodeList->length > 0 ? preg_replace('@[^0-9a-z\.]+@i', '', $packageNodeList->item(0)->textContent) : false;

			// Description
			$descriptionNodeList = $xpath->query("(td[contains(concat(' ', normalize-space(@class), ' '), ' col-description ')])[1]", $row);
			$classDescription = $descriptionNodeList->length > 0 ? trim(strip_tags($descriptionNodeList->item(0)->textContent)) : '';

			$result[ preg_replace('/\s+/', '', strtolower( $classLink->textContent ) ) ] = array(
				'id' => $classLink->textContent,
				'title' => $classLink->textContent,
				'url' => $classLink->getAttribute('href'),
				'description' => $classDescription,
				'package' => $classPackage ?: $lastPackage,
			);

			if ($classPackage){
				$lastPackage = $classPackage;
			}

		}
			
		return !empty($result) ? $result : false;

	}

}

?>
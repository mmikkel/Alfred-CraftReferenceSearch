<?php

/**
 * Alfred workflow: Craft CMS Class Reference Search
 *
 * @author      Mats Mikkel Rummelhoff <http://mmikkel.no>
 * @package     Craft CMS Reference Search
 * @copyright   Copyright (c) 2015, Mats Mikkel Rummelhoff
 * @license     http://opensource.org/licenses/mit-license.php MIT License
 * @link        https://github.com/mmikkel/CraftReference-Alfred
 */

error_reporting(0);

define('CRAFT_REFERENCE_URL', 'https://buildwithcraft.com/classreference');
define('CRAFT_URL', 'https://buildwithcraft.com');
define('CACHE_PATH', './data.json');
define('CACHE_TTL', 86400);
define('ICON','icon.png');

require_once('./library/workflows.php');

$workflows = new Workflows();

// Get ze data
if (!file_exists(CACHE_PATH) || filemtime(CACHE_PATH) <= time()-CACHE_TTL) {

	require_once('./library/CraftReferenceParser.php');
	
	$data = CraftReferenceParser::parse(CRAFT_REFERENCE_URL);

	if (!$data || empty($data)){
		die("I'm sorry mama");
	}

	file_put_contents(CACHE_PATH, json_encode($data));

} else {

	$data = json_decode(file_get_contents(CACHE_PATH));

}

// Search
$data = (array) $data;
$keys = array();

$input = urlencode(strtolower($query ?: ($_GET[ 'query' ] ?: '' )));

foreach ( $data as $key => $value ) {
	if ( strpos( $key, $input ) > -1 ) {
		$packageName = $value->package;
		if(!isset($matches[$packageName])){
			$matches[$packageName] = array();
		}
		$matches[$packageName][] = $data[ $key ];
	}
}

if (!empty($matches)){
	$i = 0;
	foreach ( $matches as $packageName => $classes ) {
		foreach ( $classes as $key => $value ) {
			$value = (object) $value;
			$workflows->result( $i . '.' . $value->package . '.' . $value->id, CRAFT_URL . $value->url, $value->title . ' (' . $value->package . ')', $value->description, ICON );
			++$i;
		}
	}	
}

$searchSites = array(
	'Google' => 'https://www.google.com/#q=' . $input,
	'buildwithcraft.com' => 'http://buildwithcraft.com/search?q=' . $input,
	'StraightUpCraft' => 'https://straightupcraft.com/search/results?q=' . $input,
	'Stack Exchange' => 'http://craftcms.stackexchange.com/search?q=' . $input,
	'Craft Cookbook' => 'http://craftcookbook.net/recipes?q=' . $input,
);
foreach ($searchSites as $site => $url) {
	$workflows->result( 'x.' . preg_replace('/\s+/', '', $site), $url, 'Search ' . $site . ' for "' . $query . '"');
}

$xml = $workflows->toxml();

header("Content-Type:text/xml");
die( $xml ?: 'No dice' );

?>
<?php

/**
 * This is a script that is intended to live in the root directory of a local Shopify theme
 * Run this script from a browser to update your local theme with any edits or new files
 * that were uploaded/modified from the Shopify control panel. It is not intended to replace
 * the Theme-Kit app, but rather run alongside, as this script, when compared with the Theme-
 * Kit `download` function checks against a timestamp and only downloads those files changed 
 * since the last execution rather than the *entire fucking theme*.
 *
 * The only requirement is you add a file called `last_sync.html` with a value of 0 in your
 * Shopify assets folder. The first run of the script will take a while, so you could mess
 * with the initial timestamp to not update the entire theme.
 *

	MIT License

	Copyright (c) 2016 Sara A. King, eHouse Studio

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.

 *
 * @category   Shopify Helper
 * @package    sync_shopify_theme.php
 * @author     Sara A. King <sara@ehousestudio.com>
 * @copyright  2016 eHouse Studio
 * @license    https://opensource.org/licenses/MIT
 * @version    0.1.0
 * @link       http://pear.php.net/package/PackageName
 */

	// Update these variables with the correct values from a new *Private* app in Shopify
	$api_key = 'e5a7d4003acb3a49fcd3f031e687eef2';
	$password = '4168cc119442c42a587008d0e191ee1a';
	$store_url = 'roux-maison.myshopify.com';
	$theme_id = '159188618';

	// get_data retrives data with the API
	function get_data($request, $api_key, $password, $store_url, $theme_id)
	{
		$url = 'https://' . $api_key . ':' . $password . '@' . $store_url;
		$url =  $url.$request;
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPGET, 1); 
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		curl_close($session);
		$response = json_decode($response);
		return $response;
	}

	// put data updates or uploads data with the API
	function put_data($request, $data, $api_key, $password, $store_url, $theme_id)
	{
		$url = 'https://' . $api_key . ':' . $password . '@' . $store_url;
		$url =  $url.$request;
		$session = curl_init();
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($session, CURLOPT_POSTFIELDS,$data);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_SSL_VERIFYPEER,false);
		$response = curl_exec($session);
		curl_close($session);
		$response = json_decode($response);
		return $response;
	}

	// returns the timestamp of the last sync
	function get_last_sync($api_key, $password, $store_url, $theme_id)
	{
		$response = get_data('/admin/themes/'.$theme_id.'/assets.json?asset[key]=assets/last_sync.html&theme_id='.$theme_id, $api_key, $password, $store_url, $theme_id);
		return $response->asset->value;
	}

	// writes new timestamp to the last sync file (on shopify)
	function update_last_sync($last_sync, $api_key, $password, $store_url, $theme_id)
	{
		$data['asset']['key'] = 'assets/last_sync.html';
		$data['asset']['value'] = $last_sync;
		$data = json_encode($data);
		$response = put_data('/admin/themes/'.$theme_id.'/assets.json', $data, $api_key, $password, $store_url, $theme_id);
	}

	// download a file from the shopify server. this only works for images!
    function get_file($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return($result);
    }

    // using a temp file we created using get_file, write the file to the local file structure
    function write_file($text, $new_filename){
        $fp = fopen($new_filename, 'w+');
        fwrite($fp, $text);
        fclose($fp);
    }

    // get the timestamp of the last sync so we can compare with the files being pulled
	$last_sync = get_last_sync($api_key, $password, $store_url, $theme_id);
	//override for testing:
	//$last_sync = '2016-09-21T09:25:26-05:00';

	$new_last_updated_at = 0;

	// run a query to pull each asset in the theme
	$assets = get_data('/admin/themes/'.$theme_id.'/assets.json', $api_key, $password, $store_url, $theme_id);
	$updated_assets = [];

	// iterate through the assets
	foreach ($assets->assets as $key => $asset)
	{
		// check to see if the updated date on shopify is greater than the last sync date
		$updated_at = $asset->updated_at;

		if ($updated_at > $last_sync)
		{
			if ($updated_at > $new_last_updated_at)
			{
				$new_last_updated_at = $updated_at;
			}
			$file_name = $asset->key;

			// is this an image asset or a template/snippet/config/layout file (the latter file types do not have public urls!)
			if ($asset->public_url!==null)
			{
				// yes, this is an image, download it and save it
			    $temp_file_contents = get_file($asset->public_url);
			    write_file($temp_file_contents,$file_name);
			}
			else
			{
				// this is a text file of some sort. since it doesn't have a public url, we can't cURL it so the solution is to get the updated value of the file and overwrite the file in the local file structure
				$response = get_data('/admin/themes/'.$theme_id.'/assets.json?asset[key]='.$file_name.'&theme_id='.$theme_id, $api_key, $password, $store_url, $theme_id);
				file_put_contents($file_name, $response->asset->value);		    	
			}
			// save the asset data we just retrieved to report on it below
		    $updated_assets[] = $asset;
		}
	}

	// finally, update the timestamp with the newest timestamp retrieved in the assets array
	update_last_sync($new_last_updated_at, $api_key, $password, $store_url, $theme_id);

	// deets
	echo '<h3>The following files were updated:</h3>';
	echo '<pre>';
	print_r($updated_assets);
	echo '</pre>';

?>
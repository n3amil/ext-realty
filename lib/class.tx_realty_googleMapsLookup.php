<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Oliver Klee <typo3-coding@oliverklee.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Class 'tx_realty_googleMapsLookup' for the 'realty' extension.
 *
 * This class represents a service to look up geo coordinates via Google Maps.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_googleMapsLookup {
	/**
	 * @var string the base URL of the Google Maps geo coding service
	 */
	const BASE_URL =
		'http://maps.google.com/maps/geo?sensor=false&output=csv&key=';

	/**
	 * @var string the Google Maps geo coding base URL including the key
	 *             and the "q" variable name
	 */
	private $baseUrlWithKey;

	/**
	 * @var float the amount of time (in seconds) that need to pass between
	 *            subsequent geocoding requests
	 */
	const GEOCODING_THROTTLING = 1.75;

	/**
	 * @var float the timestamp of the last geocoding request
	 */
	static private $lastGeocodingTimestamp = 0.00;

	/**
	 * @var tx_oelib_templatehelper plugin configuration
	 */
	private $configuration;

	/**
	 * The constructor.
	 *
	 * @param tx_oelib_templatehelper the plugin configuration
	 */
	public function __construct(tx_oelib_templatehelper $configuration) {
		if (!$configuration->hasConfValueString(
			'googleMapsApiKey', 's_googlemaps'
		)) {
			throw new Exception(
				'The Google Maps API key was missing from the configuration.'
			);
		}

		$this->configuration = $configuration;

		$this->baseUrlWithKey = self::BASE_URL .
			$configuration->getConfValueString(
				'googleMapsApiKey', 's_googlemaps'
			) . '&q=';
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		unset($this->configuration);
	}

	/**
	 * Looks up the geo coordinates of an address.
	 *
	 * @param string the street of the address, may be empty
	 * @param string the ZIP code of the address, may be empty
	 * @param string the district of the address, may be empty
	 * @param string the city of the address, may be empty
	 * @param integer the country of the address as a UID from
	 *                static_info_tables, if this is 0, the default
	 *                country set in the configuration will be used
	 *
	 * @return array an array with the geo coordinates using the keys
	 *               'longitude' and 'latitude' or an empty array if the
	 *               lookup failed
	 */
	public function lookUp(
		$street = '', $zip = '', $district = '', $city = '', $countryUid = 0
	) {
		if (($zip . $district . $city) == '') {
			return array();
		}

		$addressParts = array();
		foreach (array(
			$street, trim($zip . ' ' . $district), $city,
			$this->getCountryCode($countryUid),
		) as $part) {
			if ($part != '') {
				$addressParts[] = $part;
			}
		}

		$delay = 0;

		do {
			if ($delay > 0) {
				usleep($delay);
			}
			$this->throttle();
			$rawResult = t3lib_div::getURL(
				$this->baseUrlWithKey . urlencode(implode(', ', $addressParts))
			);
			if ($rawResult === false) {
				throw new Exception(
					'There was an error connecting to the Google Maps server.'
				);
			}

			$delay += 100000;

			$resultParts = t3lib_div::trimExplode(',', $rawResult, true);
			$status = $resultParts[0];
		// 620 = too many requests too fast
		} while ($status == '620');

		if ($status == '200') {
			// 200 = Okay
			$latitude = $resultParts[2];
			$longitude = $resultParts[3];
			$result = array(
				'latitude' => $latitude,
				'longitude' => $longitude,
			);
		} else {
			$result = array();
		}

		return $result;
	}

	/**
	 * Makes sure the necessary amount of time has passed since the last
	 * geocoding request.
	 */
	private function throttle() {
		if (self::$lastGeocodingTimestamp > 0) {
			$secondsSinceLastRequest
				= microtime(TRUE) - self::$lastGeocodingTimestamp;
			if ($secondsSinceLastRequest < self::GEOCODING_THROTTLING) {
				usleep( 1000000 *
					(self::GEOCODING_THROTTLING - $secondsSinceLastRequest)
				);
			}
		}

		self::$lastGeocodingTimestamp = microtime(TRUE);
	}

	/**
	 * Reads the default ISO 3166-1 alpha2 country code for a UID from
	 * static_info_tables.
	 *
	 * @param integer a country UID from static_info_tables, will be used if it
	 *                is > 0 otherwise the configuration of "defaultCountryUID"
	 *                will be used
	 *
	 * @return string the ISO 3166-1 alpha 2 country code for the provided UID
	 *                if it was > 0, otherwise the country code for the UID
	 *                configured in "defaultCountryUID", an empty string if the
	 *                UID which was taken does not map to a country
	 */
	private function getCountryCode($uid) {
		$actualUid = ($uid > 0)
			? $uid
			: $this->configuration->getConfValueInteger(
				'defaultCountryUID', 's_googlemaps'
			);

		try {
			$result = tx_oelib_MapperRegistry::get('tx_oelib_Mapper_Country')
				->find($actualUid)->getIsoAlpha2Code();
		} catch (Exception $exception) {
			$result = '';
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_googleMapsLookup.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_googleMapsLookup.php']);
}
?>
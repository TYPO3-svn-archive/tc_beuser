<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Ivan Kartolo <ivan.kartolo@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

class tx_tcbeuser_compatibility implements t3lib_Singleton {
	/**
	 * @var boolean
	 */
	protected $isVersion6 = FALSE;

	/**
	 * @return tx_ttnews_compatibility
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('tx_tcbeuser_compatibility');
	}

	/**
	 * Creates this object.
	 */
	public function __construct() {
		if (class_exists('t3lib_utility_VersionNumber')) {
			if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 6000000) {
				$this->isVersion6 = TRUE;
			}
		}
	}

	/**
	 * Forces the integer $theInt into the boundaries of $min and $max. If the $theInt is 'FALSE' then the $zeroValue is applied.
	 *
	 * @param integer $theInt Input value
	 * @param integer $min Lower limit
	 * @param integer $max Higher limit
	 * @param integer $zeroValue Default value if input is FALSE.
	 * @return integer The input value forced into the boundaries of $min and $max
	 * @deprecated removed in TYPO3 6.0.0 already
	 */
	public function intInRange($theInt, $min, $max = 2000000000, $zeroValue = 0) {
		if ($this->isVersion6) {
			return t3lib_utility_Math::forceIntegerInRange($theInt, $min, $max, $zeroValue);
		} else {
			return t3lib_div::intInRange($theInt, $min, $max, $zeroValue);
		}
	}

}
?>
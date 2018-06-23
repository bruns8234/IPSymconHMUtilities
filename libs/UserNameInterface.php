<?php

declare(strict_types=1);

trait UserNameInterface
{
	$hmDeviceNames = [
		'ANZ' => 'LED Funk-Statusanzeige',
		'FBH' => 'Fernbedienung (Handgerät)',
		'FBA' => 'Fernbedienung (Aufputz)',
		'FBU' => 'Fernbedienung (Unterputz)',
		'FBV' => 'Fernbedienung (Modul)',
		'TFK' => 'Tür/Fensterkontakt',
		'KMS' => 'Wetter Kombi-Sensor',
		'DMZ' => 'Dimmer (Zwischendecke)',
		'DMS' => 'Dimmer (Zwischenstecker)',
		'DMU' => 'Dimmer (Unterputz)',
		'DMM' => 'Dimmer (Markenschalter)',
		'DMP' => 'PWM-Dimmer (Zwischendecke)',
		'STS' => 'Schalter (Zwischenstecker)',
		'STM' => 'Schalter (Zwischenstecker) mit Leistungsmessung',
		'STU' => 'Schalter (Unterputz)',
		'STA' => 'Schalter (Aufputz)',
		'STV' => 'Schalter (Modul)',
		'RLU' => 'Rolladenschalter (Unterputz)',
		'RTR' => 'Raumtemperaturregler',
		'HZV' => 'Heizkörperventilantrieb',
		'HRV' => 'Heizungsregelventil',
		'TSA' => 'Türschlossantrieb',
		'FEA' => 'Fenster-Kipp-Antrieb',
		'FGT' => 'MP3 Funk-Gong (Tischgerät)',
		'FRM' => 'Funk-Rauchmelder',
		'REG' => 'Regensensor'
	];

	/**
	 * This function get a path and a clear text name for the HM instance referred by the 
	 * lowbat variable identified by parameter $id.
	 *
	 * It is necessarry to adapt the function to the local requirements. Actually the
	 * function is designed to handle the following data hierachie in the object tree:
	 * Base category is called "HomeMatic". Below we have individual categories for the
	 * differend floors in building "Basement", "Ground level", "1st floor" but also
	 * locations like "Outside" or "Garden". Below of each of this floor/location
	 * there is one category for each room "Dining", "Living" or locations like "Pool" or
	 * "Garage". So we have a two-level localisation. Each location than have one or more
	 * categories representing all devices. Finally each channel of an device than exists
	 * as an "HM DEVICE" instance object below the "device" category object.
	 * 
	 * Example:
	 * +-> HomeMatic
	 * |   +-> Erdgeschoss
	 * |   |   +-> Küche
	 * |   |   |   +-> FBA06-EKU-A-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-MAINTENANCE
	 * |   |   |   |   |   +-> LOWBAT instance variable
	 * |   |   |   |   +-> FBA06-EKU-A-01-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-02-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-03-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-04-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-05-TUER EFL
	 * |   |   |   |   +-> FBA06-EKU-A-06-TUER EFL
	 * 0   1   2   3   4
	 * Here are the different levels 0=HomeMatic (Root), 1=Erdgeschoss (Floor), 2=Küche (Room),
	 * 3=FBA06-EKU-A-TUER EFL (Device), 4=FBA06-EKU-A-MAINTENANCE (Channel).
	 * The function gets all the names in an array. Than it combines level 1 and 2 as path
	 * and uses the first 3 Letters from level 3 to identify the device type which is returned
	 * as clear text name.
	 * IF YOUR HIERACHIE FOLLOWS A DIFFERENT APPROACH YOU MUST REWRITE THE FUNCTION TO FULLFILL
	 * YOUR SPECIAL REQUIREMENTS.
	 */
	protected function getVariableData($varID)
	{
		$levels = [];
		$id = $varID;
		do {
			$id = IPS_GetParent($id);
			if ($id > 0) {
				$levels[] = IPS_GetName($id);
			}
		} while ($id > 0);
		// Now we have [0]=channel, [1]=device, [2]=room, [3]=floor, [4]=root
		$path = $levels[3] . '/' . $levels[2];
		$devID = substr($levels[1], 0, 3);
		$name  = $levels[1];
		if (isset($hmDeviceNames[$devID])) {
			$name = $hmDeviceNames[$devID] . ' (' . ord(substr($levels[1], 10, 1)) - 64 . ')';
		}
		$result = [$path, $name, $levels[1]];
		
		return $result;
	}
}
?>
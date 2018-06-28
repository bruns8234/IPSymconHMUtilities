<?php

declare(strict_types=1);

trait UserNameInterface
{
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
IPS_LogMessage('getVariableData', 'START of Function');
		$hmDeviceNames = [
			'ANZ' => 'LED Funk-Statusanzeige',
			'FBH' => 'Fernbedienung',
			'FBA' => 'Aufputz-FB-Sender',
			'FBU' => 'Unterputz-FB-Sender',
			'FBV' => 'Modul-FB-Sender',
			'TFK' => 'Tür/Fensterkontakt',
			'KMS' => 'Wetter Kombi-Sensor',
			'DMZ' => 'Zwischendeckendimmer',
			'DMS' => 'Steckdosendimmer',
			'DMU' => 'Unterputzdimmer',
			'DMM' => 'Markenschalterdimmer',
			'DMP' => 'Zwischendecken-PWM-Dimmer',
			'STS' => 'Schaltsteckdose',
			'STM' => 'Schaltsteckdose mit Leistungsmessung',
			'STU' => 'Unterputzschaltaktor',
			'STA' => 'Aufputzschaltaktor',
			'STV' => 'Modulschaltaktor',
			'RLU' => 'Unterputz-Rolladenschalter',
			'RTR' => 'Raumtemperaturregler',
			'HZV' => 'Heizkörperventilantrieb',
			'HRV' => 'Heizungsregelventil',
			'TSA' => 'Türschlossantrieb',
			'FEA' => 'Fenster-Kipp-Antrieb',
			'FGT' => 'MP3 Tisch-Funk-Gong',
			'FRM' => 'Funk-Rauchmelder',
			'REG' => 'Regensensor'
		];
		$hmRooms = [
			'URK' => 'Untergeschoss/Reifenkeller',
			'KET' => 'Erdgeschoss/Kellertreppe',
			'ESZ' => 'Erdgeschoss/Schlafzimmer',
			'EFL' => 'Erdgeschoss/Flur',
			'EWZ' => 'Erdgeschoss/Wohnzimmer',
			'EKZ' => 'Erdgeschoss/Kaminzimmer',
			'EEZ' => 'Erdgeschoss/Esszimmer',
			'EKU' => 'Erdgeschoss/Küche',
			'EWC' => 'Erdgeschoss/Gäste-WC',
			'EBZ' => 'Erdgeschoss/Badezimmer',
			'EWF' => 'Erdgeschoss/Windfang',
			'OLF' => 'Obergeschoss/Lounge und Flur',
			'OGH' => 'Obergeschoss/Gästezimmer Hof',
			'OTH' => 'Obergeschoss/Thorsten',
			'OGS' => 'Obergeschoss/Gästezimmer Strasse',
			'OMZ' => 'Obergeschoss/Modelbahnzimmer',
			'OBZ' => 'Obergeschoss/Badezimmer',
			'DSB' => 'Dachboden/Spitzboden',
			'TOR' => 'Aussen/Hoftor',
			'PAV' => 'Aussen/Pavillion',
			'BAR' => 'Personen/Barbara',
			'BAR' => 'Personen/Barbara',
			'GUE' => 'Personen/Günther',
			'THO' => 'Personen/Thorsten',
			'LAS' => 'Personen/Martina und Thomas',
			'NIK' => 'Personen/Yvette und Marco',
			'GAS' => 'Personen/Gäste',
			'VAR' => 'Sonstige/Sonstige'
		];
		$devicename  = '';
		$devicename  = IPS_GetName(IPS_GetParent(IPS_GetParent($varID)));
IPS_LogMessage('getVariableData', 'Devicename is '.$devicename);

		$name = '';
		$ctname = '';
		$type = '';
		$room = '';
		$path = '';
		$typecount = 0;
		$location = '';
		
		// Name is always $devicename
		$name = $devicename;
IPS_LogMessage('getVariableData', 'Set "name" to '.$devicename);
		
		// Any - in the string?
		if (strpos($devicename, '-') === false) {
IPS_LogMessage('getVariableData', 'Malformed devicename! Can not find any -...');
		} else {
			// Split at '-'...
			$parts = explode('-', $devicename);
IPS_LogMessage('getVariableData', 'Split devicename. Get '.count($parts).' elements: ['.implode('][', $parts).']');
			// Now look how many parts we have (at least 2)...
			$cnt = count($parts);
			if ($cnt == 4) {
IPS_LogMessage('getVariableData', 'We have 4 parts... parts[3] is location');
				$location = $parts[3];
			}
			if ($cnt >= 3) {
IPS_LogMessage('getVariableData', 'We have 3 or more parts...');
				if (strlen($parts[2]) == 1) {
IPS_LogMessage('getVariableData', 'Length of parts[2] = 1 --> this "'.$parts[2].'" is the tpyecount');
					$typecount = ord($parts[2]) - 64;
				} else {
IPS_LogMessage('getVariableData', 'Length of parts[2] is invalid (must be one) - malformed devicename');
					// Malformed third part (this is not a typecounter, assume its a location)
					$location = $parts[2];
IPS_LogMessage('getVariableData', 'Set location to parts[2] "'.$parts[2].'"');
					$typecount = 0;
IPS_LogMessage('getVariableData', 'Set typecount to 0');
				}
			}
			$room = $parts[1];
IPS_LogMessage('getVariableData', 'Set room to parts[1] "'.$parts[1].'"');
			$type = $parts[0];
IPS_LogMessage('getVariableData', 'Set type to parts[0] "'.$parts[0].'"');
			
			if (strlen($type) == 5) {
				$type = substr($type, 0, 3);
IPS_LogMessage('getVariableData', 'Length of type is 5, so the first 3 chars are the type ident = "'.$type.'"');
			}
		}
		
IPS_LogMessage('getVariableData', 'Check if "'.$type.'" is defined in $hmDeviceNames...');
		if (isset($hmDeviceNames[$type])) {
			$ctname = $hmDeviceNames[$type];
IPS_LogMessage('getVariableData', 'Found type. ctname set to "'.$ctname.'"');
		}
IPS_LogMessage('getVariableData', 'Check if "'.$room.'" is defined in $hmRooms...');
		if (isset($hmRooms[$room])) {
			$path   = $hmRooms[$room];
IPS_LogMessage('getVariableData', 'Found room. path set to "'.$path.'"');
		}
		
		$result = [
			'name'      => $name,
			'ctname'    => $ctname,
			'type'      => $type,
			'typecount' => $typecount,
			'room'      => $room,
			'path'      => $path,
			'location'  => $location
		];
		return $result;
	}
}
?>
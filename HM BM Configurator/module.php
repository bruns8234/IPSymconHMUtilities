<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/Dbg_Toolbox.php';
require_once __DIR__ . '/../libs/string_Toolbox.php';
require_once __DIR__ . '/../libs/UserNameInterface.php';

class HomeMaticBMConfigurator extends IPSModule
{
	use DebugHelper;
	use StringHelper;
	use UserNameInterface;
	
	public function Create()
	{
		parent::Create();
		
		$this->RegisterPropertyInteger('ROOT_ID', 0);
		$this->RegisterPropertyInteger('SUMALARM_ID', 0);
		$this->RegisterPropertyBoolean('AUTO_UPDATE', false);
	}
	
	public function ApplyChanges()
	{
		parent::ApplyChanges();
	}
	
	public function GetConfigurationForm(): string
	{
		// Variables is a list of ALL variables with name "LOWBAT", located below an HM-DEVICE instance
		$Variables = $this->GetVariables();		// [numeric Index] = <ID of LOWBAT variable>

		// Instances is a list of ALL HMUTIL Battery Monitor Instances and the corresponding "LOWBAT" variable ID's
		$Instances = $this->GetInstances();		// [ID of BM Inst] = <ID of LOWBAT variable>
		
		// Get the SUMALARM_ID if set ( > 0 )
		$alarmID = $this->ReadPropertyInteger('SUMALARM_ID');
		
		asort($Instances);
		sort($Variables);

		// With $Instances and $Variables we create a list of all assigned, missing and unassigned batteries
		$List = [];
		// Walk through the list of LOWBAT variables
		foreach($Variables as $variableID) {
			// Get general information for this variable...
			$info       = $this->getVariableData($variableID);
			$devID      = IPS_GetParent($variableID);
			$hm_address = substr(json_decode(IPS_GetConfiguration($devID), true)['Address'], 0, -2);
			
			// Do we have a existing Instance for this battery?
			$instanceID = array_search($variableID, $Instances);
			if ($instanceID === false) {
				// No existing instance, include in Values as new Instance
				$List[$hm_address] = array(
					'address' => $hm_address,
					'variable' => $variableID,
					'path' => $info['path'],
					'device' => $info['name'],
					'instanceID' => 0,
					'create' => array(
						'moduleID' => '{3AFD8764-0C36-4E2B-9E7F-A86FB9C57AE4}',
						'configuration' => array(
							'LOWBAT_ID' => $variableID,
							'SUMALARM_ID' => $alarmID,
							'UPDATE_NAME' => $this-ReadPropertyBoolean('AUTO_UPDATE')
						)
					)
				);
			} else {
				// Include in Values as existing instance
				$List[$hm_address] = array(
					'address' => $hm_address,
					'variable' => $variableID,
					'path' => $info['path'],
					'device' => IPS_GetName($instanceID),
					'instanceID' => $instanceID,
					'create' => array(
						'moduleID' => '{3AFD8764-0C36-4E2B-9E7F-A86FB9C57AE4}',
						'configuration' => array(
							'LOWBAT_ID' => $variableID,
							'SUMALARM_ID' => $alarmID,
							'UPDATE_NAME' => false
						)
					)
				);
			}
		}

		// Check if any Instance exist which refers to an non existing Lowbat variables
		foreach($Instances as $instanceID => $variableID) {
			$result = array_search($variableID, $Variables);
			if ($result === false) {
				// Get HM Address out of the invalid instance...
				$hm_address = substr(json_decode(IPS_GetConfiguration($instanceID), true)['Address'], 0, -2);
				
				// Include in Values as invalid instance
				$List[$hm_address] = array(
					'address' => $hm_address,
					'variable' => $variableID,
					'path' => '-/-',
					'device' => '-/-',
					'name' => '-/-',
					'instanceID' => $instanceID
				);
			}
		}
		
		// Remove the alphanumeric keys from the list
		$Values = [];
		foreach($List as $record) {
			$Values[] = $record;
		}
		// then convert all strings to UTF8 (required by json_encode)
		$Values = $this->utf8_string_array_encode($Values);
		
		// Read the static part of the configuration form and include the generated values
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		$Form['actions'][0]['values'] = $Values;
		$this->SendDebug('FORM', json_encode($Form), 0);
		$this->SendDebug('FORM', json_last_error_msg(), 0);
		
		return json_encode($Form);
	}
	
	private function GetInstances(): array
	{
		$InstanceList = IPS_GetInstanceListByModuleID("{3AFD8764-0C36-4E2B-9E7F-A86FB9C57AE4}");
		// We have now a plain list with ObjectIDs as Values.
		// We want to have a list with [ObjectID] => [LOWBAT_ID] pairs
		
		$Instances = [];
		foreach($InstanceList as $InstanceID) {
			$Data = json_decode(IPS_GetConfiguration($InstanceID), true);
			$LowbatID = $Data['LOWBAT_ID'];
			$Instances[$InstanceID] = $LowbatID;
		}
		
		return $Instances;
	}
	
	private function GetVariables(): array
	{
		$Variables = [];
		foreach(IPS_GetVariableList() as $varID) {
			if (IPS_GetObject($varID)['ObjectIdent'] == 'LOWBAT') {
				$parentID = IPS_GetParent($varID);
				if (IPS_GetObject($parentID)['ObjectType'] == 1) {
					if (IPS_GetInstance($parentID)['ModuleInfo']['ModuleID'] == "{EE4A81C6-5C90-4DB7-AD2F-F6BBD521412E}") {
						$Variables[] = $varID;
					}
				}
			}
		}
		
		return $Variables;
	}
}
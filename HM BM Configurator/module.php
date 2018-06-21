<?php

declare(strict_types=1);

class HMBMConfigurator extends IPSModule
{
	public function Create()
	{
		parent::Create();
		
		$this->RegisterPropertyInteger('ROOT_ID', 0);
	}
	
	public function ApplyChanges()
	{
		parent::ApplyChanges();
	}
	
	public function GetConfigurationForm(): string
	{
		$Instances = $this->GetInstances();		// Get list of all existing battery monitor instances
		$Variables = $this->GetVariables();		// Get list of all existing HM Instances with LOWBAT variables

		$RootName = [];
		$RootID = $this->ReadPropertyInteger('ROOT_ID');
		if ($RootID != 0) {
			$RoonName = [IPS_GetName($RootID)];
		}
		
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		
		$Values = [];
		// With $Instances and $Variables we create a list of all assigned, missing and unassigned batteries
		foreach($Variables as $LowbatID) {
			// Do we have a existing Instance for this?
			$InstanceID = array_search($LowbatID, $Instances);
			if ($InstanceID === false) {
				// No existing instance, include in Values as -not assigned-
				$Values[] = ['Valid' => true, 'VarID' => $LowbatID, 'Instance' => 0, 'Device' => '', 'Room' => '', 'Floor' => ''];
			} else {
				// We found an existing instance...
				$Values[] = ['Valid' => true, 'VarID' => $LowbatID, 'Instance' => $InstanceID, 'Device' => '', 'Room' => '', 'Floor' => ''];
			}
		}
		// Check if any Instance exist which have an invalid LowbatID
		foreach($Instances as $InstanceID => $LowbatID) {
			$result = array_search($LowbatID, $Variables);
			if ($result === false) {
				// This instance has an invalid LowbatID...
				$Values[] = ['Valid' => false, 'VarID' => $LowbatID, 'Instance' => $InstanceID, 'Device' => '', 'Room' => '', 'Floor' => ''];
			}
		}
		
		// Insert Values data into the configuration form...
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
			$LowbatID = $data['LOWBAT_ID'];
			$Instances[$InstanceID] = $LowbatID;
		}
		
		return $Instances;
	}
	
	private function GetVariables(): array
	{
		$Variables = [];
		foreach(IPS_GetVariableList() as $varID) {
			if (IPS_GetObject($varID)['ObjectIdent'] == 'LOWBAT')
				$Variables[] = $varID;
		}
		
		return $Variables;
	}
<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/Dbg_Toolbox.php';

class HomeMaticBMConfigurator extends IPSModule
{
	use DebugHelper;
	
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
		$Instances = $this->GetInstances();		// [ID of BM Inst] = <ID of LOWBAT variable>
		$Variables = $this->GetVariables();		// [numeric Index] = <ID of LOWBAT variable>
		
		asort($Instances);
		sort($Variables);
		
		$this->SendDebugArray('GetConfigForm', 'Instances', $Instances, '[%u] %u', 20);
		$this->SendDebugArray('GetConfigForm', 'Variables', $Variables, '[%u] %u', 20);
		
		$Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		
		$Values = [];
		
		/**
		 *
		 * Instance List:
		 *
		 * Addresse			Variable	Path							HM-Device			Name
		 * ------------------------------------------------------------------------------------------------------------
		 * HEQ0065705:0		23521		HomeMatic\Erdgeschoss\Flur		ANZ16-EFL-A			LED-Anzeige
		 *
		 * Für alle Einträge gilt:
		 * - Path wird aus dem Objektbaum extrahiert
		 * - HM-Device entspricht dem Instanz-Namen reduziert um den letzten Part
		 * - Name wird mit Hilfe einer statischen Liste aus dem ersten Teil des Instanz-Namen ermittelt.
		 *
		 *
		 * Values have to be created with following structure:
		 *
		 *	$values = [
		 *		x => [
		 *			"address" => "",
		 *			"variable" => "",
		 *			"path" => "",
		 *			"device" => "",
		 *			"name" => ""
		 *			"create" => [
		 *				"moduleID" => "{3AFD8764-0C36-4E2B-9E7F-A86FB9C57AE4}",
		 *				"configuration" => [
		 *					"LOWBAT_ID" => {ID der zu überwachenden LOWBAT-Variable}
		 *		]
		 *		1 => [
		 *			"parent" => 1,
		 *			"name" => "Rechenmodul - Minimum",
		 *			"address" => "2",
		 *			"create" => [
		 *				"moduleID" => "{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}",
		 *				"configuration" => [
		 *					"Calculation" => 2,
		 *					"Variables" => [
		 *					]
		 *				]
		 *			]
		 *		],
		 *		2 => [
		 *			"parent" => 1,
		 *			"instanceID" => 53398,
		 *			"name" => "Fehlerhafte Instanz",
		 *			"address" => "4"
		 *		]
		 *	];
		 */
		
		// With $Instances and $Variables we create a list of all assigned, missing and unassigned batteries
		foreach($Variables as $variableID) {
			// Do we have a existing Instance for this battery?
			$instanceID = array_search($variableID, $Instances);
			if ($InstanceID === false) {
				// No existing instance, include in Values as new Instance
				$Values[] = [
					'address' => $hm_address, 'variable' => $lowbat_id, 'path' => '', 'device' => '', 'name' => '', 
					'create' => [
						'moduleID' => '{A7B0B43B-BEB0-4452-B55E-CD8A9A56B052}',
						'configuration' => [
							'ROOT_ID' => $lowbat_id,
							'UPDATE_NAME' => true
						]
					]
				];
			} else {
				// Include in Values as existing instance
				//$Values[] = ['valid' => true, 'varID' => $LowbatID, 'instance' => $InstanceID, 'device' => '', 'room' => '', 'floor' => ''];
			}
		}
		// Check if any Instance exist which refers to an non existing Lowbat variables
		foreach($Instances as $instanceID => $variableID) {
			$result = array_search($variableID, $Variables);
			if ($result === false) {
				// Include in Values as invalid instance
				//$Values[] = ['valid' => false, 'varID' => $LowbatID, 'instance' => $InstanceID, 'device' => '', 'room' => '', 'floor' => ''];
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
			$LowbatID = $Data['LOWBAT_ID'];
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
}
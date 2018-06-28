<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/HMDataClass.php';
require_once __DIR__ . '/../libs/vP_Toolbox.php';
require_once __DIR__ . '/../libs/UserNameInterface.php';

class HomeMaticBatteryMonitor extends IPSModule
{
	use VariableProfileHelper;
	use UserNameInterface;

	public function Create()
	{
		parent::Create();

		$this->RegisterPropertyInteger('LOWBAT_ID', 0);
		$this->RegisterPropertyInteger('SUMALARM_ID', 0);
		$this->RegisterPropertyBoolean('UPDATE_NAME', false);
	}

	public function ApplyChanges()
	{
		parent::ApplyChanges();

		$this->RegisterProfileIntegerEx('HMUTIL.ModuleState', '', '', '', [[1, $this->Translate('Empty'), '', 0xff0000], [2, $this->Translate('Weak'), '', 0xffff00], [3, $this->Translate('Full'), '', 0x00ff00]]);
		$this->RegisterProfileIntegerEx('HMUTIL.SaveButton', '', '', '', [[0, $this->Translate('Save'), '', 0x0000ff]]);

		$this->RegisterVariableInteger('STATE', $this->Translate('State'), 'HMUTIL.ModuleState', 1);
		$this->RegisterVariableString('FIRST_LOW', $this->Translate('First bat. alarm'), '', 2);
		$this->RegisterVariableString('LAST_CHANGE', $this->Translate('Last bat. change'), '', 3);
		$this->RegisterVariableInteger('SAVE_CHANGE', $this->Translate('Save bat. change'), 'HMUTIL.SaveButton', 4);
		$this->PresetValues();
		$this->EnableAction('SAVE_CHANGE');

		$lowbatID = $this->ReadPropertyInteger('LOWBAT_ID');
		$this->RegisterMessage($lowbatID, VM_UPDATE);

		if ($this->ReadPropertyBoolean('UPDATE_NAME') == true) {
			$this->UpdateInstanceName();
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{
		$this->SendDebug('MessageSink', 'SenderID: ' . $SenderID . ', Message: ' . $Message, 0);

		$sourceID = $this->ReadPropertyInteger('LOWBAT_ID');
		if ($sourceID == $SenderID) {
			$this->HandleUpdate(GetValue($sourceID));

			return;
		}
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case 'SAVE_CHANGE':
				$this->SaveBatteryChange();
				break;
			default:
				throw new Exception('RequestAction: Invalid Ident ' . $Ident);
		}
	}

	public function UpdateInstanceName()
	{
		// Clear flag for automatic name updates if set
		if ($this->ReadPropertyBoolean('UPDATE_NAME') == true) {
			IPS_SetProperty($this->InstanceID, 'UPDATE_NAME', false);
		}

		// Get ID of LOWBAT-Variable
		$id = $this->ReadPropertyInteger("LOWBAT_ID");

		// getVariableData returns a full path and a clear text name for the
		// HomeMatic instance containing the lowbat variable. The function must be
		// customised in the trait file UserNameInterface.php.
		$data = $this->getVariableData($id);
		// Now we have in $data: name, ctname, type, typecount, room, path, location

		// Update Instance Data
		IPS_SetName($this->InstanceID, (strlen($data['ctname']) > 0 ? $data['ctname'] : $data['name']).' ('.$data['typecount'].')');
		$this->SetSummary($data['path'] . ' [' . $data['name'] . ']');

		// Save changed properties, if any...
		IPS_ApplyChanges($this->InstanceID);
		return true;
	}

	private function HandleUpdate(bool $NewState)
	{
		// Actual Status of Module (1=Battery empty, 2=Battery weak, 3=Battery full)
		$ModuleStateID = $this->GetIDForIdent('STATE');
		$ModuleState = GetValue($ModuleStateID);

		// Date of first LOWBAT alarm
		$FirstLowAlarmID = $this->GetIDForIdent('FIRST_LOW');

		// Handle change of LOWBAT
		if ($NewState == true && $ModuleState == 3) {
			// New LOWBAT indication arrived
			$this->SendDebug('HandleUpdate', 'Battery State Change from FULL to EMPTY', 0);
			SetValue($FirstLowAlarmID, date('d.m.Y'));
			SetValue($ModuleStateID, 1);
			// Increase value of summary alarm by one if defined (value of property is > 0)
			$SummaryAlarmID = $this->ReadPropertyInteger("SUMALARM_ID");
			if ($SummaryAlarmID > 0) {
				$value = GetValue($SummaryAlarmID);
				$value += 1;
				SetValue($SummaryAlarmID, $value);
			}

			return;
		}
		if ($NewState == true && $ModuleState == 2) {
			$this->SendDebug('HandleUpdate', 'Battery State Change from WEAK to EMPTY', 0);
			SetValue($ModuleStateID, 1);

			return;
		}
		if ($NewState == false && $ModuleState == 1) {
			// Battery is weak, not full...
			$this->SendDebug('HandleUpdate', 'Battery State Change from EMPTY to WEAK', 0);
			SetValue($ModuleStateID, 2);

			return;
		}
	}

	private function PresetValues()
	{
			if (GetValue($this->GetIDForIdent('STATE')) == 0) {
				// Preset the instance variables only if state is 0 (initial value)
				SetValue($this->GetIDForIdent('STATE'), 3);
				SetValue($this->GetIDForIdent('FIRST_LOW'), '--.--.--');
				SetValue($this->GetIDForIdent('LAST_CHANGE'), '--.--.--');
			}

		return;
	}

	public function SaveBatteryChange()
	{
		$ModuleState = GetValue($this->GetIDForIdent('STATE'));
		$AlarmState = GetValue($this->ReadPropertyInteger('LOWBAT_ID'));

		if ($AlarmState != false or $ModuleState == 3) {
			return;
		} else {
			if ($AlarmState == true) {
				return;
			} else {
				// Yep, we can save the change date and reset state to full
				SetValue($this->GetIDForIdent('FIRST_LOW'), '--.--.--');
				SetValue($this->GetIDForIdent('LAST_CHANGE'), date('d.m.Y'));
				SetValue($this->GetIDForIdent('STATE'), 3);

				return;
			}
			$SmmaryAlarmID = $this->ReadPropertyInteger("SUMALARM_ID");
			if ($SummaryAlarmID > 0) {
				$value = GetValue($SummaryAlarmID);
				if ($value > 0) $value -= 1;
				SetValue($SummaryAlarmID, $value);
			}
		}
	}
}

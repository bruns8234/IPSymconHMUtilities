<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/HMDataClass.php';
require_once __DIR__ . '/../libs/vP_Toolbox.php';

class HomeMaticBatteryMonitor extends IPSModule
{
	use VariableProfileTools;
	
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('LOWBAT_ID', 0);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->RegisterProfileIntegerEx('HMUTIL.ModuleState', '', '', '', [[1, 'Empty', '', 0xff0000], [2, 'Weak', '', 0xffff00], [3, 'Full', '', 0x00ff00]]);
        $this->RegisterProfileIntegerEx('HMUTIL.SaveButton', '', '', '', [[0, 'Save', '', 0x0000ff]]);

        $this->RegisterVariableInteger('STATE', 'State', 'HMUTIL.ModuleState');
        $this->SetState(3);
        $this->RegisterVariableString('FIRST_LOW', 'First bat. alarm');
        $this->RegisterVariableString('LAST_CHANGE', 'Last bat. change');
        $this->RegisterVariableInteger('SAVE_CHANGE', 'Save bat. change', 'HMUTIL.SaveButton');
        $this->EnableAction('SAVE_CHANGE');

        $lowbatID = $this->ReadPropertyInteger('LOWBAT_ID');
        $this->RegisterMessage($lowbatID, VM_UPDATE);
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
            default:
                throw new Exception('Invalid Ident');
        }
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

    private function SetState($NewState)
    {
        SetValue($this->GetIDForIdent('STATE'), $NewState);

        return;
    }

    public function SaveBatteryChange()
    {
        $ModuleState = GetValue($this->GetIDForIdent('STATE'));
        $AlarmState = GetValue($this->ReadPropertyInteger('LOWBAT_ID'));
		
        if ($AlarmState != false or $ModuleState == 3) {
            echo 'No Alarm! No Action needed!';

            return;
        } else {
            if ($AlarmState == true) {
                echo 'Not allowed! Need to clear alarm first!';

                return;
            } else {
                // Yep, we can save the change date and reset state to full
                SetValue($this->GetIDForIdent('FIRST_LOW'), '--.--.--');
                SetValue($this->GetIDForIdent('LAST_CHANGE'), date('d.m.Y'));
                SetValue($this->GetIDForIdent('STATE'), 3);
				
				return;
            }
        }
    }
	
	protected private function RegisterProfileInteger($Name, $Icon, 
}

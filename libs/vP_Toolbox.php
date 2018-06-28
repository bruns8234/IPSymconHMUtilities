<?php

declare(strict_types=1);

trait VariableProfileHelper
{
    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix)
    {
        $this->RegisterProfile(0, $Name, $Icon, $Prefix, $Suffix, 0, 0, 0);
    }

    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix);
        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
    }

    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
    {
        $this->RegisterProfile(1, $Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize);
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[count($Associations) - 1][0];
        }
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        $OldValues = array_column(IPS_GetVariableProfile($Name)['Associations'], 'Value');
        
		foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
            $OldKey = array_search($Association[0], $OldValues);
            if ($OldKey !== false) {
                unset($OldValues[$OldKey]);
            }
        }
		if (count($OldValues) > 0) {
			foreach ($OldValues as $OldKey => $OldValue) {
				IPS_SetVariableProfileAssociation($Name, $OldValue, '', '', 0);
			}
		}
    }

    protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
    {
        $this->RegisterProfile(2, $Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits);
    }

    protected function RegisterProfile($VarTyp, $Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits = 0)
    {
        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $VarTyp);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != $VarTyp) {
                throw new Exception('Variable profile type does not match for profile ' . $Name, E_USER_WARNING);
            }
        }
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        if ($VarTyp != 0) {
            IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        }
        if ($VarTyp == 2) {
            IPS_SetVariableProfileDigits($Name, $Digits);
        }
    }

    protected function UnregisterProfile(string $Name)
    {
        if (!IPS_VariableProfileExists($Name)) {
            return;
        }
		$hold = false;
        foreach (IPS_GetVariableList() as $VarID) {
            if (IPS_GetParent($VarID) == $this->InstanceID) {
                continue;
            } else {
				$info = IPS_GetVariable($VarID);
				$hold = ($info['VariableCustomProfile'] == $Name) or ($info['VariableProfile'] == $Name);
            }
			if ($hold == true) return;
        }
        IPS_DeleteVariableProfile($Name);
    }
}
?>
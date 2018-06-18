<?

if (!defined('IPS_BASE'))  { define("IPS_BASE", 10000); }
if (!defined('VM_UPDATE')) { define("VM_UPDATE", IPS_BASE + 603); }


    // Klassendefinition
    class HomeMaticBatteryMonitor extends IPSModule {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
 
			$this->RegisterPropertyInteger("LOWBAT_ID", 0);
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
			
			$this->RegisterVariableInteger("STATE", "State");
			$this->RegisterVariableString("FIRST_LOW", "First Alarm");
			$this->RegisterVariableString("LAST_CHANGE", "Last Change");
			
			$lowbatID = $this->ReadPropertyInteger("LOWBAT_ID");
			$this->RegisterMessage($lowbatID, VM_UPDATE);
		}
 
		public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {
		
			$this->SendDebug("MessageSink", "SenderID: ". $SenderID .", Message: ". $Message, 0);
			
			$sourceID = $this->ReadPropertyInteger("LOWBAT_ID");
			if($sourceID == $SenderID) {
				$this->HandeUpdate(GetValue($sourceID));
				return;
			}
		}
		
		public function HandleUpdate($NewState) {

			// Actual Status of Module (1=Battery empty, 2=Battery weak, 3=Battery full)
			$ModuleStateID = $this->GetIDForIdent("STATE");
			$ModuleState   = GetValue($ModuleStateID);
			
			// Date of first LOWBAT alarm
			$FirstLowAlarmID = $this->GetIDForIdent("FIRST_LOW");

			// Date of last battery change
			$LastBatteryChangeID = $this->GetIDForIdent("LAST_CHANGE");
			
			// Handle change of LOWBAT
			if ($NewState == true && $ModuleState == 3) {
				// New LOWBAT indication arrived
				$this->SendDebug("HandleUpdate", "Battery State Change from FULL to EMPTY", 0);
				SetValue($this->FirstLowAlarmID, date("d.m.Y"));
				SetValue($this->ModuleStateID, 1);
				return;
			}
			if ($NewState == true && $ModuleState == 2) {
				$this->SendDebug("HandleUpdate", "Battery State Change from WEAK to EMPTY", 0);
				SetValue($this->ModuleStateID, 1);
				return;
			}
			if ($NewState == false && $ModuleState == 1) {
				// Battery is weak, not full...
				$this->SendDebug("HandleUpdate", "Battery State Change from EMPTY to WEAK", 0);
				SetValue($this->ModuleStateID, 2);
				return;
			}
		}
	}
?>
<?

if (!defined('IPS_BASE'))  { define("IPS_BASE", 10000); }
if (!defined('VM_UPDATE')) { define("VM_UPDATE", IPS_BASE + 603); }


    // Klassendefinition
    class HomematicBatteriyMonitor extends IPSModule {
 
        // Der Konstruktor des Moduls
        // �berschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht l�schen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // �berschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht l�schen.
            parent::Create();
 
			$this->RegisterPropertyInteger("LOWBAT_ID", 0);		// ID der Batterievariablen (bool, true=Bat.leer, false=Bat.voll)
			$this->RegisterPropertyBoolean("LAST_STATE", 0);
        }
 
        // �berschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht l�schen
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
			
			$LastStateID = $this->GetIDForIdent("LAST_STATE");
			$FirstLowID  = $this->GetIDForIdent("FIRST_LOW");
			
			$LastState = GetValue($LastStateID);
			
			if ($lastState == false && $NewState == true) {
				$this->SendDebug("HandleUpdate", "Change from FULL to EMPTY battery state", 0);
				// First Indication of a empty battery
				SetValue($LastStateID, true);
				SetValue($FirstLowID, date("d.m.Y"));
				SetValue($State, 0);
				return;
			}
			if ($lastState == false && 
		}
?>
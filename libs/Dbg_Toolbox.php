<?php

declare(strict_types=1);

trait DebugHelper
{
	protected function SendDebugArray($dbgSource, $arrayName, $array, $mask, $cols)
	{
		$count = 0;
		$out   = '';
		foreach($array as $key => $value) {
			if ($count < $cols) {
				$out .= sprintf($mask, $key, $value);
				$count++;
			} else {
				$this->SendDebug($dbgSource, 'Content of $' . $arrayName . ': ' . $out, 0);
				$out = '';
				$count = 0;
			}
		}
		if ($count > 0) {
			$this->SendDebug($dbgSource, 'Content of $' . $arrayName . ': ' . $out, 0);
		}
	}
}
?>
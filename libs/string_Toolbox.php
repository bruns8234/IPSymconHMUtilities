<?php

declare(strict_types=1);

trait StringHelper
{
	private function utf8_string_array_encode(&$array){
		$func = function(&$value,&$key){
			if(is_string($value)){
				$value = utf8_encode($value);
			}
			if(is_string($key)){
				$key = utf8_encode($key);
			}
			if(is_array($value)){
				$this->utf8_string_array_encode($value);
			}
		};
		array_walk($array,$func);
		return $array;
	}
}
?>
<?php

class BankAccount {

	const ACCOUNT_NUMBER_PATTERN 	       = '/(^\d{6})-(\d{2,8}$)/';
	const B_TYPE_BANK_ACCOUNT_NUMBER_IDS = [4, 5];
	const UNKNOWN_BANK_NUMBER_IDS 	     = [7, 9];

	private $number;

	public function __construct($number) {
		$this->setAccount($number);
	}

	public function getLongAccountNumber() {
		return $this->number;
	}

	private function setAccount($number) {

		if (! $this->validAccountNumber($number)) {
			throw new Exception('Invalid account number: ' . $number);
		}

		if ($this->invalidBank($number)) {
			throw new Exception('Unknown bank');
	  }

		$number = $this->translateAccountNumberTo14Base($number);

		if (! $this->hasValidChecksum($number)) {
			throw new Exception('Checksum calculation failed');
		}

		$this->number = $number;
	}

	private function validAccountNumber($number) {
		return preg_match(self::ACCOUNT_NUMBER_PATTERN, $number);
	}

	private function invalidBank($number) {
		return in_array($number[0], self::UNKNOWN_BANK_NUMBER_IDS);
	}

	private function translateAccountNumberTo14Base($number) {

		$firstPart = substr($number, 0, 6);
		$latterPart = substr($number, strpos($number, "-") + 1);

		if ($this->accountIsBType($number)) {
			return $firstPart . $latterPart[0] . $this->getZeros($latterPart) . substr($latterPart, 1);
		} else {
			return $firstPart . $this->getZeros($latterPart) . $latterPart;
		}
	}

	private function getZeros($amount) {
		return str_repeat('0', 8-strlen($amount));
	}

	private function accountIsBType($number) {
		return in_array($number[0], self::B_TYPE_BANK_ACCOUNT_NUMBER_IDS);
	}

	private function hasValidChecksum($number) {
		
		$weightedData = $this->getWeightedData(substr($number,0, 13), (str_repeat('21', 6) . 2));
			
		$checksumSum = $this->calculateCheckSum($weightedData);

		return $this->getCheckSum($checksumSum) === (int) $number[strlen($number)-1];
	}
	
	private function getWeightedData($numbers, $weights) {
		return array_map(function ($number, $weight) { 
				return $number * $weight;
			}, 
			str_split($numbers), 
			str_split($weights)
		);
	}
		
	private function calculateCheckSum($data) {
		
		return array_reduce($data, function($sum, $item){

			if ($item >= 10) {
				$sum += (int) substr($item, 0, 1);
				$sum += (int) substr($item, 1, 2);
			} else {
				$sum += $item;
			}
			return $sum;
		});
	}
	
	private function getCheckSum($checksumSum) {
		return (int) ceil($checksumSum / 10) * 10 - $checksumSum;
	}
}

$finnishTypeaNumber = new BankAccount('123456-785');
$finnishTypebNumber = new BankAccount('423456-781');

//Failing tests:
	//$notValidAccountNumber = new BankAccount('110335-1537');
	//$notValidAccountNumber = new BankAccount('110335A1537');
	//$notValidAccountNumber = new BankAccount('110335-1537C');
	//$notValidAccountNumber = new BankAccount('11033-1537');
	//$notValidAccountNumber = new BankAccount('1103355-1537');
	//$notValidAccountNumberCheckSum = new BankAccount('110335-12345678');

var_dump($finnishTypeaNumber->getLongAccountNumber());
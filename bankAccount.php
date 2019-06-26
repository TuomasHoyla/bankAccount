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

	public function getIbanAccountNumber() {
		//todo
	}

	private function setAccount($number) {

		if (! $this->validateNumber($number)) {
			throw new Exception('Invalid account number: ' . $number);
		}

		if ($this->validateBank($number)) {
			throw new Exception('Unknown bank');
	  }

		$number = $this->translateAccountNumberTo14Base($number);

		if (! $this->hasValidChecksum($number)) {
			throw new Exception('Checksum calculation failed');
		}

		$this->number = $number;
	}

	private function validateNumber($number) {
		return preg_match(self::ACCOUNT_NUMBER_PATTERN, $number);
	}

	private function validateBank($number) {
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

		$numbers = substr($number,0, 13);

		$weights = str_repeat('21', 6) . 2;

		$weightedData = [];

		for ($i = 0; $i < strlen($numbers); $i++) {

			$weightedData[] = ($weights[$i] * $numbers[$i]);

		}

		$checksumSum = array_reduce($weightedData, function($carry, $item){

			if ($item >= 10) {
				$carry += (int) substr($item, 0, 1);
				$carry += (int) substr($item, 1, 2);
				return $carry;
			} else {
				$carry += $item;
    			return $carry;
			}
		});

		$checkSum = ceil($checksumSum / 10) * 10 - $checksumSum;

		return (int) $checkSum === (int) $number[strlen($number)-1];
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

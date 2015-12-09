<?php
/**
 * Class Util
 *
 * @filesource   Util.php
 * @created      25.11.2015
 * @package      chillerlan\QRCode
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2015 Smiley
 * @license      MIT
 */

namespace chillerlan\QRCode;

/**
 *
 */
class Util{

	/**
	 * @param int $typeNumber
	 * @param int $errorCorrectLevel
	 *
	 * @return array
	 * @throws \chillerlan\QRCode\QRCodeException
	 */
	public static function getRSBlocks($typeNumber, $errorCorrectLevel){
		// PHP5 compat
		$RSBLOCK = QRConst::RSBLOCK;
		$BLOCK_TABLE = QRConst::BLOCK_TABLE;

		if(!isset($RSBLOCK[$errorCorrectLevel])){
			throw new QRCodeException('$typeNumber: '.$typeNumber.' / $errorCorrectLevel: '.$errorCorrectLevel.PHP_EOL.print_r($RSBLOCK, true));
		}

		$rsBlock = $BLOCK_TABLE[($typeNumber - 1) * 4 + $RSBLOCK[$errorCorrectLevel]];

		$list = [];
		$length = count($rsBlock) / 3;
		for($i = 0; $i < $length; $i++){
			for($j = 0; $j < $rsBlock[$i * 3 + 0]; $j++){
				$list[] = [$rsBlock[$i * 3 + 1], $rsBlock[$i * 3 + 2]];
			}
		}

		return $list;
	}

	/**
	 * @param int $typeNumber
	 * @param int $mode
	 * @param int $ecLevel
	 *
	 * @return int
	 * @throws \chillerlan\QRCode\QRCodeException
	 */
	public static function getMaxLength($typeNumber, $mode, $ecLevel){
		$RSBLOCK = QRConst::RSBLOCK;
		$MAX_LENGTH = QRConst::MAX_LENGTH;
		$MODE = QRConst::MODE;

		if(!isset($RSBLOCK[$ecLevel])){
			throw new QRCodeException('$_err: '.$ecLevel);
		}

		if(!isset($MODE[$mode])){
			throw new QRCodeException('$_mode: '.$mode);
		}

		return $MAX_LENGTH[$typeNumber - 1][$RSBLOCK[$ecLevel]][$MODE[$mode]];
	}

	/**
	 * @param string $s
	 *
	 * @return int
	 */
	public static function getMode($s){

		switch(true){
			case self::isAlphaNum($s): return self::isNumber($s) ? QRConst::MODE_NUMBER : QRConst::MODE_ALPHANUM;
			case self::isKanji($s)   : return QRConst::MODE_KANJI;
			default:
				return QRConst::MODE_BYTE;
		}

	}

	/**
	 * @param string $s
	 *
	 * @return bool
	 */
	protected static function isNumber($s){

		$len = strlen($s);
		for($i = 0; $i < $len; $i++){
			$c = ord($s[$i]);

			if(!(ord('0') <= $c && $c <= ord('9'))){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $s
	 *
	 * @return bool
	 */
	protected static function isAlphaNum($s){

		$len = strlen($s);
		for($i = 0; $i < $len; $i++){
			$c = ord($s[$i]);

			if(!(ord('0') <= $c && $c <= ord('9')) && !(ord('A') <= $c && $c <= ord('Z')) && strpos(' $%*+-./:', $s[$i]) === false){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $s
	 *
	 * @return bool
	 */
	protected static function isKanji($s){

		$i = 0;
		$len = strlen($s);
		while($i + 1 < $len){
			$c = ((0xff&ord($s[$i])) << 8)|(0xff&ord($s[$i + 1]));

			if(!(0x8140 <= $c && $c <= 0x9FFC) && !(0xE040 <= $c && $c <= 0xEBBF)){
				return false;
			}

			$i += 2;
		}

		if($i < $len){
			return false;
		}

		return true;
	}

	/**
	 * @param int $data
	 *
	 * @return int
	 */
	public static function getBCHTypeInfo($data){
		$d = $data << 10;

		while(self::getBCHDigit($d) - self::getBCHDigit(QRConst::G15) >= 0){
			$d ^= (QRConst::G15 << (self::getBCHDigit($d) - self::getBCHDigit(QRConst::G15)));
		}

		return (($data << 10)|$d)^QRConst::G15_MASK;
	}

	/**
	 * @param int $data
	 *
	 * @return int
	 */
	public static function getBCHTypeNumber($data){
		$d = $data << 12;

		while(self::getBCHDigit($d) - self::getBCHDigit(QRConst::G18) >= 0){
			$d ^= (QRConst::G18 << (self::getBCHDigit($d) - self::getBCHDigit(QRConst::G18)));
		}

		return ($data << 12)|$d;
	}

	/**
	 * @param int $data
	 *
	 * @return int
	 */
	public static function getBCHDigit($data){
		$digit = 0;

		while($data !== 0){
			$digit++;
			$data >>= 1;
		}

		return $digit;
	}

	/**
	 * @param string $c
	 *
	 * @return int
	 * @throws \chillerlan\QRCode\QRCodeException
	 */
	public static function getCharCode($c){
		$c = ord($c);

		switch(true){
			case ord('0') <= $c && $c <= ord('9'): return $c - ord('0');
			case ord('A') <= $c && $c <= ord('Z'): return $c - ord('A') + 10;
			default:
				foreach(QRConst::CHAR_MAP as $i => $char){
					if(ord($char) === $c){
						return $i;
					}
				}
		}

		throw new QRCodeException('illegal char: '.$c);
	}

	/**
	 * @param string $string
	 *
	 * @return int
	 * @throws \chillerlan\QRCode\QRCodeException
	 */
	public static function parseInt($string){
		$num = 0;

		$len = strlen($string);
		for($i = 0; $i < $len; $i++){
			$c = ord($string[$i]);
			$ord0 = ord('0');

			if($ord0 <= $c && $c <= ord('9')){
				$c = $c - $ord0;
			}
			else{
				throw new QRCodeException('illegal char: '.$c);
			}

			$num = $num * 10 + $c;
		}

		return $num;
	}

}

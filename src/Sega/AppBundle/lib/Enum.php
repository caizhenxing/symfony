<?php
namespace Dcs;
/**
 * Enumないから型チェックできるようにEnumを定義
 * 
 * @example
 * final class Animal extends Enum{
 * 	const CAT = 'cat';
 * 	const DOG = 'dog';
 * 	const BIRD = 'bird';
 * }
 * 
 * $animal = new Animal(Animal::DOG);
 * echo $animal;	// dogが表示される
 * $animal = Animal::DOG();	// これもOK
 * 
 * if($animal->equal(Animal::BIRD()))
 * 	echo "I am bird";
 * else
 * 	echo "I am not bird";
 */
abstract class Enum{
	private $scalar;

	function __construct($value)
	{
		$ref = new \ReflectionObject($this);
		$consts = $ref->getConstants();
		if (! in_array($value, $consts, true)) {
			throw new \InvalidArgumentException;
		}

		$this->scalar = $value;
	}

	final static function __callStatic($label, $args)
	{
		$class = get_called_class();
		$const = constant("$class::$label");
		return new $class($const);
	}

	/**
	 * データの値を取り出す
	 */
	final function valueOf()
	{
		return $this->scalar;
	}
	/**
	 * 同値チェック
	 * @param any $val
	 * @return boolean
	 */
	final function equal($val){
		if($val instanceof Enum)
			return $this->scalar === $val->scalar;
		else
			return $this->scalar === $val;
	}

	final function __toString()
	{
		return (string)$this->scalar;
	}
	
}
?>
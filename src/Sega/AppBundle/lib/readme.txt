一、このフォルダ内に置くPHPファイル名は、中のクラス名と同じにすること
一、このフォルダ内に置くPHPファイルには、namespace Dcs+パスにすること

例1
lib/TestClass.php
-----
<?php
namespace Dcs;

class TestClass{
	public static function IYH(){
		echo "IYHOOOOOOOOOOOOOOOOOOOOOOOOOOO";
	}
}
?>
-----

例２
lib/Samp/T2.php
-----
<?php
namespace Dcs\Samp; // Dcs+lib下のパス名をネームスペースにすること

class T2{
	public static function SGE(){
		echo "SUGEEEEEEEEEEEEEEEEEEEEEEEEEEEEEE";
	}
}
?>
-----

他のphpから呼び出す場合、下のようにrequireを指定せずに呼び出しが可能になる
例
Test.php
-----
<?php
\Dcs\TestClass::IYH();
\Dcs\Samp\T2::SGE();
?>
-----

ただし、Symfony上から呼び出した場合による
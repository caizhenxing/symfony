<?php
namespace Dcs\Security;

/**
 * 暗号化モード
 */
class Mode extends \Dcs\Enum{
	const NONE = 0;	///< 暗号化しない
	const X_OR = 1;	///< xor暗号化
	const RSA = 2;	///< aes暗号化
}
?>
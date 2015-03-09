<?php
namespace Sega\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use \Dcs\Security as sec;

class SampleSecurityController extends Controller{
	
	/* Unity側サンプル SecuritySample */

	// xor
	public function xor_reqAction($data){
		return new Response("Request is " . sec::decrypt(sec\Mode::X_OR(), $data));
	}
	
	//rsa
	public function rsa_reqAction($data){
		return new Response("Request is " . sec::decrypt(sec\Mode::RSA(), $data));
	}
	// xor
	public function xor_resAction(){
		return new Response(sec::encrypt(sec\Mode::X_OR(), "SecuritySampleXORResponse!!"));
	}

	//rsa
	public function rsa_resAction($key){
		return new Response(sec::encrypt(sec\Mode::RSA(), "SecuritySampleRSAResponse!!", $key));
	}
	
}

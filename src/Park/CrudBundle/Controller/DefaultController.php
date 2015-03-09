<?php

namespace Park\CrudBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Park\CrudBundle\Entity\Persons;
use Park\CrudBundle\Entity\Akb48;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
	public function indexAction(Request $request)
 {
       /*   $akb = new Akb48();
        $akb->setName('佐藤亜美菜'); */
 		$repository = $this->getDoctrine()->getRepository('ParkCrudBundle:Persons');
 		$person = $repository->find(2);
        $person = new Persons();
        $person->setName('マキ')
       			->setJob('segaGamer');
        // relate this product to the category
       // $person->setAkbfans($akb);

        $form = $this->createFormBuilder($person)
        ->add('name', 'text', array('attr'=> array('class'=>'form-control')))
        ->add('job', 'text', array('attr'=> array('class'=>'form-control')))
        ->add('save', 'submit',array('attr'=> array('class'=>'btn btn-large btn-success')))
        //->add(array('akbid' => 'akb48_id'))
        ->getForm();
        
        return $this->render('ParkCrudBundle:Default:index.html.twig', array(
        		'form' => $form->createView(),
        ));
        
        
/*         $em = $this->getDoctrine()->getManager();
       // $em->persist($akb);
        $em->persist($person);
        $em->flush();
        exit(\Doctrine\Common\Util\Debug::dump($person));
       return $this->render('ParkCrudBundle:Default:index.html.twsig' ,array('people'=>$person)); */
//         return new Response(
//             'Created person id: '.$person->getId()
//             .' and akb48 id: '.$akb->getId()
//         ); 
 		//$repository = $this->getDoctrine()->getRepository('ParkCrudBundle:Persons');
		
 //		$people = $repository->find(1);

//  		$fans =$this->getDoctrine()->getRepository('ParkCrudBundle:Akb48')->find(2);
//  		$people -> setAkbfans($fans);
//  		$em = $this->getDoctrine()->getManager();
//  		//$em->persist($akb);
//  		$em->persist($people);
//  		$em->flush();

 		
/*  		$query = $repository->createQueryBuilder('p')
	 		->addSelect('ak')
	 		->leftJoin('p.akbfans','ak')
	 		->where('p.name = :name')
	 		->setParameter('name', '北原里英')
	 		->orderBy('p.name', 'ASC')
	 		->getQuery();
 		
 		$people = $query->getResult(); */
 	  //  $repository = $this->getDoctrine()->getRepository('ParkCrudBundle:Persons');
	 	/* $stmt = $this->getDoctrine()->getManager()
	 	->getConnection()
	 	->prepare('SELECT COUNT(id) AS num, name FROM Person WHERE name = :name');
	 	$stmt->bindValue('name','倉持明日香');
	 	$stmt->execute();
	 	$var = $stmt->fetchAll();

 		exit(\Doctrine\Common\Util\Debug::dump($var)); */
 		//return $this->render('ParkCrudBundle:Default:index.html.twsig' ,array('people'=>$people));
    }
   	 public function newAction(Request $request)
    {
    	// create a task and give it some dummy data for this example
    	$person = new Persons();
        $person->setName('マキ')
       			->setJob('segaGamer');
    
          $form = $this->createFormBuilder($person)
         		->setAction($this->generateUrl('park_crud_new'))
         		->setMethod('GET')
        		->add('name', 'text', array('attr'=> array('class'=>'form-control')))
        		->add('job', 'text', array('attr'=> array('class'=>'form-control')))
        		->add('save', 'submit',array('attr'=> array('class'=>'btn btn-large btn-success')))
        		->add('saveAndAdd', 'submit', array('label' => 'Save and Add'))
        ->getForm();
          $form->handleRequest($request);

          if ($form->isValid()) {
          	// perform some action, such as saving the task to the database
          	exit("form vaild");
          	return $this->redirect($this->generateUrl('park_crud_new'));
          }
    	
    	return $this->render('ParkCrudBundle:Default:index.html.twig', array(
    			'form' => $form->createView(),
    	));
    }
/* 	
    public function indexAction()
    {
    	
    	$em = $this->getDoctrine()->getManager();
    	$people = $em->getRepository('ParkCrudBundle:Persons')
    	->findAllOrderedByName();
    	if (!$people) {
    		throw $this->createNotFoundException(
    				'No Person found for id '.$id
    		);
    	}
    	//exit(\Doctrine\Common\Util\Debug::dump($person));
    	$em = $this->getDoctrine()->getManager();
    	$em->persist($people);
    	$em->flush();
 
        return $this->render('ParkCrudBundle:Default:index.html.twig' ,array('people'=>$people));
    } */
}

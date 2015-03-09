<?php

namespace Park\ThroughBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Park\ThroughBundle\Entity as Entity;
use Park\ThroughBundle\Form as Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    /**
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Response
     */
    public function addAction()
    {
        $user = new Entity\User();
        $form = $this->get('form.factory')->create(new Form\AddUser(), $user);
        $request = $this->get('request');

        if ($request->getMethod() == 'POST')
        {
            $form->handleRequest($request);
            if ($form->isValid())
            {
                $em = $this->getDoctrine()->getEntityManager();
                $user->setDateAdded(new \DateTime());
                $em->persist($user);
                $em->flush();
                $this->get('session')->getFlashBag()->set('notice', 'You have successfully added '.$user->getFirstName().' '.$user->getLastName().' to the database!');
                return $this->redirect($this->generateUrl('walkthrough_add_user'));
            }
        }

        return $this->render('ParkThroughBundle:User:add.html.twig',
            array(
                'form' => $form->createView()
            ));
    }
    /**
     * @Route("/walk/user/conf")
     * @Template()
     */
    public function confAction()
    {

 		$repository = $this->getDoctrine()
 		->getRepository('ParkThroughBundle:User');
 		
 		$users = $repository->findAll();

 			return $this->render('ParkThroughBundle:User:conf.html.twig',array('users' => $users));
    }
    
    /**
     * @Route("/walk/user/del/{id}", requirements={"id" = "\d+"}, defaults={"id" = 0})
     * @Template()
     */
    public function deleteAction($id)
    {
    	if ($id == 0){ // no user id entered
    		return $this->redirect($this->generateUrl('walkthrough_confirm_user'), 301);
    	}
    
    	$em = $this->getDoctrine()->getManager();
    	$user = $em->getRepository('ParkThroughBundle:User')->find($id);
    
    	if (!$user) { // no user in the system
    		throw $this->createNotFoundException(
    				'No user found for id '.$id
    		);
    	} else {
    		$em->remove($user);
    		$em->flush();
    		return $this->render('ParkThroughBundle:User:del.html.twig');
    		//return $this->redirect($this->generateUrl('walkthrough_confirm_user'), 301);
    	}
    }
    /**
     * @Route("/walk/user/edit/{id}", requirements={"id" = "\d+"}, defaults={"id" = 0})
     * @Template()
     */
    public function editAction($id, Request $request)
    {
    	if ($id == 0){ // no user id entered
    		return $this->redirect($this->generateUrl('walkthrough_confirm_user'), 301);
    	}
    	$user = $this->getDoctrine()
    	->getRepository('ParkThroughBundle:User')
    	->find($id);
    
    	if (!$user) {// no user in the system
    		throw $this->createNotFoundException(
    				'No user found for id '.$id
    		);
    	}
    	$form = $this->get('form.factory')->create(new Form\AddUser(), $user);
    		
    	if ($request->getMethod()=='POST'){
    		$form->bind($request);
    		if ($form->isValid()){
    			$em = $this->getDoctrine()->getManager();
    			$em->persist($user);
    			$em->flush();
    			return $this->redirect($this->generateUrl('walkthrough_editOk_user'), 301);
    		}
    	} else {
    		return $this->render('ParkThroughBundle:User:edit.html.twig',array(
    				'form'=>$form->createView()	));
    	}
    
    	return array('name' => $id);
    }
    
    /**
     * @Route("/walk/user/edit_ok")
     * @Template()
     */
    public function editOkAction()
    {
    	return $this->render('ParkThroughBundle:User:editOk.html.twig');
    }
    
    
}

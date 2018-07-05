<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        $em = $this->getDoctrine()->getManager();
        $data = array();
        $types = $em->getRepository('AppBundle:ProductType')->findAll();
        foreach ($types as $type) {
        	switch ($type->getCode()) {
        		case 'CAR':
        			$data['cars'] = $em->getRepository('AppBundle:Product')->findLastByType($type->getId(),5);
        			break;

        		case 'LOD':
        			$data['lodgments'] = $em->getRepository('AppBundle:Product')->findLastByType($type->getId(),6);
        			break;
        		
        		default:
        			$data['activities'] = $em->getRepository('AppBundle:Product')->findLastByType($type->getId(),3);
        			break;
        	}
        }
        
        return $this->render('default/index.html.twig',array('data'=>$data,));
    }
}
